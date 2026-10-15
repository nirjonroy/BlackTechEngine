<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\SiteSetting;
use Illuminate\Support\Facades\Storage;

class SiteSettingController extends Controller
{
    public function index()
    {
        $setting = SiteSetting::first(); // Prothom row ti nibe
        return view('admin.settings.index', compact('setting'));
    }

    public function update(Request $request)
    {
        $setting = SiteSetting::first() ?? new SiteSetting();

        $setting->site_name = $request->site_name;
        $setting->email = $request->email;
        $setting->mobile = $request->mobile;
        $setting->address = $request->address;
        $setting->facebook_url = $request->facebook_url;

        // Image Upload Logic
        if ($request->hasFile('logo_dark')) {
            if ($setting->logo_dark) { Storage::disk('public')->delete($setting->logo_dark); }
            $setting->logo_dark = $request->file('logo_dark')->store('settings', 'public');
        }
        if ($request->hasFile('logo_light')) {
            if ($setting->logo_light) { Storage::disk('public')->delete($setting->logo_light); }
            $setting->logo_light = $request->file('logo_light')->store('settings', 'public');
        }
        if ($request->hasFile('favicon')) {
            if ($setting->favicon) { Storage::disk('public')->delete($setting->favicon); }
            $setting->favicon = $request->file('favicon')->store('settings', 'public');
        }

        $setting->save();

        return back()->with('success', 'Site settings updated successfully!');
    }
}
