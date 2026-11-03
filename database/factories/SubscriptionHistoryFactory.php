<?php

namespace Database\Factories;

use App\Enums\SubscriptionStatus;
use App\Models\SubscriptionHistory;
use App\Models\WorkspaceSubscription;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SubscriptionHistory>
 */
class SubscriptionHistoryFactory extends Factory
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
            'action' => 'created',
            'from_status' => null,
            'to_status' => SubscriptionStatus::Active->value,
            'lock_version' => 0,
            'context' => [],
        ];
    }
}
