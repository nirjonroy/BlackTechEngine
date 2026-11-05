<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\AiContentDraft;
use App\Models\AiGenerationAttempt;
use App\Models\Blog;
use App\Models\Brand;
use App\Models\PricingPlan;
use App\Models\SocialPost;
use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceSubscription;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $metrics = [
            'customers' => User::query()->count(),
            'workspaces' => Workspace::query()->count(),
            'brands' => Brand::query()->count(),
            'subscriptions' => WorkspaceSubscription::query()->count(),
            'plans' => PricingPlan::query()->count(),
            'active_pricing_plans' => PricingPlan::query()->publiclyVisible()->count(),
            'published_blogs' => Blog::query()->published()->count(),
            'admin_accounts' => Admin::query()->count(),
        ];

        $contentStatus = [
            'draft_blogs' => Blog::query()
                ->where(function ($query): void {
                    $query
                        ->whereNull('published_at')
                        ->orWhere('published_at', '>', now());
                })
                ->count(),
            'inactive_pricing_plans' => PricingPlan::query()->where('is_active', false)->count(),
        ];

        $recentPublishedBlogs = Blog::query()
            ->published()
            ->select(['id', 'title', 'slug', 'category', 'published_at'])
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->limit(4)
            ->get();

        $subscriptionDistribution = WorkspaceSubscription::query()
            ->with('plan:id,name')
            ->selectRaw('pricing_plan_id, status, COUNT(*) as aggregate')
            ->groupBy('pricing_plan_id', 'status')
            ->orderBy('pricing_plan_id')
            ->orderBy('status')
            ->get()
            ->map(fn (WorkspaceSubscription $subscription): array => [
                'plan' => $subscription->plan?->name ?? 'Unavailable plan',
                'status' => $subscription->status->label(),
                'count' => (int) $subscription->aggregate,
            ]);

        $aiUsage = AiGenerationAttempt::query()
            ->toBase()
            ->selectRaw('COUNT(*) as attempts')
            ->selectRaw('COALESCE(SUM(COALESCE(input_tokens, 0) + COALESCE(output_tokens, 0)), 0) as tokens')
            ->first();
        $contentActivity = [
            'content_drafts' => AiContentDraft::query()->count(),
            'social_posts' => SocialPost::query()->count(),
            'ai_attempts' => (int) ($aiUsage?->attempts ?? 0),
            'ai_tokens' => (int) ($aiUsage?->tokens ?? 0),
        ];
        $monthlyTrends = $this->monthlyTrends();

        return view('admin.dashboard', compact(
            'contentActivity',
            'contentStatus',
            'metrics',
            'monthlyTrends',
            'recentPublishedBlogs',
            'subscriptionDistribution',
        ));
    }

    /** @return Collection<int, array{label: string, drafts: int, social_posts: int, ai_attempts: int}> */
    private function monthlyTrends(): Collection
    {
        return collect(range(5, 0))->map(function (int $monthsAgo): array {
            $startsAt = now()->startOfMonth()->subMonths($monthsAgo);
            $endsAt = $startsAt->copy()->addMonth();

            return [
                'label' => $startsAt->format('M Y'),
                'drafts' => AiContentDraft::query()->where('created_at', '>=', $startsAt)->where('created_at', '<', $endsAt)->count(),
                'social_posts' => SocialPost::query()->where('created_at', '>=', $startsAt)->where('created_at', '<', $endsAt)->count(),
                'ai_attempts' => AiGenerationAttempt::query()->where('created_at', '>=', $startsAt)->where('created_at', '<', $endsAt)->count(),
            ];
        });
    }
}
