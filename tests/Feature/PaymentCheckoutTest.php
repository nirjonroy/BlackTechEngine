<?php

use App\Enums\BillingInterval;
use App\Enums\EntitlementKey;
use App\Enums\InvoiceStatus;
use App\Enums\PaymentStatus;
use App\Enums\SubscriptionStatus;
use App\Models\PaymentTransaction;
use App\Models\PaymentWebhookEvent;
use App\Models\PricingPlan;
use App\Models\PricingPlanEntitlement;
use App\Models\User;
use Illuminate\Support\Str;

beforeEach(function () {
    config()->set('subscriptions.checkout_enabled', true);
});

test('an owner can initiate an idempotent monthly paid checkout without changing access', function () {
    [$user, $workspace, , $subscription] = subscriptionFixture();
    $plan = PricingPlan::factory()->create(['monthly_price_minor' => 2900]);
    $key = (string) Str::uuid();

    $first = $this->actingAs($user)->post(route('subscriptions.checkout.store', $workspace), [
        'pricing_plan_id' => $plan->getKey(),
        'billing_interval' => BillingInterval::Monthly->value,
        'idempotency_key' => $key,
    ]);
    $replay = $this->actingAs($user)->post(route('subscriptions.checkout.store', $workspace), [
        'pricing_plan_id' => $plan->getKey(),
        'billing_interval' => BillingInterval::Monthly->value,
        'idempotency_key' => $key,
    ]);

    $first->assertRedirect('https://gateway.example.test/checkout/'.$key);
    $replay->assertRedirect('https://gateway.example.test/checkout/'.$key);
    $payment = PaymentTransaction::query()->sole();
    expect($payment->status)->toBe(PaymentStatus::Pending)
        ->and($payment->amount_minor)->toBe(2900)
        ->and($payment->invoice->status)->toBe(InvoiceStatus::Open)
        ->and($subscription->fresh()->pricing_plan_id)->not->toBe($plan->getKey())
        ->and(PaymentTransaction::query()->count())->toBe(1);
});

test('annual checkout uses the configured annual price', function () {
    [$user, $workspace] = subscriptionFixture();
    $plan = PricingPlan::factory()->create(['monthly_price_minor' => 2900, 'annual_price_minor' => 29000]);
    $key = (string) Str::uuid();

    $this->actingAs($user)->post(route('subscriptions.checkout.store', $workspace), [
        'pricing_plan_id' => $plan->getKey(),
        'billing_interval' => BillingInterval::Annual->value,
        'idempotency_key' => $key,
    ])->assertRedirect('https://gateway.example.test/checkout/'.$key);

    expect(PaymentTransaction::query()->sole()->amount_minor)->toBe(29000);
});

test('checkout rejects guests members disabled gateways and reused identifiers from another order', function () {
    [$owner, $workspace] = subscriptionFixture();
    $member = User::factory()->create();
    $workspace->members()->attach($member, ['role' => 'member']);
    $plan = PricingPlan::factory()->create();
    $key = (string) Str::uuid();
    $payload = [
        'pricing_plan_id' => $plan->getKey(),
        'billing_interval' => BillingInterval::Monthly->value,
        'idempotency_key' => $key,
    ];

    $this->post(route('subscriptions.checkout.store', $workspace), $payload)->assertRedirect(route('login'));
    $this->actingAs($member)->post(route('subscriptions.checkout.store', $workspace), $payload)->assertNotFound();
    $this->actingAs($owner)->post(route('subscriptions.checkout.store', $workspace), $payload)->assertRedirect();

    $otherPlan = PricingPlan::factory()->create();
    $this->actingAs($owner)->from(route('subscriptions.index'))->post(route('subscriptions.checkout.store', $workspace), [
        ...$payload,
        'pricing_plan_id' => $otherPlan->getKey(),
    ])->assertSessionHasErrors('idempotency_key');

    config()->set('subscriptions.checkout_enabled', false);
    $this->actingAs($owner)->from(route('subscriptions.index'))->post(route('subscriptions.checkout.store', $workspace), [
        ...$payload,
        'idempotency_key' => (string) Str::uuid(),
    ])->assertSessionHasErrors('payment');
});

test('a signed successful webhook activates the paid plan once and snapshots its entitlements', function () {
    [$user, $workspace, , $subscription] = subscriptionFixture();
    $plan = PricingPlan::factory()->create(['monthly_price_minor' => 4900]);
    PricingPlanEntitlement::factory()->for($plan, 'plan')->create([
        'key' => EntitlementKey::ContentDraftsPerPeriod->value,
        'limit' => 50,
    ]);
    $this->actingAs($user)->post(route('subscriptions.checkout.store', $workspace), [
        'pricing_plan_id' => $plan->getKey(),
        'billing_interval' => BillingInterval::Monthly->value,
        'idempotency_key' => (string) Str::uuid(),
    ]);
    $payment = PaymentTransaction::query()->sole();
    $payload = paymentEventPayload($payment, 'event-success-1', PaymentStatus::Succeeded);

    $this->withHeader('X-Test-Signature', 'test-signature')
        ->postJson(route('billing.webhooks.payment'), $payload)
        ->assertOk()
        ->assertExactJson(['received' => true]);
    $this->withHeader('X-Test-Signature', 'test-signature')
        ->postJson(route('billing.webhooks.payment'), $payload)
        ->assertOk();

    $subscription->refresh();
    expect($subscription->pricing_plan_id)->toBe($plan->getKey())
        ->and($subscription->status)->toBe(SubscriptionStatus::Active)
        ->and($subscription->activation_source)->toBe('verified_payment')
        ->and(data_get($subscription->entitlement_snapshot, 'entitlements.'.EntitlementKey::ContentDraftsPerPeriod->value.'.limit'))->toBe(50)
        ->and($subscription->histories()->where('action', 'payment_checkout_plan_changed')->count())->toBe(1)
        ->and(PaymentWebhookEvent::query()->count())->toBe(1)
        ->and($payment->invoice->fresh()->status)->toBe(InvoiceStatus::Paid);
});

test('redirecting back to an invoice never activates an unverified checkout', function () {
    [$user, $workspace, , $subscription] = subscriptionFixture();
    $plan = PricingPlan::factory()->create();
    $this->actingAs($user)->post(route('subscriptions.checkout.store', $workspace), [
        'pricing_plan_id' => $plan->getKey(),
        'billing_interval' => BillingInterval::Monthly->value,
        'idempotency_key' => (string) Str::uuid(),
    ]);
    $payment = PaymentTransaction::query()->sole();

    $this->actingAs($user)->get(route('subscriptions.invoices.show', [$workspace, $payment->invoice]))
        ->assertOk()
        ->assertSee('Awaiting provider confirmation');

    expect($subscription->fresh()->pricing_plan_id)->not->toBe($plan->getKey());
});

test('a verified past-due renewal advances exactly one billing period', function () {
    $this->travelTo('2026-11-08 12:00:00');
    [$user, $workspace, $plan, $subscription] = subscriptionFixture();
    $subscription->update([
        'status' => SubscriptionStatus::PastDue,
        'current_period_starts_at' => '2026-10-01 00:00:00',
        'current_period_ends_at' => '2026-11-01 00:00:00',
        'renews_at' => null,
    ]);
    $this->actingAs($user)->post(route('subscriptions.checkout.store', $workspace), [
        'pricing_plan_id' => $plan->getKey(),
        'billing_interval' => BillingInterval::Monthly->value,
        'idempotency_key' => (string) Str::uuid(),
    ]);
    $payment = PaymentTransaction::query()->sole();

    $this->withHeader('X-Test-Signature', 'test-signature')->postJson(
        route('billing.webhooks.payment'),
        paymentEventPayload($payment, 'event-renewal', PaymentStatus::Succeeded),
    )->assertOk();

    $subscription->refresh();
    expect($subscription->status)->toBe(SubscriptionStatus::Active)
        ->and($subscription->current_period_starts_at->toDateTimeString())->toBe('2026-11-01 00:00:00')
        ->and($subscription->current_period_ends_at->toDateTimeString())->toBe('2026-12-01 00:00:00')
        ->and($subscription->histories()->where('action', 'payment_checkout_renewed')->count())->toBe(1);
});

test('a paid event requiring manual review preserves the money record without granting stale access', function () {
    [$user, $workspace, , $subscription] = subscriptionFixture();
    $plan = PricingPlan::factory()->create();
    $this->actingAs($user)->post(route('subscriptions.checkout.store', $workspace), [
        'pricing_plan_id' => $plan->getKey(),
        'billing_interval' => BillingInterval::Monthly->value,
        'idempotency_key' => (string) Str::uuid(),
    ]);
    $payment = PaymentTransaction::query()->sole();
    $subscription->increment('lock_version');

    $this->withHeader('X-Test-Signature', 'test-signature')->postJson(
        route('billing.webhooks.payment'),
        paymentEventPayload($payment, 'event-stale-subscription', PaymentStatus::Succeeded),
    )->assertAccepted();

    expect($payment->fresh()->status)->toBe(PaymentStatus::Succeeded)
        ->and($payment->invoice->fresh()->status)->toBe(InvoiceStatus::Paid)
        ->and($subscription->fresh()->pricing_plan_id)->not->toBe($plan->getKey())
        ->and(PaymentWebhookEvent::query()->sole()->processing_status)->toBe('requires_review');
});

test('invalid signatures and mismatched payment values cannot activate access', function () {
    [$user, $workspace, , $subscription] = subscriptionFixture();
    $plan = PricingPlan::factory()->create(['monthly_price_minor' => 4900]);
    $this->actingAs($user)->post(route('subscriptions.checkout.store', $workspace), [
        'pricing_plan_id' => $plan->getKey(),
        'billing_interval' => BillingInterval::Monthly->value,
        'idempotency_key' => (string) Str::uuid(),
    ]);
    $payment = PaymentTransaction::query()->sole();
    $payload = paymentEventPayload($payment, 'event-invalid-1', PaymentStatus::Succeeded, 1);

    $this->withHeader('X-Test-Signature', 'wrong')
        ->postJson(route('billing.webhooks.payment'), $payload)
        ->assertBadRequest();
    $this->withHeader('X-Test-Signature', 'test-signature')
        ->postJson(route('billing.webhooks.payment'), $payload)
        ->assertAccepted();

    expect($payment->fresh()->status)->toBe(PaymentStatus::Pending)
        ->and($subscription->fresh()->pricing_plan_id)->not->toBe($plan->getKey())
        ->and(PaymentWebhookEvent::query()->sole()->processing_status)->toBe('rejected');
});

test('failed cancelled and expired checkout events never activate paid access', function (PaymentStatus $status) {
    [$user, $workspace, , $subscription] = subscriptionFixture();
    $plan = PricingPlan::factory()->create();
    $this->actingAs($user)->post(route('subscriptions.checkout.store', $workspace), [
        'pricing_plan_id' => $plan->getKey(),
        'billing_interval' => BillingInterval::Monthly->value,
        'idempotency_key' => (string) Str::uuid(),
    ]);
    $payment = PaymentTransaction::query()->sole();

    $this->withHeader('X-Test-Signature', 'test-signature')
        ->postJson(route('billing.webhooks.payment'), paymentEventPayload($payment, 'event-'.$status->value, $status))
        ->assertOk();

    expect($payment->fresh()->status)->toBe($status)
        ->and($subscription->fresh()->pricing_plan_id)->not->toBe($plan->getKey());
})->with([
    'failed' => PaymentStatus::Failed,
    'cancelled' => PaymentStatus::Cancelled,
    'expired' => PaymentStatus::Expired,
]);

test('verified refunds and chargebacks suspend only the subscription activated by that payment', function (string $type, PaymentStatus $status, string $history) {
    [$user, $workspace, , $subscription] = subscriptionFixture();
    $plan = PricingPlan::factory()->create();
    $this->actingAs($user)->post(route('subscriptions.checkout.store', $workspace), [
        'pricing_plan_id' => $plan->getKey(),
        'billing_interval' => BillingInterval::Monthly->value,
        'idempotency_key' => (string) Str::uuid(),
    ]);
    $payment = PaymentTransaction::query()->sole();
    $this->withHeader('X-Test-Signature', 'test-signature')->postJson(
        route('billing.webhooks.payment'),
        paymentEventPayload($payment, 'event-paid', PaymentStatus::Succeeded),
    )->assertOk();

    $this->withHeader('X-Test-Signature', 'test-signature')->postJson(
        route('billing.webhooks.payment'),
        paymentEventPayload($payment->refresh(), 'event-reversal', $status, null, $type),
    )->assertOk();

    expect($subscription->fresh()->status)->toBe(SubscriptionStatus::PastDue)
        ->and($subscription->histories()->where('action', $history)->count())->toBe(1)
        ->and(PaymentWebhookEvent::query()->count())->toBe(2);
})->with([
    'refund' => ['refund', PaymentStatus::Refunded, 'payment_refunded'],
    'chargeback' => ['chargeback', PaymentStatus::Disputed, 'payment_disputed'],
]);

/** @return array<string, int|string> */
function paymentEventPayload(
    PaymentTransaction $payment,
    string $eventId,
    PaymentStatus $status,
    ?int $amountMinor = null,
    string $type = 'charge',
): array {
    return [
        'event_id' => $eventId,
        'provider_reference' => $payment->provider_reference,
        'type' => $type,
        'status' => $status->value,
        'amount_minor' => $amountMinor ?? $payment->amount_minor,
        'currency' => $payment->currency,
    ];
}
