<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateHomepageSettingRequest;
use App\Models\HomepageSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class HomepageSettingController extends Controller
{
    public function index(): View
    {
        return view('admin.homepage_settings.index', [
            'setting' => HomepageSetting::query()->first() ?? new HomepageSetting,
            'destinationOptions' => HomepageSetting::DESTINATION_OPTIONS,
        ]);
    }

    public function update(UpdateHomepageSettingRequest $request): RedirectResponse
    {
        $setting = HomepageSetting::query()->first() ?? new HomepageSetting;
        $setting->fill($request->validated())->save();

        return back()->with('success', 'Homepage settings updated successfully!');
    }
}
