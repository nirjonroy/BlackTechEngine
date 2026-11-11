<?php

namespace App\Http\Controllers;

use App\Enums\AiContentDraftStatus;
use App\Models\AiContentDraft;
use App\Models\Brand;
use App\Models\Workspace;
use App\Services\ContentEditorialWorkflow;
use App\Services\InternalLinking\InternalLinkMatcher;
use App\Services\InternalLinking\WordPressSiteIndexer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Validation\ValidationException;

class InternalLinkingController extends Controller
{
    public function index(Request $request, Workspace $workspace, Brand $brand)
    {
        $this->ensureTenantOwnership($workspace, $brand, $request);

        $pages = $brand->indexedPages()->orderBy('title')->paginate(25);
        $audits = $brand->indexingAudits()->latest('id')->take(5)->get();
        $wpConnection = $brand->wordPressConnection;

        return view('brands.internal-linking', [
            'workspace' => $workspace,
            'brand' => $brand,
            'pages' => $pages,
            'audits' => $audits,
            'wpConnection' => $wpConnection,
        ]);
    }

    public function reindex(
        Request $request,
        Workspace $workspace,
        Brand $brand,
        WordPressSiteIndexer $indexer,
    ): RedirectResponse {
        $this->ensureTenantOwnership($workspace, $brand, $request);

        $source = $request->input('source', 'wordpress_api');
        if (! in_array($source, ['wordpress_api', 'sitemap'], true)) {
            $source = 'wordpress_api';
        }

        try {
            $audit = $indexer->indexSite($brand, $request->user(), $source);

            if ($audit->status === 'failed') {
                return Redirect::back()->withErrors([
                    'indexing' => "Indexing failed: {$audit->error_message}",
                ]);
            }

            return Redirect::back()->with(
                'status',
                "Successfully indexed {$audit->pages_indexed} page(s) from WordPress ({$audit->duration_ms}ms).",
            );
        } catch (ValidationException $e) {
            return Redirect::back()->withErrors($e->errors());
        }
    }

    public function suggest(
        Request $request,
        Workspace $workspace,
        Brand $brand,
        AiContentDraft $aiContentDraft,
        InternalLinkMatcher $matcher,
    ): JsonResponse {
        $this->ensureDraftTenantOwnership($workspace, $brand, $aiContentDraft, $request);

        $suggestions = $matcher->suggestLinks($aiContentDraft);

        return response()->json([
            'suggestions' => $suggestions,
        ]);
    }

    public function apply(
        Request $request,
        Workspace $workspace,
        Brand $brand,
        AiContentDraft $aiContentDraft,
        InternalLinkMatcher $matcher,
        ContentEditorialWorkflow $editorialWorkflow,
    ): RedirectResponse {
        $this->ensureDraftTenantOwnership($workspace, $brand, $aiContentDraft, $request);

        // Security requirement: Never modify approved or published articles automatically
        if (in_array($aiContentDraft->status, [AiContentDraftStatus::Approved, AiContentDraftStatus::ExportReady], true)) {
            abort(422, 'Approved or export-ready content cannot be modified automatically. Re-open review before altering content.');
        }

        $request->validate([
            'links' => ['required', 'array', 'min:1', 'max:20'],
            'links.*.anchor' => ['required', 'string', 'max:200'],
            'links.*.url' => ['required', 'url', 'max:1000'],
        ]);

        $links = $request->input('links', []);
        $updatedContent = $matcher->applyLinks($aiContentDraft->content ?: '', $links);

        if ($updatedContent === $aiContentDraft->content) {
            return Redirect::back()->with('status', 'No eligible matching anchor text found in content to replace.');
        }

        if (config('ai.content_review_enabled')) {
            $editorialWorkflow->update(
                $aiContentDraft,
                $request->user(),
                ['content' => $updatedContent],
                $aiContentDraft->lock_version,
            );
        } else {
            $aiContentDraft->update(['content' => $updatedContent]);
        }

        return Redirect::back()->with('status', 'Internal links successfully inserted into draft content.');
    }

    private function ensureTenantOwnership(Workspace $workspace, Brand $brand, Request $request): void
    {
        abort_unless(
            $brand->workspace_id === $workspace->getKey()
            && ($request->user()?->can('view', $brand) ?? false),
            404,
        );
    }

    private function ensureDraftTenantOwnership(Workspace $workspace, Brand $brand, AiContentDraft $draft, Request $request): void
    {
        abort_unless(
            $brand->workspace_id === $workspace->getKey()
            && $draft->brand_id === $brand->getKey()
            && $draft->workspace_id === $workspace->getKey()
            && ($request->user()?->can('view', $brand) ?? false),
            404,
        );
    }
}

