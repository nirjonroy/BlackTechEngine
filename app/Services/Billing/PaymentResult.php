<?php

namespace App\Services\Billing;

use App\Enums\PaymentStatus;

final readonly class PaymentResult
{
    public function __construct(
        public string $provider,
        public string $providerReference,
        public ?string $providerEventId,
        public PaymentStatus $status,
        public int $amountMinor,
        public string $currency,
        public ?string $failureCode = null,
    ) {}
}
