<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdatePricingPageSettingRequest;
use App\Models\PricingPageSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;

class PricingPageSettingController extends Controller
{
    public function index()
    {
        $setting = PricingPageSetting::first() ?? new PricingPageSetting;

        return view('admin.pricing_page_settings.index', compact('setting'));
    }

    public function update(UpdatePricingPageSettingRequest $request): RedirectResponse
    {
        $setting = PricingPageSetting::first() ?? new PricingPageSetting;
        $previousMetaImage = $setting->meta_image;
        $setting->fill($request->safe()->except('meta_image'));

        if ($request->hasFile('meta_image')) {
            $setting->meta_image = $request->file('meta_image')->store('seo', 'public');
        }

        $setting->save();

        if ($request->hasFile('meta_image')) {
            $this->deleteStoredImage($previousMetaImage);
        }

        return back()->with('success', 'Pricing Page Settings updated successfully!');
    }

    private function deleteStoredImage(?string $path): void
    {
        if ($path && str_starts_with($path, 'seo/') && ! str_contains($path, '..')) {
            Storage::disk('public')->delete($path);
        }
    }
}
