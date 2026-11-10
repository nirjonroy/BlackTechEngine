<?php

namespace App\Services\Billing;

use App\Enums\PaymentStatus;
use App\Enums\PaymentType;
use App\Models\BillingInvoice;
use App\Models\IntegrationSetting;
use App\Models\PaymentTransaction;
use App\Services\Billing\Contracts\PaymentGateway;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use UnexpectedValueException;

class StripePaymentGateway implements PaymentGateway
{
    public const API_BASE_URL = 'https://api.stripe.com';

    private const SIGNATURE_TOLERANCE_SECONDS = 300;

    public function name(): string
    {
        return 'stripe';
    }

    public function isConfigured(): bool
    {
        $setting = $this->settings();

        if (! $setting instanceof IntegrationSetting || ! $setting->stripe_enabled) {
            return false;
        }

        $mode = $setting->stripe_mode === 'live' ? 'live' : 'test';

        try {
            return str_starts_with((string) $setting->stripe_publishable_key, 'pk_'.$mode.'_')
                && str_starts_with((string) $setting->stripe_secret_key, 'sk_'.$mode.'_')
                && str_starts_with((string) $setting->stripe_webhook_secret, 'whsec_');
        } catch (DecryptException) {
            return false;
        }
    }

    public function initiateCheckout(
        BillingInvoice $invoice,
        string $idempotencyKey,
        string $returnUrl,
        string $cancelUrl,
    ): CheckoutSession {
        $setting = $this->configuredSettings();
        $payment = PaymentTransaction::query()
            ->whereBelongsTo($invoice, 'invoice')
            ->where('provider', $this->name())
            ->where('idempotency_key', $idempotencyKey)
            ->firstOrFail();
        $invoice->loadMissing('subscription.plan');
        $planName = $invoice->subscription?->plan?->name ?? 'BlackTechEngine subscription';

        try {
            $response = Http::baseUrl(self::API_BASE_URL)
                ->withToken((string) $setting->stripe_secret_key)
                ->acceptJson()
                ->asForm()
                ->withHeaders(['Idempotency-Key' => $idempotencyKey])
                ->connectTimeout(5)
                ->timeout(20)
                ->withoutRedirecting()
                ->post('/v1/checkout/sessions', [
                    'mode' => 'payment',
                    'success_url' => $returnUrl,
                    'cancel_url' => $cancelUrl,
                    'client_reference_id' => $invoice->number,
                    'line_items' => [[
                        'quantity' => 1,
                        'price_data' => [
                            'currency' => strtolower($invoice->currency),
                            'unit_amount' => $invoice->total_minor,
                            'product_data' => ['name' => $planName],
                        ],
                    ]],
                    'metadata' => [
                        'payment_transaction_id' => (string) $payment->getKey(),
                        'invoice_number' => $invoice->number,
                    ],
                    'payment_intent_data' => [
                        'metadata' => [
                            'payment_transaction_id' => (string) $payment->getKey(),
                            'invoice_number' => $invoice->number,
                        ],
                    ],
                ]);
        } catch (ConnectionException) {
            $this->throwCheckoutFailure();
        }

        if (! $response->successful() || strlen($response->body()) > 1_000_000) {
            $this->throwCheckoutFailure();
        }

        $payload = $response->json();
        $reference = is_array($payload) ? ($payload['id'] ?? null) : null;
        $url = is_array($payload) ? ($payload['url'] ?? null) : null;
        $expiresAt = is_array($payload) ? ($payload['expires_at'] ?? null) : null;

        if (! is_string($reference)
            || preg_match('/\Acs_(?:test|live)_[A-Za-z0-9]+\z/D', $reference) !== 1
            || ! is_string($url)
            || ! $this->isTrustedCheckoutUrl($url)
            || ($expiresAt !== null && (! is_int($expiresAt) || $expiresAt < 1))) {
            $this->throwCheckoutFailure();
        }

        return new CheckoutSession(
            $this->name(),
            $reference,
            $url,
            is_int($expiresAt) ? CarbonImmutable::createFromTimestampUTC($expiresAt) : null,
        );
    }

    /** @param array<string, list<string>> $headers */
    public function verifyWebhook(string $payload, array $headers): PaymentEvent
    {
        $setting = $this->configuredSettings();
        $signatureHeader = $headers['stripe-signature'][0] ?? null;

        if (! is_string($signatureHeader) || ! $this->validSignature($payload, $signatureHeader, (string) $setting->stripe_webhook_secret)) {
            throw new UnexpectedValueException('The Stripe webhook signature is invalid.');
        }

        $event = json_decode($payload, true, 64, JSON_THROW_ON_ERROR);

        if (! is_array($event) || array_is_list($event)) {
            throw new UnexpectedValueException('The Stripe webhook payload is invalid.');
        }

        $eventId = $this->requiredReference($event['id'] ?? null, '/\Aevt_[A-Za-z0-9]+\z/D');
        $type = $event['type'] ?? null;
        $object = data_get($event, 'data.object');

        if (! is_string($type) || ! is_array($object) || array_is_list($object)) {
            throw new UnexpectedValueException('The Stripe webhook event is incomplete.');
        }

        [$paymentType, $status, $amountMinor] = $this->eventState($type, $object);
        $providerReference = $this->providerReference($type, $object);
        $paymentIntent = $object['payment_intent'] ?? null;
        $currency = $object['currency'] ?? null;

        if (! is_int($amountMinor) || $amountMinor < 0 || ! is_string($currency) || preg_match('/\A[a-zA-Z]{3}\z/D', $currency) !== 1) {
            throw new UnexpectedValueException('The Stripe payment values are invalid.');
        }

        return new PaymentEvent(
            provider: $this->name(),
            providerEventId: $eventId,
            providerReference: $providerReference,
            type: $paymentType,
            status: $status,
            amountMinor: $amountMinor,
            currency: strtoupper($currency),
            payloadHash: hash('sha256', $payload),
            failureCode: $this->safeFailureCode($object),
            providerPaymentReference: is_string($paymentIntent) && preg_match('/\Api_[A-Za-z0-9]+\z/D', $paymentIntent) === 1
                ? $paymentIntent
                : null,
        );
    }

    public function isTrustedCheckoutUrl(string $url): bool
    {
        $parts = parse_url($url);

        return is_array($parts)
            && ($parts['scheme'] ?? null) === 'https'
            && ($parts['host'] ?? null) === 'checkout.stripe.com'
            && ! isset($parts['user'], $parts['pass']);
    }

    private function settings(): ?IntegrationSetting
    {
        if (! Schema::hasTable('integration_settings')) {
            return null;
        }

        return IntegrationSetting::query()->find(1);
    }

    private function configuredSettings(): IntegrationSetting
    {
        $setting = $this->settings();

        if (! $setting instanceof IntegrationSetting || ! $this->isConfigured()) {
            throw new UnexpectedValueException('Stripe is not configured.');
        }

        return $setting;
    }

    private function validSignature(string $payload, string $header, string $secret): bool
    {
        $timestamp = null;
        $signatures = [];

        foreach (explode(',', $header) as $part) {
            [$key, $value] = array_pad(explode('=', trim($part), 2), 2, null);

            if ($key === 't' && is_string($value) && ctype_digit($value)) {
                $timestamp = (int) $value;
            } elseif ($key === 'v1' && is_string($value) && preg_match('/\A[a-f0-9]{64}\z/D', $value) === 1) {
                $signatures[] = $value;
            }
        }

        if ($timestamp === null || abs(now()->timestamp - $timestamp) > self::SIGNATURE_TOLERANCE_SECONDS) {
            return false;
        }

        $expected = hash_hmac('sha256', $timestamp.'.'.$payload, $secret);

        return collect($signatures)->contains(fn (string $signature): bool => hash_equals($expected, $signature));
    }

    /** @return array{PaymentType, PaymentStatus, int} */
    private function eventState(string $type, array $object): array
    {
        return match ($type) {
            'checkout.session.completed', 'checkout.session.async_payment_succeeded' => [
                PaymentType::Charge,
                ($object['payment_status'] ?? null) === 'paid' ? PaymentStatus::Succeeded : throw new UnexpectedValueException('Stripe has not confirmed payment.'),
                $object['amount_total'] ?? null,
            ],
            'checkout.session.async_payment_failed' => [PaymentType::Charge, PaymentStatus::Failed, $object['amount_total'] ?? null],
            'checkout.session.expired' => [PaymentType::Charge, PaymentStatus::Expired, $object['amount_total'] ?? null],
            'charge.refunded' => [PaymentType::Refund, PaymentStatus::Refunded, $object['amount_refunded'] ?? null],
            'charge.dispute.created' => [PaymentType::Chargeback, PaymentStatus::Disputed, $object['amount'] ?? null],
            default => throw new UnexpectedValueException('The Stripe event type is not supported.'),
        };
    }

    private function providerReference(string $type, array $object): string
    {
        if (str_starts_with($type, 'checkout.session.')) {
            return $this->requiredReference($object['id'] ?? null, '/\Acs_(?:test|live)_[A-Za-z0-9]+\z/D');
        }

        return $this->requiredReference($object['payment_intent'] ?? null, '/\Api_[A-Za-z0-9]+\z/D');
    }

    private function requiredReference(mixed $value, string $pattern): string
    {
        if (! is_string($value) || preg_match($pattern, $value) !== 1) {
            throw new UnexpectedValueException('The Stripe event reference is invalid.');
        }

        return $value;
    }

    private function safeFailureCode(array $object): ?string
    {
        $code = data_get($object, 'last_payment_error.code');

        return is_string($code) && preg_match('/\A[a-z0-9_]{1,96}\z/D', $code) === 1 ? $code : null;
    }

    private function throwCheckoutFailure(): never
    {
        throw ValidationException::withMessages([
            'payment' => 'Stripe Checkout could not be started. No paid access was granted.',
        ]);
    }
}
