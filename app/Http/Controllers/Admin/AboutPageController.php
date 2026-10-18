<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateAboutPageRequest;
use App\Models\AboutPage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class AboutPageController extends Controller
{
    public function index()
    {
        $about = AboutPage::with(['stats', 'comparisons', 'architectures', 'coreValues'])->first() ?? new AboutPage;

        return view('admin.about.index', compact('about'));
    }

    public function update(UpdateAboutPageRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        DB::transaction(function () use ($validated): void {
            $about = AboutPage::first() ?? new AboutPage;
            $about->fill(Arr::except($validated, ['stats', 'comparisons', 'architectures', 'core_values']));
            $about->save();

            $about->stats()->delete();
            foreach ($validated['stats'] ?? [] as $index => $stat) {
                if (! empty($stat['value'])) {
                    $about->stats()->create([...$stat, 'order_by' => $index]);
                }
            }

            $about->comparisons()->delete();
            foreach ($validated['comparisons'] ?? [] as $index => $comparison) {
                if (! empty($comparison['capability'])) {
                    $about->comparisons()->create([...$comparison, 'order_by' => $index]);
                }
            }

            $about->architectures()->delete();
            foreach ($validated['architectures'] ?? [] as $index => $architecture) {
                if (! empty($architecture['title'])) {
                    $about->architectures()->create([...$architecture, 'order_by' => $index]);
                }
            }

            $about->coreValues()->delete();
            foreach ($validated['core_values'] ?? [] as $index => $coreValue) {
                if (! empty($coreValue['title'])) {
                    $about->coreValues()->create([...$coreValue, 'order_by' => $index]);
                }
            }
        });

        return back()->with('success', 'About Page settings updated successfully!');
    }
}
