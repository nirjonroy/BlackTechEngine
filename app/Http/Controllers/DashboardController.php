<?php

namespace App\Http\Controllers;

use App\Enums\AiContentDraftStatus;
use App\Enums\EntitlementKey;
use App\Enums\SocialPostStatus;
use App\Models\UsageCounter;
use App\Models\Workspace;
use App\Models\WorkspaceSubscription;
use App\Services\Subscriptions\EntitlementDecision;
use App\Services\Subscriptions\EntitlementService;
use App\Services\Subscriptions\UsageMeter;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class DashboardController extends Controller
{
    public function __construct(
        private readonly EntitlementService $entitlements,
        private readonly UsageMeter $usageMeter,
    ) {}

    public function __invoke(Request $request): View
    {
        $user = $request->user();
        $workspaces = $user->accessibleWorkspaces()
            ->withCount(['brands', 'brands as active_brands_count' => fn ($query) => $query->where('is_active', true)])
            ->orderBy('name')
            ->get();

        $activeWorkspace = $workspaces->firstWhere('id', $user->active_workspace_id) ?? $workspaces->first();
        $recentBrands = $activeWorkspace instanceof Workspace
            ? $activeWorkspace->brands()->latest()->limit(3)->get()
            : collect();
        $subscription = null;
        $quotaUsage = [];
        $contentStats = ['drafts' => collect(), 'social' => collect()];
        $monthlyTrends = collect();

        if ($activeWorkspace instanceof Workspace) {
            $activeWorkspace->loadMissing(['subscription.plan']);
            $subscription = $activeWorkspace->subscription;
            $quotaUsage = $this->quotaUsage($activeWorkspace, $subscription);
            $contentStats = $this->contentStats($activeWorkspace);
            $monthlyTrends = $this->monthlyTrends($activeWorkspace);
        }

        return view('dashboard', compact(
            'workspaces',
            'activeWorkspace',
            'recentBrands',
            'subscription',
            'quotaUsage',
            'contentStats',
            'monthlyTrends',
        ));
    }

    /**
     * @return array<string, array{label: string, used: int, limit: int|null, remaining: int|null, enabled: bool, icon: string}>
     */
    private function quotaUsage(Workspace $workspace, ?WorkspaceSubscription $subscription): array
    {
        $ownedWorkspaceCount = Workspace::query()->where('owner_id', $workspace->owner_id)->count();
        $activeBrandCount = (int) $workspace->active_brands_count;

        return [
            'workspaces' => $this->quotaItem(
                'Workspaces',
                $ownedWorkspaceCount,
                $this->entitlements->decision($workspace, EntitlementKey::MaximumWorkspaces, $ownedWorkspaceCount),
                'blocks',
            ),
            'brands' => $this->quotaItem(
                'Active brands',
                $activeBrandCount,
                $this->entitlements->decision($workspace, EntitlementKey::MaximumBrands, $activeBrandCount),
                'badge-check',
            ),
            'drafts' => $this->meteredQuotaItem($workspace, $subscription, EntitlementKey::ContentDraftsPerPeriod, 'Drafts this month', 'file-text'),
            'social_posts' => $this->meteredQuotaItem($workspace, $subscription, EntitlementKey::SocialPostsPerPeriod, 'Social posts this month', 'calendar-days'),
            'ai_attempts' => $this->meteredQuotaItem($workspace, $subscription, EntitlementKey::AiGenerationAttempts, 'AI generations this month', 'sparkles'),
            'ai_tokens' => $this->meteredQuotaItem($workspace, $subscription, EntitlementKey::AiTokens, 'AI tokens this month', 'binary'),
        ];
    }

    /** @return array{label: string, used: int, limit: int|null, remaining: int|null, enabled: bool, icon: string} */
    private function meteredQuotaItem(
        Workspace $workspace,
        ?WorkspaceSubscription $subscription,
        EntitlementKey $key,
        string $label,
        string $icon,
    ): array {
        $used = $this->currentUsage($subscription, $key);
        $decision = $this->entitlements->decision($workspace, $key, $used);

        return $this->quotaItem(
            $label,
            $used,
            $decision,
            $icon,
            $this->usageMeter->remaining($workspace, $key),
        );
    }

    /** @return array{label: string, used: int, limit: int|null, remaining: int|null, enabled: bool, icon: string} */
    private function quotaItem(
        string $label,
        int $used,
        EntitlementDecision $decision,
        string $icon,
        ?int $remaining = null,
    ): array {
        return [
            'label' => $label,
            'used' => $used,
            'limit' => $decision->limit,
            'remaining' => $remaining ?? $decision->remaining,
            'enabled' => $decision->enabled,
            'icon' => $icon,
        ];
    }

    private function currentUsage(?WorkspaceSubscription $subscription, EntitlementKey $key): int
    {
        if ($subscription === null) {
            return 0;
        }

        $counter = UsageCounter::query()
            ->whereBelongsTo($subscription, 'subscription')
            ->where('entitlement_key', $key->value)
            ->where('period_starts_at', '<=', now())
            ->where(function ($query): void {
                $query->whereNull('period_ends_at')->orWhere('period_ends_at', '>', now());
            })
            ->orderByDesc('period_starts_at')
            ->orderByDesc('id')
            ->first();

        return $counter?->consumed() ?? 0;
    }

    /** @return array{drafts: Collection<int, array{label: string, count: int}>, social: Collection<int, array{label: string, count: int}>} */
    private function contentStats(Workspace $workspace): array
    {
        $draftCounts = $workspace->contentDrafts()
            ->selectRaw('status, COUNT(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');
        $socialCounts = $workspace->socialPosts()
            ->selectRaw('status, COUNT(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        return [
            'drafts' => collect(AiContentDraftStatus::cases())->map(fn (AiContentDraftStatus $status): array => [
                'label' => $status->label(),
                'count' => (int) ($draftCounts[$status->value] ?? 0),
            ]),
            'social' => collect(SocialPostStatus::cases())->map(fn (SocialPostStatus $status): array => [
                'label' => $status->label(),
                'count' => (int) ($socialCounts[$status->value] ?? 0),
            ]),
        ];
    }

    /** @return Collection<int, array{label: string, drafts: int, social_posts: int}> */
    private function monthlyTrends(Workspace $workspace): Collection
    {
        return collect(range(5, 0))->map(function (int $monthsAgo) use ($workspace): array {
            $startsAt = now()->startOfMonth()->subMonths($monthsAgo);
            $endsAt = $startsAt->copy()->addMonth();

            return [
                'label' => $startsAt->format('M Y'),
                'drafts' => $workspace->contentDrafts()
                    ->where('created_at', '>=', $startsAt)
                    ->where('created_at', '<', $endsAt)
                    ->count(),
                'social_posts' => $workspace->socialPosts()
                    ->where('created_at', '>=', $startsAt)
                    ->where('created_at', '<', $endsAt)
                    ->count(),
            ];
        });
    }
}
