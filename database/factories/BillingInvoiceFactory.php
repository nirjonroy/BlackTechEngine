<?php

namespace Database\Factories;

use App\Enums\InvoiceStatus;
use App\Models\BillingInvoice;
use App\Models\WorkspaceSubscription;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BillingInvoice>
 */
class BillingInvoiceFactory extends Factory
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
            'number' => 'INV-'.fake()->unique()->numerify('########'),
            'status' => InvoiceStatus::Open,
            'currency' => 'USD',
            'subtotal_minor' => 1900,
            'discount_minor' => 0,
            'tax_minor' => 0,
            'total_minor' => 1900,
            'refunded_minor' => 0,
            'period_starts_at' => now()->startOfMonth(),
            'period_ends_at' => now()->startOfMonth()->addMonth(),
            'due_at' => now()->addDays(7),
        ];
    }
}
