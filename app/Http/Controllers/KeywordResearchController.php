<?php

namespace App\Http\Controllers;

use App\Enums\SearchIntent;
use App\Http\Requests\StoreBrandKeywordRequest;
use App\Models\Brand;
use App\Models\BrandKeyword;
use App\Models\Workspace;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Validation\ValidationException;

class KeywordResearchController extends Controller
{
    public function index(Request $request, Workspace $workspace, Brand $brand): View
    {
        Gate::authorize('view', $brand);
        abort_unless($brand->workspace_id === $workspace->getKey(), 404);

        $selectedIntent = $request->string('intent')->toString();
        $selectedCluster = $request->integer('cluster_id') ?: $request->integer('cluster');
        $onlyStarred = $request->boolean('starred');
        $search = trim($request->string('search')->toString());

        $clusters = $brand->topicClusters()
            ->withCount('keywords')
            ->orderBy('name')
            ->get();

        $keywordsQuery = $brand->keywords()
            ->with('cluster')
            ->when($selectedIntent !== '', fn ($q) => $q->where('search_intent', $selectedIntent))
            ->when($selectedCluster > 0, fn ($q) => $q->where('brand_topic_cluster_id', $selectedCluster))
            ->when($onlyStarred, fn ($q) => $q->where('is_starred', true))
            ->when($search !== '', fn ($q) => $q->where('keyword', 'like', "%{$search}%"))
            ->orderByDesc('is_starred')
            ->orderBy('keyword');

        $keywords = $keywordsQuery->paginate(25)->withQueryString();

        $totalKeywords = $brand->keywords()->count();
        $totalClusters = $clusters->count();
        $starredCount = $brand->keywords()->where('is_starred', true)->count();
        $aiGenerationEnabled = (bool) config('ai.content_generation_enabled', false);

        $stats = [
            'total_keywords' => $totalKeywords,
            'total_clusters' => $totalClusters,
            'starred_count' => $starredCount,
            'by_intent' => [
                'informational' => $brand->keywords()->where('search_intent', SearchIntent::Informational)->count(),
                'commercial' => $brand->keywords()->where('search_intent', SearchIntent::Commercial)->count(),
                'transactional' => $brand->keywords()->where('search_intent', SearchIntent::Transactional)->count(),
                'navigational' => $brand->keywords()->where('search_intent', SearchIntent::Navigational)->count(),
            ],
        ];

        return view('keywords.index', [
            'workspace' => $workspace,
            'brand' => $brand,
            'clusters' => $clusters,
            'keywords' => $keywords,
            'stats' => $stats,
            'totalKeywords' => $totalKeywords,
            'totalClusters' => $totalClusters,
            'starredCount' => $starredCount,
            'selectedIntent' => $selectedIntent,
            'selectedCluster' => $selectedCluster,
            'selectedClusterId' => $selectedCluster,
            'onlyStarred' => $onlyStarred,
            'search' => $search,
            'searchTerm' => $search,
            'intents' => SearchIntent::cases(),
            'searchIntents' => SearchIntent::cases(),
            'aiGenerationEnabled' => $aiGenerationEnabled,
        ]);
    }

    public function store(StoreBrandKeywordRequest $request, Workspace $workspace, Brand $brand): RedirectResponse
    {
        Gate::authorize('update', $brand);
        abort_unless($brand->workspace_id === $workspace->getKey(), 404);

        $kwText = trim($request->string('keyword')->toString());
        $normalized = BrandKeyword::normalize($kwText);

        $exists = $brand->keywords()->where('normalized_keyword', $normalized)->exists();
        if ($exists) {
            throw ValidationException::withMessages([
                'keyword' => 'This keyword already exists for this brand.',
            ]);
        }

        $clusterId = $request->integer('brand_topic_cluster_id');
        if ($clusterId > 0) {
            $validCluster = $brand->topicClusters()->where('id', $clusterId)->exists();
            abort_unless($validCluster, 404);
        } else {
            $clusterId = null;
        }

        $brand->keywords()->create([
            'workspace_id' => $workspace->getKey(),
            'brand_topic_cluster_id' => $clusterId,
            'created_by_user_id' => $request->user()->getKey(),
            'keyword' => $kwText,
            'normalized_keyword' => $normalized,
            'search_intent' => $request->string('search_intent')->toString(),
            'notes' => $request->string('notes')->toString() ?: null,
            'search_volume' => null, // Explicitly null: unverified external metrics
            'difficulty_score' => null,
            'is_starred' => false,
        ]);

        return Redirect::route('workspaces.brands.keywords.index', [$workspace, $brand])
            ->with('status', 'Keyword added to brand strategy.');
    }

    public function destroy(Workspace $workspace, Brand $brand, BrandKeyword $brandKeyword): RedirectResponse
    {
        Gate::authorize('update', $brand);
        abort_unless(
            $brand->workspace_id === $workspace->getKey()
            && $brandKeyword->workspace_id === $workspace->getKey()
            && $brandKeyword->brand_id === $brand->getKey(),
            404
        );

        $brandKeyword->delete();

        return Redirect::route('workspaces.brands.keywords.index', [$workspace, $brand])
            ->with('status', 'Keyword removed from brand strategy.');
    }

    public function toggleStar(Workspace $workspace, Brand $brand, BrandKeyword $brandKeyword): RedirectResponse
    {
        Gate::authorize('update', $brand);
        abort_unless(
            $brand->workspace_id === $workspace->getKey()
            && $brandKeyword->workspace_id === $workspace->getKey()
            && $brandKeyword->brand_id === $brand->getKey(),
            404
        );

        $brandKeyword->update([
            'is_starred' => ! $brandKeyword->is_starred,
        ]);

        return Redirect::back()->with('status', 'Keyword star status updated.');
    }
}
