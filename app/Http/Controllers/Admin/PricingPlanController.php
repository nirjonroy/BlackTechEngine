<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PricingPlan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class PricingPlanController extends Controller
{
    public function index()
    {
        $plans = PricingPlan::orderBy('monthly_price', 'asc')->get();

        return view('admin.pricing.index', compact('plans'));
    }

    public function create()
    {
        return view('admin.pricing.create');
    }

    public function store(Request $request)
    {
        $request->validate(['name' => 'required|string']);

        $plan = new PricingPlan;
        $plan->fill($request->except(['_token', 'meta_image', 'features']));
        $plan->is_popular = $request->has('is_popular');

        if ($request->hasFile('meta_image')) {
            $plan->meta_image = $request->file('meta_image')->store('seo', 'public');
        }
        $plan->save();

        // Features insert kora
        if ($request->has('features')) {
            foreach ($request->features as $index => $feature) {
                if (! empty($feature['name'])) {
                    $plan->features()->create([
                        'name' => $feature['name'],
                        'is_included' => isset($feature['is_included']) ? 1 : 0,
                        'order_by' => $index,
                    ]);
                }
            }
        }

        return redirect()->route('admin.pricing.index')->with('success', 'Plan created successfully!');
    }

    public function edit($id)
    {
        $plan = PricingPlan::with('features')->findOrFail($id);

        return view('admin.pricing.edit', compact('plan'));
    }

    public function update(Request $request, $id)
    {
        $request->validate(['name' => 'required|string']);

        $plan = PricingPlan::findOrFail($id);
        $plan->fill($request->except(['_token', 'meta_image', 'features']));
        $plan->is_popular = $request->has('is_popular');

        if ($request->hasFile('meta_image')) {
            if ($plan->meta_image) {
                Storage::disk('public')->delete($plan->meta_image);
            }
            $plan->meta_image = $request->file('meta_image')->store('seo', 'public');
        }
        $plan->save();

        // Purano feature delete kore notun gulo insert kora
        $plan->features()->delete();
        if ($request->has('features')) {
            foreach ($request->features as $index => $feature) {
                if (! empty($feature['name'])) {
                    $plan->features()->create([
                        'name' => $feature['name'],
                        'is_included' => isset($feature['is_included']) ? 1 : 0,
                        'order_by' => $index,
                    ]);
                }
            }
        }

        return redirect()->route('admin.pricing.index')->with('success', 'Plan updated successfully!');
    }

    public function destroy(PricingPlan $plan): RedirectResponse
    {
        $metaImage = $plan->meta_image;

        DB::transaction(function () use ($plan): void {
            $plan->delete();
        });

        if ($metaImage) {
            Storage::disk('public')->delete($metaImage);
        }

        return redirect()->route('admin.pricing.index')->with('success', 'Plan deleted successfully!');
    }
}
