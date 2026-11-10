<?php

namespace App\Services\Billing;

use App\Models\BillingInvoice;
use App\Services\Billing\Contracts\PaymentGateway;
use LogicException;

class UnavailablePaymentGateway implements PaymentGateway
{
    public function name(): string
    {
        return 'unavailable';
    }

    public function isConfigured(): bool
    {
        return false;
    }

    public function initiateCheckout(
        BillingInvoice $invoice,
        string $idempotencyKey,
        string $returnUrl,
        string $cancelUrl,
    ): CheckoutSession {
        throw new LogicException('No payment gateway is configured.');
    }

    public function verifyWebhook(string $payload, array $headers): PaymentEvent
    {
        throw new LogicException('No payment gateway is configured.');
    }

    public function isTrustedCheckoutUrl(string $url): bool
    {
        return false;
    }
}
