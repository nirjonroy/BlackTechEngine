<?php

use App\Enums\PaymentStatus;
use App\Enums\PaymentType;
use App\Models\BillingInvoice;
use App\Models\IntegrationSetting;
use App\Models\PaymentTransaction;
use App\Services\Billing\StripePaymentGateway;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use UnexpectedValueException;

function configuredStripe(): IntegrationSetting
{
    return IntegrationSetting::factory()->create([
        'id' => 1,
        'stripe_mode' => 'test',
        'stripe_publishable_key' => 'pk_test_synthetic',
        'stripe_secret_key' => 'sk_test_synthetic',
        'stripe_webhook_secret' => 'whsec_synthetic',
        'stripe_enabled' => true,
    ]);
}

function stripeSignature(string $payload, int $timestamp, string $secret = 'whsec_synthetic'): string
{
    return 't='.$timestamp.',v1='.hash_hmac('sha256', $timestamp.'.'.$payload, $secret);
}

test('Stripe Checkout uses the fixed endpoint server invoice and idempotency key', function () {
    configuredStripe();
    [, , , $subscription] = subscriptionFixture();
    $invoice = BillingInvoice::factory()->for($subscription, 'subscription')->create([
        'workspace_id' => $subscription->workspace_id,
        'total_minor' => 2900,
        'currency' => 'USD',
    ]);
    $payment = PaymentTransaction::factory()->for($invoice, 'invoice')->create([
        'workspace_subscription_id' => $subscription->id,
        'workspace_id' => $subscription->workspace_id,
        'provider' => 'stripe',
        'provider_reference' => null,
        'idempotency_key' => 'checkout-key',
        'amount_minor' => 2900,
    ]);
    Http::preventStrayRequests();
    Http::fake([StripePaymentGateway::API_BASE_URL.'/*' => Http::response([
        'id' => 'cs_test_synthetic123',
        'url' => 'https://checkout.stripe.com/c/pay/cs_test_synthetic123',
        'expires_at' => now()->addMinutes(30)->timestamp,
    ])]);

    $session = app(StripePaymentGateway::class)->initiateCheckout(
        $invoice,
        'checkout-key',
        'https://app.example.test/return',
        'https://app.example.test/cancel',
    );

    expect($session->providerReference)->toBe('cs_test_synthetic123')
        ->and($session->checkoutUrl)->toStartWith('https://checkout.stripe.com/');
    Http::assertSent(function (Request $request) use ($payment): bool {
        return $request->url() === StripePaymentGateway::API_BASE_URL.'/v1/checkout/sessions'
            && $request->hasHeader('Idempotency-Key', 'checkout-key')
            && data_get($request->data(), 'metadata.payment_transaction_id') === (string) $payment->id
            && data_get($request->data(), 'line_items.0.price_data.unit_amount') === 2900;
    });
});

test('Stripe signed payment refund and dispute events are normalized safely', function (string $type, array $object, PaymentType $expectedType, PaymentStatus $expectedStatus): void {
    configuredStripe();
    $this->travelTo('2026-11-07 20:00:00');
    $payload = json_encode([
        'id' => 'evt_synthetic123',
        'type' => $type,
        'data' => ['object' => $object],
    ], JSON_THROW_ON_ERROR);
    $timestamp = now()->timestamp;
    $event = app(StripePaymentGateway::class)->verifyWebhook($payload, [
        'stripe-signature' => [stripeSignature($payload, $timestamp)],
    ]);

    expect($event->type)->toBe($expectedType)
        ->and($event->status)->toBe($expectedStatus)
        ->and($event->amountMinor)->toBe(2900)
        ->and($event->currency)->toBe('USD')
        ->and($event->providerPaymentReference)->toBe('pi_synthetic123');
})->with([
    'paid checkout' => ['checkout.session.completed', ['id' => 'cs_test_synthetic123', 'payment_status' => 'paid', 'amount_total' => 2900, 'currency' => 'usd', 'payment_intent' => 'pi_synthetic123'], PaymentType::Charge, PaymentStatus::Succeeded],
    'refund' => ['charge.refunded', ['id' => 'ch_synthetic123', 'amount_refunded' => 2900, 'currency' => 'usd', 'payment_intent' => 'pi_synthetic123'], PaymentType::Refund, PaymentStatus::Refunded],
    'chargeback' => ['charge.dispute.created', ['id' => 'dp_synthetic123', 'amount' => 2900, 'currency' => 'usd', 'payment_intent' => 'pi_synthetic123'], PaymentType::Chargeback, PaymentStatus::Disputed],
]);

test('Stripe rejects forged stale and malformed webhooks', function () {
    configuredStripe();
    $payload = json_encode(['id' => 'evt_synthetic123', 'type' => 'checkout.session.completed', 'data' => ['object' => []]], JSON_THROW_ON_ERROR);

    expect(fn () => app(StripePaymentGateway::class)->verifyWebhook($payload, [
        'stripe-signature' => ['t=1,v1='.str_repeat('a', 64)],
    ]))->toThrow(UnexpectedValueException::class);
});

test('Stripe configuration and checkout URL trust fail closed', function () {
    $gateway = app(StripePaymentGateway::class);
    expect($gateway->isConfigured())->toBeFalse()
        ->and($gateway->isTrustedCheckoutUrl('https://checkout.stripe.com/c/pay/test'))->toBeTrue()
        ->and($gateway->isTrustedCheckoutUrl('https://checkout.stripe.com.attacker.test/test'))->toBeFalse()
        ->and($gateway->isTrustedCheckoutUrl('http://checkout.stripe.com/test'))->toBeFalse();
});
