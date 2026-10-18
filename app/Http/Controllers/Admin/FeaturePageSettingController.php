<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateFeaturePageSettingRequest;
use App\Models\FeaturePageSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;

class FeaturePageSettingController extends Controller
{
    public function index()
    {
        $setting = FeaturePageSetting::first() ?? new FeaturePageSetting;

        return view('admin.feature_page_settings.index', compact('setting'));
    }

    public function update(UpdateFeaturePageSettingRequest $request): RedirectResponse
    {
        $setting = FeaturePageSetting::first() ?? new FeaturePageSetting;
        $previousMetaImage = $setting->meta_image;
        $setting->fill($request->safe()->except('meta_image'));

        if ($request->hasFile('meta_image')) {
            $setting->meta_image = $request->file('meta_image')->store('seo', 'public');
        }

        $setting->save();

        if ($request->hasFile('meta_image')) {
            $this->deleteStoredImage($previousMetaImage);
        }

        return back()->with('success', 'Feature Page Settings updated successfully!');
    }

    private function deleteStoredImage(?string $path): void
    {
        if ($path && str_starts_with($path, 'seo/') && ! str_contains($path, '..')) {
            Storage::disk('public')->delete($path);
        }
    }
}
