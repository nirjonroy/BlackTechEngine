<?php

namespace Database\Factories;

use App\Enums\EntitlementKey;
use App\Models\UsageCounter;
use App\Models\WorkspaceSubscription;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<UsageCounter>
 */
class UsageCounterFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'workspace_subscription_id' => WorkspaceSubscription::factory(),
            'workspace_id' => fn (array $attributes): int => WorkspaceSubscription::query()->findOrFail($attributes['workspace_subscription_id'])->workspace_id,
            'entitlement_key' => EntitlementKey::ContentDraftsPerPeriod->value,
            'period_starts_at' => now()->startOfMonth(),
            'period_ends_at' => now()->startOfMonth()->addMonth(),
            'used' => 0,
            'reserved' => 0,
            'manual_adjustment' => 0,
            'lock_version' => 0,
        ];
    }
}
