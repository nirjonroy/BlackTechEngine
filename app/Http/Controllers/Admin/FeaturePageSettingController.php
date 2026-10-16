<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\FeaturePageSetting;
use Illuminate\Support\Facades\Storage;

class FeaturePageSettingController extends Controller
{
    public function index()
    {
        $setting = FeaturePageSetting::first() ?? new FeaturePageSetting();
        return view('admin.feature_page_settings.index', compact('setting'));
    }

    public function update(Request $request)
    {
        $setting = FeaturePageSetting::first() ?? new FeaturePageSetting();
        
        $setting->fill($request->except(['_token', 'meta_image']));

        // Meta Image Upload Handling
        if ($request->hasFile('meta_image')) {
            if ($setting->meta_image) {
                Storage::disk('public')->delete($setting->meta_image);
            }
            $setting->meta_image = $request->file('meta_image')->store('seo', 'public');
        }

        $setting->save();

        return back()->with('success', 'Feature Page Settings updated successfully!');
    }
}