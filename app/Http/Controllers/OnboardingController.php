<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\Workspace;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Redirect;

class OnboardingController extends Controller
{
    public function start(Request $request): RedirectResponse
    {
        $workspace = $request->user()->accessibleWorkspaces()->oldest()->first();

        if (! $workspace instanceof Workspace) {
            return Redirect::route('onboarding.workspace.create');
        }

        $brand = $workspace->brands()->oldest()->first();

        if (! $brand instanceof Brand) {
            return Redirect::route('onboarding.brand.create', $workspace);
        }

        return Redirect::route('onboarding.confirm', [$workspace, $brand]);
    }

    public function confirm(Workspace $workspace, Brand $brand): View
    {
        Gate::authorize('view', $brand);

        return view('onboarding.confirm', compact('workspace', 'brand'));
    }

    public function complete(Request $request, Workspace $workspace, Brand $brand): RedirectResponse
    {
        Gate::authorize('view', $brand);

        $user = $request->user();
        $user->active_workspace_id = $workspace->getKey();
        $user->save();

        return Redirect::route('dashboard')->with('status', 'Your workspace and brand are ready.');
    }
}
