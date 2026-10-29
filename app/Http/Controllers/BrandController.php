<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBrandRequest;
use App\Http\Requests\UpdateBrandRequest;
use App\Models\Brand;
use App\Models\Workspace;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Redirect;

class BrandController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Workspace $workspace): View
    {
        Gate::authorize('view', $workspace);

        $brands = $workspace->brands()->orderBy('name')->get();

        return view('brands.index', compact('workspace', 'brands'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Workspace $workspace): View
    {
        Gate::authorize('update', $workspace);

        return view('brands.create', [
            'workspace' => $workspace,
            'onboarding' => request()->routeIs('onboarding.brand.create'),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreBrandRequest $request, Workspace $workspace): RedirectResponse
    {
        $brand = $workspace->brands()->create($request->brandData());

        if ($request->routeIs('onboarding.brand.store')) {
            return Redirect::route('onboarding.confirm', [$workspace, $brand]);
        }

        return Redirect::route('workspaces.brands.show', [$workspace, $brand])
            ->with('status', 'Brand profile created.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Workspace $workspace, Brand $brand): View
    {
        Gate::authorize('view', $brand);

        return view('brands.show', compact('workspace', 'brand'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Workspace $workspace, Brand $brand): View
    {
        Gate::authorize('update', $brand);

        return view('brands.edit', compact('workspace', 'brand'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateBrandRequest $request, Workspace $workspace, Brand $brand): RedirectResponse
    {
        $brand->update($request->brandData());

        return Redirect::route('workspaces.brands.show', [$workspace, $brand])
            ->with('status', 'Brand profile updated.');
    }
}
