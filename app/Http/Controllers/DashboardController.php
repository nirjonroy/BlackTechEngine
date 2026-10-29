<?php

namespace App\Http\Controllers;

use App\Models\Workspace;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
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

        return view('dashboard', compact('workspaces', 'activeWorkspace', 'recentBrands'));
    }
}
