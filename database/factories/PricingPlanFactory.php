<?php

namespace Database\Factories;

use App\Models\PricingPlan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PricingPlan>
 */
class PricingPlanFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->words(2, true),
            'code' => fake()->unique()->slug(2),
            'monthly_price' => '19.00',
            'annual_price' => '190.00',
            'billing_currency' => 'USD',
            'monthly_price_minor' => 1900,
            'annual_price_minor' => 19000,
            'billing_version' => 1,
            'trial_days' => 0,
            'is_active' => true,
            'show_on_pricing_page' => true,
            'is_billable' => true,
        ];
    }
}
