<?php

namespace Database\Factories;

use App\Enums\PaymentStatus;
use App\Enums\PaymentType;
use App\Enums\ReconciliationStatus;
use App\Models\BillingInvoice;
use App\Models\PaymentTransaction;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PaymentTransaction>
 */
class PaymentTransactionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'billing_invoice_id' => BillingInvoice::factory(),
            'workspace_subscription_id' => fn (array $attributes): int => BillingInvoice::query()->findOrFail($attributes['billing_invoice_id'])->workspace_subscription_id,
            'workspace_id' => fn (array $attributes): int => BillingInvoice::query()->findOrFail($attributes['billing_invoice_id'])->workspace_id,
            'provider' => 'local_test',
            'provider_reference' => fake()->unique()->uuid(),
            'provider_event_id' => fake()->unique()->uuid(),
            'idempotency_key' => fake()->unique()->uuid(),
            'type' => PaymentType::Charge,
            'status' => PaymentStatus::Pending,
            'reconciliation_status' => ReconciliationStatus::Unreconciled,
            'currency' => 'USD',
            'amount_minor' => 1900,
        ];
    }
}
