<?php

namespace App\Http\Controllers;

use App\Enums\AiContentDraftStatus;
use App\Enums\SocialPlatform;
use App\Enums\SocialPostStatus;
use App\Http\Requests\StoreSocialPostRequest;
use App\Http\Requests\UpdateSocialPostRequest;
use App\Models\AiContentDraft;
use App\Models\Brand;
use App\Models\SocialPost;
use App\Models\Workspace;
use App\Services\SocialPostWorkflow;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class SocialPostController extends Controller
{
    public function __construct(private readonly SocialPostWorkflow $workflow) {}

    public function index(Request $request): View
    {
        Gate::authorize('viewAny', SocialPost::class);
        $request->validate([
            'workspace' => ['nullable', 'integer'],
            'brand' => ['nullable', 'integer'],
            'platform' => ['nullable', Rule::enum(SocialPlatform::class)],
            'status' => ['nullable', Rule::enum(SocialPostStatus::class)],
        ]);
        $workspaces = $request->user()->accessibleWorkspaces()->with('brands')->orderBy('name')->get();
        $workspace = $request->filled('workspace') ? $workspaces->firstWhere('id', $request->integer('workspace')) : ($workspaces->firstWhere('id', $request->user()->active_workspace_id) ?? $workspaces->first());
        abort_if($request->filled('workspace') && $workspace === null, 404);
        $brand = $request->filled('brand') ? $workspace?->brands->firstWhere('id', $request->integer('brand')) : null;
        abort_if($request->filled('brand') && $brand === null, 404);
        $posts = SocialPost::query()->with(['brand', 'variants'])->whereHas('workspace', fn ($query) => $query->where('owner_id', $request->user()->getKey())->orWhereHas('members', fn ($members) => $members->whereKey($request->user()->getKey())))
            ->when($workspace, fn ($query) => $query->whereBelongsTo($workspace))->when($brand, fn ($query) => $query->whereBelongsTo($brand))
            ->when($request->filled('platform'), fn ($query) => $query->whereHas('variants', fn ($variants) => $variants->where('platform', $request->string('platform')->toString())))
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')->toString()))
            ->latest('updated_at')->paginate(12)->withQueryString();

        return view('social-content.index', ['workspaces' => $workspaces, 'selectedWorkspace' => $workspace, 'selectedBrand' => $brand, 'posts' => $posts, 'platforms' => SocialPlatform::cases(), 'statuses' => SocialPostStatus::cases()]);
    }

    public function create(Workspace $workspace, Brand $brand): View
    {
        Gate::authorize('view', $brand);
        $sources = AiContentDraft::query()->whereBelongsTo($workspace)->whereBelongsTo($brand)->whereIn('status', [AiContentDraftStatus::Approved->value, AiContentDraftStatus::ExportReady->value])->latest()->get();

        return view('social-content.create', ['workspace' => $workspace, 'brand' => $brand, 'platforms' => SocialPlatform::cases(), 'sources' => $sources, 'idempotencyKey' => Str::uuid()->toString()]);
    }

    public function store(StoreSocialPostRequest $request, Workspace $workspace, Brand $brand): RedirectResponse
    {
        $post = $this->workflow->create($workspace, $brand, $request->user(), $request->idempotencyKey(), $request->postData(), $request->variantsData(), $request->sourceDraft());

        return Redirect::route('social-content.posts.show', [$workspace, $brand, $post])->with('status', 'Social draft saved. Nothing has been published.');
    }

    public function show(Workspace $workspace, Brand $brand, SocialPost $socialPost): View
    {
        Gate::authorize('view', $socialPost);
        $socialPost->load(['variants', 'sourceDraft', 'approver', 'events.actor', 'revisions.editor']);

        return view('social-content.show', compact('workspace', 'brand', 'socialPost'));
    }

    public function edit(Workspace $workspace, Brand $brand, SocialPost $socialPost): View
    {
        Gate::authorize('update', $socialPost);
        $socialPost->load('variants');

        return view('social-content.edit', ['workspace' => $workspace, 'brand' => $brand, 'socialPost' => $socialPost, 'platforms' => SocialPlatform::cases(), 'sources' => collect()]);
    }

    public function update(UpdateSocialPostRequest $request, Workspace $workspace, Brand $brand, SocialPost $socialPost): RedirectResponse
    {
        $post = $this->workflow->update($socialPost, $request->user(), $request->postData(), $request->variantsData(), $request->expectedVersion());

        return Redirect::route('social-content.posts.show', [$workspace, $brand, $post])->with('status', 'Social draft updated. Approval may require renewal.');
    }

    public function duplicate(Request $request, Workspace $workspace, Brand $brand, SocialPost $socialPost): RedirectResponse
    {
        Gate::authorize('view', $socialPost);
        $copy = $this->workflow->duplicate($socialPost, $request->user());

        return Redirect::route('social-content.posts.edit', [$workspace, $brand, $copy])->with('status', 'Draft duplicated. Review every platform before use.');
    }
}
