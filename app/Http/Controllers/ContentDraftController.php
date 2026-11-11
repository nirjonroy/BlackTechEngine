<?php

namespace App\Http\Controllers;

use App\Enums\AiContentDraftStatus;
use App\Enums\AiContentLength;
use App\Enums\AiContentType;
use App\Enums\EntitlementKey;
use App\Enums\UsageEventStatus;
use App\Http\Requests\AiContentDraftIndexRequest;
use App\Http\Requests\StoreAiContentDraftRequest;
use App\Http\Requests\UpdateAiContentDraftRequest;
use App\Models\AiContentDraft;
use App\Models\Brand;
use App\Models\Workspace;
use App\Services\ContentDraftExporter;
use App\Services\ContentEditorialWorkflow;
use App\Services\ContentReviewGate;
use App\Services\InternalLinking\InternalLinkMatcher;
use App\Services\Subscriptions\EntitlementService;
use App\Services\Subscriptions\UsageMeter;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Str;

class ContentDraftController extends Controller
{
    public function __construct(
        private readonly ContentEditorialWorkflow $workflow,
        private readonly ContentDraftExporter $exporter,
        private readonly ContentReviewGate $reviewGate,
        private readonly EntitlementService $entitlements,
        private readonly UsageMeter $usage,
        private readonly InternalLinkMatcher $linkMatcher,
    ) {}

    public function index(AiContentDraftIndexRequest $request): View
    {
        $workspaces = $request->user()->accessibleWorkspaces()
            ->with(['brands' => fn ($query) => $query->orderBy('name')])
            ->orderBy('name')
            ->get();
        $selectedWorkspace = $request->filled('workspace')
            ? $workspaces->firstWhere('id', $request->integer('workspace'))
            : ($workspaces->firstWhere('id', $request->user()->active_workspace_id) ?? $workspaces->first());

        abort_if($request->filled('workspace') && $selectedWorkspace === null, 404);

        $selectedBrand = null;

        if ($request->filled('brand')) {
            $selectedBrand = $selectedWorkspace?->brands->firstWhere('id', $request->integer('brand'));
            abort_if($selectedBrand === null, 404);
        }

        $drafts = AiContentDraft::query()
            ->with(['brand', 'model.provider'])
            ->when($selectedWorkspace, fn ($query, Workspace $workspace) => $query->whereBelongsTo($workspace))
            ->when($selectedBrand, fn ($query, Brand $brand) => $query->whereBelongsTo($brand))
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')->toString()))
            ->whereHas('workspace', fn ($query) => $query
                ->where('owner_id', $request->user()->getKey())
                ->orWhereHas('members', fn ($members) => $members->whereKey($request->user()->getKey())))
            ->latest('updated_at')
            ->paginate(12)
            ->withQueryString();

        $statuses = AiContentDraftStatus::cases();
        $reviewEnabled = $this->reviewGate->enabled();

        return view('content-studio.index', compact(
            'workspaces',
            'selectedWorkspace',
            'selectedBrand',
            'drafts',
            'statuses',
            'reviewEnabled',
        ));
    }

    public function create(Workspace $workspace, Brand $brand): View
    {
        Gate::authorize('view', $brand);

        return view('content-studio.create', [
            'workspace' => $workspace,
            'brand' => $brand,
            'contentTypes' => AiContentType::cases(),
            'contentLengths' => AiContentLength::cases(),
            'idempotencyKey' => (string) Str::uuid(),
            'generationUi' => $this->generationUi($workspace),
        ]);
    }

    public function store(StoreAiContentDraftRequest $request, Workspace $workspace, Brand $brand): RedirectResponse
    {
        $this->entitlements->assertFeature($workspace, EntitlementKey::ContentStudio);

        $draft = DB::transaction(function () use ($brand, $request, $workspace): AiContentDraft {
            $reservation = $this->usage->reserve(
                $workspace,
                EntitlementKey::ContentDraftsPerPeriod,
                1,
                $request->idempotencyKey(),
                $request->user(),
            );

            if ($reservation?->status === UsageEventStatus::Committed
                && $reservation->reference_type === AiContentDraft::class
                && $reservation->reference_id !== null) {
                return AiContentDraft::query()
                    ->whereBelongsTo($workspace)
                    ->whereBelongsTo($brand)
                    ->findOrFail($reservation->reference_id);
            }

            $draft = AiContentDraft::query()->create([
                ...$request->draftData(),
                'workspace_id' => $workspace->getKey(),
                'brand_id' => $brand->getKey(),
                'created_by_user_id' => $request->user()->getKey(),
            ]);
            $this->workflow->recordCreation($draft, $request->user());
            $this->usage->commit($reservation, AiContentDraft::class, $draft->getKey());

            return $draft;
        });

        return Redirect::route('content-studio.drafts.show', [$workspace, $brand, $draft])
            ->with('status', 'Draft saved. Nothing has been published.');
    }

    public function show(Workspace $workspace, Brand $brand, AiContentDraft $aiContentDraft): View
    {
        Gate::authorize('view', $aiContentDraft);
        $reviewEnabled = $this->reviewGate->enabled();
        $relations = ['creator', 'model.provider', 'featuredImage', 'generationAttempts' => fn ($query) => $query->latest()];

        if ($reviewEnabled) {
            $relations[] = 'approver';
            $relations['revisions'] = fn ($query) => $query->with('editor')->limit((int) config('ai.content_revision_limit', 25));
            $relations['editorialEvents'] = fn ($query) => $query->with('actor')->limit((int) config('ai.content_review_history_limit', 100));
        }

        $aiContentDraft->load($relations);
        $previewHtml = $reviewEnabled ? $this->exporter->previewHtml($aiContentDraft) : null;
        $internalLinkSuggestions = $this->linkMatcher->suggestLinks($aiContentDraft);

        return view('content-studio.show', compact(
            'workspace',
            'brand',
            'aiContentDraft',
            'previewHtml',
            'reviewEnabled',
            'internalLinkSuggestions',
        ));
    }

    public function edit(Workspace $workspace, Brand $brand, AiContentDraft $aiContentDraft): View
    {
        Gate::authorize('update', $aiContentDraft);

        return view('content-studio.edit', [
            'workspace' => $workspace,
            'brand' => $brand,
            'aiContentDraft' => $aiContentDraft,
            'contentTypes' => AiContentType::cases(),
            'contentLengths' => AiContentLength::cases(),
            'reviewEnabled' => $this->reviewGate->enabled(),
        ]);
    }

    public function update(UpdateAiContentDraftRequest $request, Workspace $workspace, Brand $brand, AiContentDraft $aiContentDraft): RedirectResponse
    {
        if ($this->reviewGate->enabled()) {
            $aiContentDraft = $this->workflow->update(
                $aiContentDraft,
                $request->user(),
                $request->draftData(),
                $request->expectedVersion(),
            );
        } else {
            $aiContentDraft->update($request->draftData());
        }

        return Redirect::route('content-studio.drafts.show', [$workspace, $brand, $aiContentDraft])
            ->with('status', 'Draft changes saved. Nothing has been published.');
    }

    /** @return array{allowed: bool, enabled: bool, attempts_remaining: int|null, tokens_remaining: int|null, message: string} */
    private function generationUi(Workspace $workspace): array
    {
        $enabled = (bool) config('ai.content_generation_enabled');
        $contentStudio = $this->entitlements->decision($workspace, EntitlementKey::ContentStudio);
        $attempts = $this->entitlements->decision($workspace, EntitlementKey::AiGenerationAttempts);
        $tokens = $this->entitlements->decision($workspace, EntitlementKey::AiTokens);
        $attemptsRemaining = $this->usage->remaining($workspace, EntitlementKey::AiGenerationAttempts);
        $tokensRemaining = $this->usage->remaining($workspace, EntitlementKey::AiTokens);
        $quotaAvailable = $attempts->allowed
            && $tokens->allowed
            && $attemptsRemaining !== 0
            && $tokensRemaining !== 0;
        $allowed = $enabled && $contentStudio->allowed && $quotaAvailable;

        $message = match (true) {
            ! $enabled => 'AI generation is disabled. You can still save the brief as a manual draft.',
            ! $contentStudio->allowed => 'Your current plan does not include Content Studio access.',
            ! $attempts->enabled || ! $tokens->enabled => 'Your current plan does not include AI generation.',
            $attemptsRemaining === 0 || $tokensRemaining === 0 => 'Your AI generation quota for this monthly usage period is exhausted.',
            default => 'AI generation is available. Usage is reserved before the provider request and settled from actual token usage.',
        };

        return [
            'allowed' => $allowed,
            'enabled' => $enabled,
            'attempts_remaining' => $attemptsRemaining,
            'tokens_remaining' => $tokensRemaining,
            'message' => $message,
        ];
    }
}
