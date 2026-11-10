<?php

namespace Database\Factories;

use App\Enums\PaymentStatus;
use App\Models\PaymentTransaction;
use App\Models\PaymentWebhookEvent;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<PaymentWebhookEvent> */
class PaymentWebhookEventFactory extends Factory
{
    public function definition(): array
    {
        return [
            'payment_transaction_id' => PaymentTransaction::factory(),
            'provider' => fn (array $attributes): string => PaymentTransaction::query()
                ->findOrFail($attributes['payment_transaction_id'])->provider,
            'provider_event_id' => fake()->unique()->uuid(),
            'provider_reference' => fn (array $attributes): string => (string) PaymentTransaction::query()
                ->findOrFail($attributes['payment_transaction_id'])->provider_reference,
            'event_type' => 'charge.succeeded',
            'payment_status' => PaymentStatus::Succeeded,
            'processing_status' => 'processed',
            'payload_hash' => hash('sha256', fake()->uuid()),
            'processed_at' => now('UTC'),
        ];
    }
}
