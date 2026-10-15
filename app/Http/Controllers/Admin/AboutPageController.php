<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\AboutPage;

class AboutPageController extends Controller
{
    public function index()
    {
        // Prothom record ti nibe, na thakle empty instance toiri korbe
        $about = AboutPage::with(['stats', 'comparisons', 'architectures', 'coreValues'])->first() ?? new AboutPage();
        return view('admin.about.index', compact('about'));
    }

    public function update(Request $request)
    {
        $about = AboutPage::first() ?? new AboutPage();

        // Main table er data update
        $about->fill($request->only([
            'hero_badge', 'hero_title', 'hero_description',
            'comparison_title', 'comparison_description',
            'architecture_badge', 'architecture_title', 'architecture_description',
            'core_values_title', 'core_values_description',
            'cta_title', 'cta_description', 'cta_btn1_text', 'cta_btn1_link', 'cta_btn2_text', 'cta_btn2_link'
        ]));
        $about->save();

        // Related data update korar process (Purano gulo delete kore notun gulo insert kora)
        
        // 1. Stats Update
        $about->stats()->delete();
        if ($request->has('stats')) {
            foreach ($request->stats as $index => $stat) {
                if (!empty($stat['value'])) {
                    $about->stats()->create(array_merge($stat, ['order_by' => $index]));
                }
            }
        }

        // 2. Comparisons Update
        $about->comparisons()->delete();
        if ($request->has('comparisons')) {
            foreach ($request->comparisons as $index => $comp) {
                if (!empty($comp['capability'])) {
                    $about->comparisons()->create(array_merge($comp, ['order_by' => $index]));
                }
            }
        }

        // 3. Architectures Update
        $about->architectures()->delete();
        if ($request->has('architectures')) {
            foreach ($request->architectures as $index => $arch) {
                if (!empty($arch['title'])) {
                    $about->architectures()->create(array_merge($arch, ['order_by' => $index]));
                }
            }
        }

        // 4. Core Values Update
        $about->coreValues()->delete();
        if ($request->has('core_values')) {
            foreach ($request->core_values as $index => $cv) {
                if (!empty($cv['title'])) {
                    $about->coreValues()->create(array_merge($cv, ['order_by' => $index]));
                }
            }
        }

        return back()->with('success', 'About Page settings updated successfully!');
    }
}