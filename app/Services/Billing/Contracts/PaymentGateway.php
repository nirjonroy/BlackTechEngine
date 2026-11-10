<?php

namespace App\Services\Billing\Contracts;

use App\Models\BillingInvoice;
use App\Services\Billing\CheckoutSession;
use App\Services\Billing\PaymentEvent;

interface PaymentGateway
{
    public function name(): string;

    public function isConfigured(): bool;

    public function initiateCheckout(
        BillingInvoice $invoice,
        string $idempotencyKey,
        string $returnUrl,
        string $cancelUrl,
    ): CheckoutSession;

    /** @param array<string, list<string>> $headers */
    public function verifyWebhook(string $payload, array $headers): PaymentEvent;

    public function isTrustedCheckoutUrl(string $url): bool;
}
