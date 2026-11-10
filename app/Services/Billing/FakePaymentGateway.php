<?php

namespace App\Services\Billing;

use App\Enums\PaymentStatus;
use App\Enums\PaymentType;
use App\Models\BillingInvoice;
use App\Services\Billing\Contracts\PaymentGateway;
use LogicException;
use UnexpectedValueException;

class FakePaymentGateway implements PaymentGateway
{
    public function __construct(private readonly PaymentStatus $status = PaymentStatus::Succeeded) {}

    public function name(): string
    {
        return 'local_test';
    }

    public function isConfigured(): bool
    {
        return app()->environment('testing');
    }

    public function initiateCheckout(
        BillingInvoice $invoice,
        string $idempotencyKey,
        string $returnUrl,
        string $cancelUrl,
    ): CheckoutSession {
        $this->ensureTesting();

        return new CheckoutSession(
            provider: $this->name(),
            providerReference: 'checkout-'.$idempotencyKey,
            checkoutUrl: 'https://gateway.example.test/checkout/'.$idempotencyKey,
            expiresAt: now('UTC')->addMinutes(30),
        );
    }

    public function verifyWebhook(string $payload, array $headers): PaymentEvent
    {
        $this->ensureTesting();

        if (! hash_equals('test-signature', $headers['x-test-signature'][0] ?? '')) {
            throw new UnexpectedValueException('The payment webhook signature is invalid.');
        }

        $data = json_decode($payload, true, flags: JSON_THROW_ON_ERROR);

        if (! is_array($data)) {
            throw new UnexpectedValueException('The payment webhook payload is invalid.');
        }

        return new PaymentEvent(
            provider: $this->name(),
            providerEventId: (string) ($data['event_id'] ?? ''),
            providerReference: (string) ($data['provider_reference'] ?? ''),
            type: PaymentType::from((string) ($data['type'] ?? PaymentType::Charge->value)),
            status: PaymentStatus::from((string) ($data['status'] ?? $this->status->value)),
            amountMinor: (int) ($data['amount_minor'] ?? 0),
            currency: (string) ($data['currency'] ?? ''),
            payloadHash: hash('sha256', $payload),
            failureCode: isset($data['failure_code']) ? (string) $data['failure_code'] : null,
        );
    }

    public function isTrustedCheckoutUrl(string $url): bool
    {
        return str_starts_with($url, 'https://gateway.example.test/checkout/');
    }

    private function ensureTesting(): void
    {
        if (! app()->environment('testing')) {
            throw new LogicException('The local payment gateway is available only during automated tests.');
        }
    }
}
