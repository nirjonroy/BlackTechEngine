<?php

namespace App\Services\Billing;

use App\Enums\PaymentStatus;
use App\Models\BillingInvoice;
use App\Services\Billing\Contracts\PaymentGateway;
use LogicException;

class FakePaymentGateway implements PaymentGateway
{
    public function __construct(private readonly PaymentStatus $status = PaymentStatus::Succeeded) {}

    public function charge(BillingInvoice $invoice, string $idempotencyKey): PaymentResult
    {
        if (! app()->environment('testing')) {
            throw new LogicException('The local payment gateway is available only during automated tests.');
        }

        return new PaymentResult(
            provider: 'local_test',
            providerReference: 'test-'.$idempotencyKey,
            providerEventId: 'event-'.$idempotencyKey,
            status: $this->status,
            amountMinor: $invoice->total_minor,
            currency: $invoice->currency,
            failureCode: $this->status === PaymentStatus::Failed ? 'test_decline' : null,
        );
    }
}
