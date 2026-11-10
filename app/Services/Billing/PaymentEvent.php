<?php

namespace App\Services\Billing;

use App\Enums\PaymentStatus;
use App\Enums\PaymentType;

final readonly class PaymentEvent
{
    public function __construct(
        public string $provider,
        public string $providerEventId,
        public string $providerReference,
        public PaymentType $type,
        public PaymentStatus $status,
        public int $amountMinor,
        public string $currency,
        public string $payloadHash,
        public ?string $failureCode = null,
        public ?string $providerPaymentReference = null,
    ) {}
}
