<?php

namespace App\Services\Billing;

use App\Models\BillingInvoice;
use App\Services\Billing\Contracts\PaymentGateway;
use LogicException;

class UnavailablePaymentGateway implements PaymentGateway
{
    public function charge(BillingInvoice $invoice, string $idempotencyKey): PaymentResult
    {
        throw new LogicException('No payment gateway is configured.');
    }
}
