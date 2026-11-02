<?php

namespace App\Http\Controllers;

use App\Enums\AiContentDraftStatus;
use App\Enums\AiContentLength;
use App\Enums\AiContentType;
use App\Http\Requests\AiContentDraftIndexRequest;
use App\Http\Requests\StoreAiContentDraftRequest;
use App\Http\Requests\UpdateAiContentDraftRequest;
use App\Models\AiContentDraft;
use App\Models\Brand;
use App\Models\Workspace;
use App\Services\ContentDraftExporter;
use App\Services\ContentEditorialWorkflow;
use App\Services\ContentReviewGate;
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
        ]);
    }

    public function store(StoreAiContentDraftRequest $request, Workspace $workspace, Brand $brand): RedirectResponse
    {
        $draft = DB::transaction(function () use ($brand, $request, $workspace): AiContentDraft {
            $draft = AiContentDraft::query()->create([
                ...$request->draftData(),
                'workspace_id' => $workspace->getKey(),
                'brand_id' => $brand->getKey(),
                'created_by_user_id' => $request->user()->getKey(),
            ]);
            $this->workflow->recordCreation($draft, $request->user());

            return $draft;
        });

        return Redirect::route('content-studio.drafts.show', [$workspace, $brand, $draft])
            ->with('status', 'Draft saved. Nothing has been published.');
    }

    public function show(Workspace $workspace, Brand $brand, AiContentDraft $aiContentDraft): View
    {
        Gate::authorize('view', $aiContentDraft);
        $reviewEnabled = $this->reviewGate->enabled();
        $relations = ['creator', 'model.provider', 'generationAttempts' => fn ($query) => $query->latest()];

        if ($reviewEnabled) {
            $relations[] = 'approver';
            $relations['revisions'] = fn ($query) => $query->with('editor')->limit((int) config('ai.content_revision_limit', 25));
            $relations['editorialEvents'] = fn ($query) => $query->with('actor')->limit((int) config('ai.content_review_history_limit', 100));
        }

        $aiContentDraft->load($relations);
        $previewHtml = $reviewEnabled ? $this->exporter->previewHtml($aiContentDraft) : null;

        return view('content-studio.show', compact(
            'workspace',
            'brand',
            'aiContentDraft',
            'previewHtml',
            'reviewEnabled',
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
}
