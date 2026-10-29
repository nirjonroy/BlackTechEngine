<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreWorkspaceRequest;
use App\Http\Requests\UpdateWorkspaceRequest;
use App\Models\Workspace;
use DateTimeZone;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Redirect;

class WorkspaceController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): View
    {
        $workspaces = $request->user()->accessibleWorkspaces()
            ->withCount('brands')
            ->orderBy('name')
            ->get();

        return view('workspaces.index', compact('workspaces'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        Gate::authorize('create', Workspace::class);

        return view('workspaces.create', [
            'timezones' => DateTimeZone::listIdentifiers(),
            'onboarding' => request()->routeIs('onboarding.workspace.create'),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreWorkspaceRequest $request): RedirectResponse
    {
        $workspace = DB::transaction(function () use ($request): Workspace {
            $user = $request->user();
            $workspace = $user->ownedWorkspaces()->create($request->workspaceData());
            $workspace->members()->attach($user, ['role' => 'owner']);
            $user->active_workspace_id = $workspace->getKey();
            $user->save();

            return $workspace;
        });

        if ($request->routeIs('onboarding.workspace.store')) {
            return Redirect::route('onboarding.brand.create', $workspace);
        }

        return Redirect::route('workspaces.index')->with('status', 'Workspace created.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Workspace $workspace): RedirectResponse
    {
        Gate::authorize('view', $workspace);

        return Redirect::route('workspaces.brands.index', $workspace);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Workspace $workspace): View
    {
        Gate::authorize('update', $workspace);

        return view('workspaces.edit', [
            'workspace' => $workspace,
            'timezones' => DateTimeZone::listIdentifiers(),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateWorkspaceRequest $request, Workspace $workspace): RedirectResponse
    {
        $workspace->update($request->workspaceData());

        return Redirect::route('workspaces.index')->with('status', 'Workspace settings updated.');
    }
}
