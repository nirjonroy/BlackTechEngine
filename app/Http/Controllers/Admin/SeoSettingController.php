<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\SeoSetting;
use Illuminate\Support\Facades\Storage;

class SeoSettingController extends Controller
{
    public function index()
    {
        $seos = SeoSetting::all();
        return view('admin.seo_settings.index', compact('seos'));
    }

    public function create()
    {
        return view('admin.seo_settings.create');
    }

    public function store(Request $request)
    {
        $seo = new SeoSetting();
        $seo->fill($request->except(['_token', 'meta_image']));

        if ($request->hasFile('meta_image')) {
            $seo->meta_image = $request->file('meta_image')->store('seo', 'public');
        }

        $seo->save();
        return redirect()->route('admin.seo.index')->with('success', 'SEO Setting created successfully!');
    }

    public function edit($id)
    {
        $seo = SeoSetting::findOrFail($id);
        return view('admin.seo_settings.edit', compact('seo'));
    }

    public function update(Request $request, $id)
    {
        $seo = SeoSetting::findOrFail($id);
        $seo->fill($request->except(['_token', 'meta_image']));

        if ($request->hasFile('meta_image')) {
            if ($seo->meta_image) {
                Storage::disk('public')->delete($seo->meta_image);
            }
            $seo->meta_image = $request->file('meta_image')->store('seo', 'public');
        }

        $seo->save();
        return redirect()->route('admin.seo.index')->with('success', 'SEO Setting updated successfully!');
    }

    public function destroy($id)
    {
        $seo = SeoSetting::findOrFail($id);
        if ($seo->meta_image) {
            Storage::disk('public')->delete($seo->meta_image);
        }
        $seo->delete();
        return redirect()->route('admin.seo.index')->with('success', 'SEO Setting deleted successfully!');
    }
}