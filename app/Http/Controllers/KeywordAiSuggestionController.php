<?php

namespace App\Http\Controllers;

use App\Http\Requests\ImportKeywordSuggestionsRequest;
use App\Http\Requests\SuggestKeywordsRequest;
use App\Models\Brand;
use App\Models\BrandKeyword;
use App\Models\Workspace;
use App\Services\Ai\Keywords\AiKeywordSuggestionManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Redirect;

class KeywordAiSuggestionController extends Controller
{
    public function __construct(
        private readonly AiKeywordSuggestionManager $suggestionManager,
    ) {}

    public function suggest(SuggestKeywordsRequest $request, Workspace $workspace, Brand $brand): JsonResponse|RedirectResponse
    {
        Gate::authorize('update', $brand);
        abort_unless($brand->workspace_id === $workspace->getKey(), 404);

        $results = $this->suggestionManager->suggest(
            $request->user(),
            $workspace,
            $brand,
            $request->validated(),
        );

        if ($request->wantsJson()) {
            return response()->json($results);
        }

        return Redirect::route('workspaces.brands.keywords.index', [$workspace, $brand])
            ->with('ai_suggestions', $results)
            ->with('status', 'AI keyword suggestions generated successfully.');
    }

    public function import(ImportKeywordSuggestionsRequest $request, Workspace $workspace, Brand $brand): RedirectResponse
    {
        Gate::authorize('update', $brand);
        abort_unless($brand->workspace_id === $workspace->getKey(), 404);

        $validated = $request->validated();
        $importedCount = 0;
        $user = $request->user();

        DB::transaction(function () use ($brand, $workspace, $user, $validated, &$importedCount) {
            foreach ($validated['clusters'] as $clusterInput) {
                $clusterName = trim($clusterInput['cluster_name']);
                if (empty($clusterName)) {
                    continue;
                }

                $cluster = $brand->topicClusters()->firstOrCreate(
                    ['name' => $clusterName],
                    [
                        'workspace_id' => $workspace->getKey(),
                        'created_by_user_id' => $user->getKey(),
                        'pillar_keyword' => $clusterInput['pillar_keyword'] ?? null,
                        'description' => $clusterInput['description'] ?? null,
                    ]
                );

                if (isset($clusterInput['keywords']) && is_array($clusterInput['keywords'])) {
                    foreach ($clusterInput['keywords'] as $kwInput) {
                        $kwText = trim($kwInput['keyword']);
                        $normalized = BrandKeyword::normalize($kwText);

                        if ($kwText === '') {
                            continue;
                        }

                        // Skip duplicate keywords
                        if ($brand->keywords()->where('normalized_keyword', $normalized)->exists()) {
                            continue;
                        }

                        $brand->keywords()->create([
                            'workspace_id' => $workspace->getKey(),
                            'brand_topic_cluster_id' => $cluster->getKey(),
                            'created_by_user_id' => $user->getKey(),
                            'keyword' => $kwText,
                            'normalized_keyword' => $normalized,
                            'search_intent' => $kwInput['search_intent'] ?? 'informational',
                            'search_volume' => null,
                            'difficulty_score' => null,
                            'is_starred' => false,
                        ]);

                        $importedCount++;
                    }
                }
            }
        });

        return Redirect::route('workspaces.brands.keywords.index', [$workspace, $brand])
            ->with('status', "Imported {$importedCount} keywords into brand strategy.");
    }
}
