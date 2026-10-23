<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateFeaturePageSettingRequest;
use App\Models\FeaturePageSetting;
use App\Services\FeaturePageContent;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;

class FeaturePageSettingController extends Controller
{
    public function index(FeaturePageContent $content)
    {
        $setting = FeaturePageSetting::first() ?? new FeaturePageSetting;

        return view('admin.feature_page_settings.index', [
            'setting' => $setting,
            'features' => old('features_json') ?? $content->features($setting->features_json)->all(),
            'iconOptions' => FeaturePageContent::ICON_OPTIONS,
            'destinationOptions' => FeaturePageContent::DESTINATION_OPTIONS,
        ]);
    }

    public function update(UpdateFeaturePageSettingRequest $request, FeaturePageContent $content): RedirectResponse
    {
        $setting = FeaturePageSetting::first() ?? new FeaturePageSetting;
        $previousMetaImage = $setting->meta_image;
        $validated = $request->safe()->except('meta_image');
        $validated['features_json'] = $content->features($validated['features_json'] ?? [])->all();
        $setting->fill($validated);

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
