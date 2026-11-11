<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBrandTopicClusterRequest;
use App\Models\Brand;
use App\Models\BrandTopicCluster;
use App\Models\Workspace;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Validation\ValidationException;

class KeywordClusterController extends Controller
{
    public function store(StoreBrandTopicClusterRequest $request, Workspace $workspace, Brand $brand): RedirectResponse
    {
        Gate::authorize('update', $brand);
        abort_unless($brand->workspace_id === $workspace->getKey(), 404);

        $name = trim($request->string('name')->toString());
        $exists = $brand->topicClusters()->where('name', $name)->exists();

        if ($exists) {
            throw ValidationException::withMessages([
                'name' => 'A topic cluster with this name already exists for this brand.',
            ]);
        }

        $brand->topicClusters()->create([
            'workspace_id' => $workspace->getKey(),
            'created_by_user_id' => $request->user()->getKey(),
            'name' => $name,
            'pillar_keyword' => $request->string('pillar_keyword')->toString() ?: null,
            'description' => $request->string('description')->toString() ?: null,
        ]);

        return Redirect::route('workspaces.brands.keywords.index', [$workspace, $brand])
            ->with('status', 'Topic cluster created successfully.');
    }

    public function destroy(Workspace $workspace, Brand $brand, BrandTopicCluster $brandTopicCluster): RedirectResponse
    {
        Gate::authorize('update', $brand);
        abort_unless(
            $brand->workspace_id === $workspace->getKey()
            && $brandTopicCluster->workspace_id === $workspace->getKey()
            && $brandTopicCluster->brand_id === $brand->getKey(),
            404
        );

        $brandTopicCluster->delete();

        return Redirect::route('workspaces.brands.keywords.index', [$workspace, $brand])
            ->with('status', 'Topic cluster removed. Associated keywords were detached.');
    }
}
