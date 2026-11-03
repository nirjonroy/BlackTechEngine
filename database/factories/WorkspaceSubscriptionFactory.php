<?php

namespace Database\Factories;

use App\Enums\BillingInterval;
use App\Enums\SubscriptionStatus;
use App\Models\PricingPlan;
use App\Models\Workspace;
use App\Models\WorkspaceSubscription;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WorkspaceSubscription>
 */
class WorkspaceSubscriptionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'workspace_id' => Workspace::factory(),
            'pricing_plan_id' => PricingPlan::factory(),
            'status' => SubscriptionStatus::Active,
            'billing_interval' => BillingInterval::Monthly,
            'currency' => 'USD',
            'price_minor' => 1900,
            'entitlement_snapshot' => ['plan' => ['name' => 'Starter', 'version' => 1], 'entitlements' => []],
            'activation_source' => 'admin_grant',
            'lock_version' => 0,
            'starts_at' => now()->startOfMonth(),
            'current_period_starts_at' => now()->startOfMonth(),
            'current_period_ends_at' => now()->startOfMonth()->addMonth(),
            'renews_at' => now()->startOfMonth()->addMonth(),
            'cancel_at_period_end' => false,
        ];
    }
}
