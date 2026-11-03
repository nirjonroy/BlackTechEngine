<?php

namespace Database\Factories;

use App\Enums\EntitlementKey;
use App\Models\PricingPlan;
use App\Models\PricingPlanEntitlement;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PricingPlanEntitlement>
 */
class PricingPlanEntitlementFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'pricing_plan_id' => PricingPlan::factory(),
            'key' => EntitlementKey::ContentDraftsPerPeriod->value,
            'is_enabled' => true,
            'limit' => 10,
            'is_unlimited' => false,
            'unit' => 'items',
            'metadata' => null,
        ];
    }
}
