<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateSiteSettingRequest;
use App\Models\SiteSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;

class SiteSettingController extends Controller
{
    public function index()
    {
        $setting = SiteSetting::first();

        return view('admin.settings.index', compact('setting'));
    }

    public function update(UpdateSiteSettingRequest $request): RedirectResponse
    {
        $setting = SiteSetting::first() ?? new SiteSetting;
        $setting->fill($request->safe()->except(['logo_dark', 'logo_light', 'favicon']));

        $replacedImages = [];
        foreach (['logo_dark', 'logo_light', 'favicon'] as $field) {
            if (! $request->hasFile($field)) {
                continue;
            }

            $replacedImages[] = [$setting->{$field}, 'settings'];
            $setting->{$field} = $request->file($field)->store('settings', 'public');
        }

        $setting->save();

        foreach ($replacedImages as [$path, $directory]) {
            $this->deleteStoredImage($path, $directory);
        }

        return back()->with('success', 'Site settings updated successfully!');
    }

    private function deleteStoredImage(?string $path, string $directory): void
    {
        if ($path && str_starts_with($path, $directory.'/') && ! str_contains($path, '..')) {
            Storage::disk('public')->delete($path);
        }
    }
}
