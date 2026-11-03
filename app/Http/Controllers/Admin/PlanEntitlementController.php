<?php

namespace App\Http\Controllers\Admin;

use App\Enums\EntitlementKey;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdatePlanEntitlementsRequest;
use App\Models\PricingPlan;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;

class PlanEntitlementController extends Controller
{
    public function edit(PricingPlan $pricingPlan): View
    {
        $pricingPlan->load('entitlements');

        return view('admin.subscriptions.plan-entitlements', [
            'plan' => $pricingPlan,
            'keys' => EntitlementKey::cases(),
            'configured' => $pricingPlan->entitlements->keyBy('key'),
        ]);
    }

    public function update(UpdatePlanEntitlementsRequest $request, PricingPlan $pricingPlan): RedirectResponse
    {
        DB::transaction(function () use ($pricingPlan, $request): void {
            $locked = PricingPlan::query()->lockForUpdate()->findOrFail($pricingPlan->getKey());
            $locked->update([
                ...Arr::only($request->validated(), [
                    'code', 'billing_currency', 'monthly_price_minor', 'annual_price_minor', 'trial_days', 'is_billable',
                ]),
                'billing_version' => $locked->billing_version + 1,
            ]);

            foreach ($request->entitlements() as $key => $entitlement) {
                $locked->entitlements()->updateOrCreate(['key' => $key], [
                    'is_enabled' => $entitlement['enabled'],
                    'limit' => $entitlement['unlimited'] ? null : $entitlement['limit'],
                    'is_unlimited' => $entitlement['unlimited'],
                    'unit' => $entitlement['unit'],
                ]);
            }
        }, 3);

        return Redirect::route('admin.pricing.entitlements.edit', $pricingPlan)
            ->with('success', 'Billable plan settings saved. Existing subscription snapshots were not changed.');
    }
}
