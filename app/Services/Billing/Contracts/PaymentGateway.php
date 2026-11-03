<?php

namespace App\Services\Billing\Contracts;

use App\Models\BillingInvoice;
use App\Services\Billing\PaymentResult;

interface PaymentGateway
{
    public function charge(BillingInvoice $invoice, string $idempotencyKey): PaymentResult;
}
