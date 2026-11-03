<?php

namespace Database\Factories;

use App\Models\BillingInvoice;
use App\Models\BillingInvoiceItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BillingInvoiceItem>
 */
class BillingInvoiceItemFactory extends Factory
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
            'description' => 'Monthly subscription',
            'quantity' => 1,
            'unit_amount_minor' => 1900,
            'total_amount_minor' => 1900,
            'metadata' => null,
        ];
    }
}
