<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateFrontendNavigationRequest;
use App\Models\SiteSetting;
use App\Services\FrontendNavigation;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class FrontendNavigationController extends Controller
{
    public function index(FrontendNavigation $navigation): View
    {
        $setting = SiteSetting::query()->first();

        return view('admin.frontend_navigation.index', [
            'setting' => $setting ?? new SiteSetting,
            'configuration' => $navigation->formConfiguration($setting),
            'destinationOptions' => FrontendNavigation::DESTINATION_OPTIONS,
        ]);
    }

    public function update(UpdateFrontendNavigationRequest $request, FrontendNavigation $navigation): RedirectResponse
    {
        $validated = $request->validated();
        $validated['header_navigation'] = $navigation->links($validated['header_navigation'], []);
        $validated['footer_columns'] = $navigation->columns($validated['footer_columns'], []);

        $setting = SiteSetting::query()->first() ?? new SiteSetting;
        $setting->fill($validated)->save();

        return back()->with('success', 'Frontend navigation and footer updated successfully!');
    }
}
