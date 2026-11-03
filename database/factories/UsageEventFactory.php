<?php

namespace Database\Factories;

use App\Enums\EntitlementKey;
use App\Enums\UsageEventStatus;
use App\Models\UsageCounter;
use App\Models\UsageEvent;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<UsageEvent>
 */
class UsageEventFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'usage_counter_id' => UsageCounter::factory(),
            'workspace_subscription_id' => fn (array $attributes): int => UsageCounter::query()->findOrFail($attributes['usage_counter_id'])->workspace_subscription_id,
            'workspace_id' => fn (array $attributes): int => UsageCounter::query()->findOrFail($attributes['usage_counter_id'])->workspace_id,
            'entitlement_key' => EntitlementKey::ContentDraftsPerPeriod->value,
            'idempotency_key' => fake()->unique()->uuid(),
            'quantity' => 1,
            'status' => UsageEventStatus::Reserved,
            'expires_at' => now()->addMinutes(15),
        ];
    }
}
