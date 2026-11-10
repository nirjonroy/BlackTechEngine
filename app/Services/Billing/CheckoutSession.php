<?php

namespace App\Services\Billing;

use Carbon\CarbonInterface;

final readonly class CheckoutSession
{
    public function __construct(
        public string $provider,
        public string $providerReference,
        public string $checkoutUrl,
        public ?CarbonInterface $expiresAt = null,
    ) {}
}
