<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SavePricingPlanRequest;
use App\Models\PricingPlan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Arr;
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

    public function store(SavePricingPlanRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $features = $validated['features'] ?? [];
        $planData = Arr::except($validated, ['features', 'meta_image']);

        if ($request->hasFile('meta_image')) {
            $planData['meta_image'] = $request->file('meta_image')->store('seo', 'public');
        }

        DB::transaction(function () use ($planData, $features): void {
            $plan = PricingPlan::create($planData);

            foreach ($features as $index => $feature) {
                $plan->features()->create([
                    'name' => $feature['name'],
                    'is_included' => $feature['is_included'],
                    'order_by' => $index,
                ]);
            }
        });

        return redirect()->route('admin.pricing.index')->with('success', 'Plan created successfully!');
    }

    public function edit(string $id)
    {
        $plan = PricingPlan::findOrFail($id);
        $plan->load('features');

        return view('admin.pricing.edit', compact('plan'));
    }

    public function update(SavePricingPlanRequest $request, string $id): RedirectResponse
    {
        $plan = PricingPlan::findOrFail($id);
        $validated = $request->validated();
        $features = $validated['features'] ?? [];
        $planData = Arr::except($validated, ['features', 'meta_image']);
        $previousMetaImage = $plan->meta_image;

        if ($request->hasFile('meta_image')) {
            $planData['meta_image'] = $request->file('meta_image')->store('seo', 'public');
        }

        DB::transaction(function () use ($plan, $planData, $features): void {
            $plan->update($planData);
            $plan->features()->delete();

            foreach ($features as $index => $feature) {
                $plan->features()->create([
                    'name' => $feature['name'],
                    'is_included' => $feature['is_included'],
                    'order_by' => $index,
                ]);
            }
        });

        if ($request->hasFile('meta_image')) {
            $this->deleteStoredImage($previousMetaImage);
        }

        return redirect()->route('admin.pricing.index')->with('success', 'Plan updated successfully!');
    }

    public function destroy(PricingPlan $plan): RedirectResponse
    {
        $metaImage = $plan->meta_image;

        DB::transaction(function () use ($plan): void {
            $plan->delete();
        });

        $this->deleteStoredImage($metaImage);

        return redirect()->route('admin.pricing.index')->with('success', 'Plan deleted successfully!');
    }

    private function deleteStoredImage(?string $path): void
    {
        if ($path && str_starts_with($path, 'seo/') && ! str_contains($path, '..')) {
            Storage::disk('public')->delete($path);
        }
    }
}
