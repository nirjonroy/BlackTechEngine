<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\PricingPageSetting;
use Illuminate\Support\Facades\Storage;

class PricingPageSettingController extends Controller
{
    public function index()
    {
        $setting = PricingPageSetting::first() ?? new PricingPageSetting();
        return view('admin.pricing_page_settings.index', compact('setting'));
    }

    public function update(Request $request)
    {
        $setting = PricingPageSetting::first() ?? new PricingPageSetting();
        $setting->fill($request->except(['_token', 'meta_image']));

        if ($request->hasFile('meta_image')) {
            if ($setting->meta_image) {
                Storage::disk('public')->delete($setting->meta_image);
            }
            $setting->meta_image = $request->file('meta_image')->store('seo', 'public');
        }

        $setting->save();
        return back()->with('success', 'Pricing Page Settings updated successfully!');
    }
}