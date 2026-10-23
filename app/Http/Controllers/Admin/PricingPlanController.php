<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SavePricingPlanRequest;
use App\Models\PricingPageSetting;
use App\Models\PricingPlan;
use App\Services\PricingPresentation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class PricingPlanController extends Controller
{
    public function index(PricingPresentation $presentation)
    {
        $plans = PricingPlan::query()->inDisplayOrder()->get();
        $currencyCode = $presentation->currencyCode(PricingPageSetting::query()->value('currency_code'));

        return view('admin.pricing.index', [
            'plans' => $plans,
            'currencyCode' => $currencyCode,
            'formattedPlanPrices' => $plans->mapWithKeys(fn (PricingPlan $plan): array => [
                $plan->getKey() => [
                    'monthly' => $presentation->format($plan->monthly_price, $currencyCode),
                    'annual' => $presentation->format($plan->annual_price, $currencyCode),
                ],
            ]),
        ]);
    }

    public function create()
    {
        return view('admin.pricing.create', [
            'plan' => new PricingPlan,
            'destinationOptions' => PricingPresentation::DESTINATION_OPTIONS,
        ]);
    }

    public function store(SavePricingPlanRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $features = $validated['features'] ?? [];
        $replaceFeatures = $request->has('features') || $request->boolean('features_present');
        $planData = Arr::except($validated, ['features', 'features_present', 'meta_image']);

        if ($request->hasFile('meta_image')) {
            $planData['meta_image'] = $request->file('meta_image')->store('seo', 'public');
        }

        DB::transaction(function () use ($planData, $features, $replaceFeatures): void {
            $plan = PricingPlan::create($planData);

            if (! $replaceFeatures) {
                return;
            }

            foreach ($features as $index => $feature) {
                $plan->features()->create([
                    'name' => $feature['name'],
                    'is_included' => $feature['is_included'],
                    'order_by' => $feature['order_by'] ?? $index,
                ]);
            }
        });

        return redirect()->route('admin.pricing.index')->with('success', 'Plan created successfully!');
    }

    public function edit(string $id)
    {
        $plan = PricingPlan::findOrFail($id);
        $plan->load('features');

        return view('admin.pricing.edit', [
            'plan' => $plan,
            'destinationOptions' => PricingPresentation::DESTINATION_OPTIONS,
        ]);
    }

    public function update(SavePricingPlanRequest $request, string $id): RedirectResponse
    {
        $plan = PricingPlan::findOrFail($id);
        $validated = $request->validated();
        $features = $validated['features'] ?? [];
        $replaceFeatures = $request->has('features') || $request->boolean('features_present');
        $planData = Arr::except($validated, ['features', 'features_present', 'meta_image']);
        $previousMetaImage = $plan->meta_image;

        if ($request->hasFile('meta_image')) {
            $planData['meta_image'] = $request->file('meta_image')->store('seo', 'public');
        }

        DB::transaction(function () use ($plan, $planData, $features, $replaceFeatures): void {
            $plan->update($planData);

            if (! $replaceFeatures) {
                return;
            }

            $plan->features()->delete();

            foreach ($features as $index => $feature) {
                $plan->features()->create([
                    'name' => $feature['name'],
                    'is_included' => $feature['is_included'],
                    'order_by' => $feature['order_by'] ?? $index,
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
