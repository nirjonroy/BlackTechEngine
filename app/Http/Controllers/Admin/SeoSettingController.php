<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SaveSeoSettingRequest;
use App\Models\SeoSetting;
use Illuminate\Http\RedirectResponse;
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

    public function store(SaveSeoSettingRequest $request): RedirectResponse
    {
        $seo = new SeoSetting;
        $seo->fill($request->safe()->except('meta_image'));

        if ($request->hasFile('meta_image')) {
            $seo->meta_image = $request->file('meta_image')->store('seo', 'public');
        }

        $seo->save();

        return redirect()->route('admin.seo.index')->with('success', 'SEO Setting created successfully!');
    }

    public function edit(string $id)
    {
        $seo = SeoSetting::findOrFail($id);

        return view('admin.seo_settings.edit', compact('seo'));
    }

    public function update(SaveSeoSettingRequest $request, string $id): RedirectResponse
    {
        $seo = SeoSetting::findOrFail($id);
        $previousMetaImage = $seo->meta_image;
        $seo->fill($request->safe()->except('meta_image'));

        if ($request->hasFile('meta_image')) {
            $seo->meta_image = $request->file('meta_image')->store('seo', 'public');
        }

        $seo->save();

        if ($request->hasFile('meta_image')) {
            $this->deleteStoredImage($previousMetaImage);
        }

        return redirect()->route('admin.seo.index')->with('success', 'SEO Setting updated successfully!');
    }

    public function destroy(SeoSetting $seo): RedirectResponse
    {
        $metaImage = $seo->meta_image;

        $seo->delete();
        $this->deleteStoredImage($metaImage);

        return redirect()->route('admin.seo.index')->with('success', 'SEO Setting deleted successfully!');
    }

    private function deleteStoredImage(?string $path): void
    {
        if ($path && str_starts_with($path, 'seo/') && ! str_contains($path, '..')) {
            Storage::disk('public')->delete($path);
        }
    }
}
