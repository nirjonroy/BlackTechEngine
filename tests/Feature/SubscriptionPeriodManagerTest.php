<?php

use App\Enums\EntitlementKey;
use App\Enums\PaymentStatus;
use App\Enums\SubscriptionStatus;
use App\Models\UsageCounter;
use App\Services\Billing\PaymentResult;
use App\Services\Subscriptions\InvoiceService;
use App\Services\Subscriptions\SubscriptionManager;
use App\Services\Subscriptions\SubscriptionPeriodManager;
use App\Services\Subscriptions\UsageMeter;
use Carbon\CarbonImmutable;
use Illuminate\Validation\ValidationException;

test('complimentary admin grants advance once and preserve historical usage', function () {
    $this->travelTo('2026-11-04 12:00:00');
    [, $workspace, , $subscription] = subscriptionFixture([
        EntitlementKey::ContentDraftsPerPeriod->value => 2,
    ]);
    $subscription->update([
        'price_minor' => 0,
        'activation_source' => 'admin_grant',
        'current_period_starts_at' => CarbonImmutable::parse('2026-09-01 00:00:00'),
        'current_period_ends_at' => CarbonImmutable::parse('2026-10-01 00:00:00'),
        'renews_at' => CarbonImmutable::parse('2026-10-01 00:00:00'),
    ]);
    UsageCounter::factory()->for($subscription, 'subscription')->for($workspace)->create([
        'entitlement_key' => EntitlementKey::ContentDraftsPerPeriod->value,
        'period_starts_at' => CarbonImmutable::parse('2026-09-01 00:00:00'),
        'period_ends_at' => CarbonImmutable::parse('2026-10-01 00:00:00'),
        'used' => 2,
    ]);

    $first = app(SubscriptionPeriodManager::class)->processDue(100, CarbonImmutable::now());
    $second = app(SubscriptionPeriodManager::class)->processDue(100, CarbonImmutable::now());

    $subscription->refresh();
    expect($first)->toMatchArray(['inspected' => 1, 'advanced' => 1])
        ->and($second['inspected'])->toBe(0)
        ->and($subscription->current_period_starts_at->toDateTimeString())->toBe('2026-11-01 00:00:00')
        ->and($subscription->current_period_ends_at->toDateTimeString())->toBe('2026-12-01 00:00:00')
        ->and($subscription->histories()->where('action', 'period_advanced')->count())->toBe(1)
        ->and($subscription->usageCounters()->count())->toBe(1)
        ->and($subscription->usageCounters()->firstOrFail()->used)->toBe(2);

    config()->set('subscriptions.enforcement_enabled', true);
    $event = app(UsageMeter::class)->reserve(
        $workspace,
        EntitlementKey::ContentDraftsPerPeriod,
        1,
        fake()->uuid(),
    );

    expect($event?->counter->period_starts_at->toDateTimeString())->toBe('2026-11-01 00:00:00')
        ->and($subscription->usageCounters()->count())->toBe(2);
});

test('paid periods become past due and expire only after the configured grace period', function () {
    $this->travelTo('2026-11-04 12:00:00');
    [, , , $subscription] = subscriptionFixture();
    $subscription->update([
        'price_minor' => 1900,
        'current_period_ends_at' => CarbonImmutable::parse('2026-11-04 00:00:00'),
        'renews_at' => CarbonImmutable::parse('2026-11-04 00:00:00'),
    ]);
    config()->set('subscriptions.past_due_grace_days', 7);
    $periods = app(SubscriptionPeriodManager::class);

    $first = $periods->processDue(100, CarbonImmutable::now());
    $repeat = $periods->processDue(100, CarbonImmutable::now());
    $expired = $periods->processDue(100, CarbonImmutable::parse('2026-11-11 00:00:01'));

    expect($first['past_due'])->toBe(1)
        ->and($repeat['past_due'])->toBe(0)
        ->and($subscription->fresh()->status)->toBe(SubscriptionStatus::Expired)
        ->and($expired['expired'])->toBe(1)
        ->and($subscription->histories()->where('action', 'payment_became_due')->count())->toBe(1)
        ->and($subscription->histories()->where('action', 'past_due_expired')->count())->toBe(1);
});

test('period-end cancellation and trial expiry are terminal and idempotent', function () {
    $this->travelTo('2026-11-04 12:00:00');
    [, , , $cancelled] = subscriptionFixture();
    $cancelled->update([
        'cancel_at_period_end' => true,
        'current_period_ends_at' => CarbonImmutable::parse('2026-11-04 00:00:00'),
    ]);
    [, , , $trial] = subscriptionFixture();
    $trial->update([
        'status' => SubscriptionStatus::Trialing,
        'trial_ends_at' => CarbonImmutable::parse('2026-11-03 00:00:00'),
        'current_period_ends_at' => CarbonImmutable::parse('2026-12-01 00:00:00'),
    ]);

    $first = app(SubscriptionPeriodManager::class)->processDue(100, CarbonImmutable::now());
    $repeat = app(SubscriptionPeriodManager::class)->processDue(100, CarbonImmutable::now());

    expect($first['cancelled'])->toBe(1)
        ->and($first['expired'])->toBe(1)
        ->and($repeat['inspected'])->toBe(0)
        ->and($cancelled->fresh()->status)->toBe(SubscriptionStatus::Cancelled)
        ->and($trial->fresh()->status)->toBe(SubscriptionStatus::Expired);
});

test('a reconciled payment advances one past-due period and an unverified payment is rejected', function () {
    $this->travelTo('2026-11-04 12:00:00');
    [, , , $subscription] = subscriptionFixture();
    $subscription->update([
        'status' => SubscriptionStatus::PastDue,
        'current_period_starts_at' => CarbonImmutable::parse('2026-10-01 00:00:00'),
        'current_period_ends_at' => CarbonImmutable::parse('2026-11-01 00:00:00'),
        'renews_at' => null,
    ]);
    $invoice = app(InvoiceService::class)->create($subscription, [[
        'description' => 'Verified renewal',
        'quantity' => 1,
        'unit_amount_minor' => 1900,
    ]]);

    $payment = app(InvoiceService::class)->recordPayment(
        $invoice,
        new PaymentResult('test', 'renewal-1', 'event-renewal-1', PaymentStatus::Succeeded, 1900, 'USD'),
        fake()->uuid(),
    );

    $subscription->refresh();
    expect($payment->status)->toBe(PaymentStatus::Succeeded)
        ->and($subscription->status)->toBe(SubscriptionStatus::Active)
        ->and($subscription->current_period_starts_at->toDateTimeString())->toBe('2026-11-01 00:00:00')
        ->and($subscription->current_period_ends_at->toDateTimeString())->toBe('2026-12-01 00:00:00')
        ->and($subscription->histories()->where('action', 'payment_renewed')->count())->toBe(1);

    $unverified = $payment->replicate(['provider_reference', 'provider_event_id', 'idempotency_key']);
    $unverified->status = PaymentStatus::Failed;
    $unverified->provider_reference = 'failed-renewal';
    $unverified->provider_event_id = 'failed-event';
    $unverified->idempotency_key = fake()->uuid();
    $unverified->save();

    expect(fn () => app(SubscriptionManager::class)->activateFromPayment($unverified))
        ->toThrow(ValidationException::class);
});

test('the period command enforces its processing bound', function () {
    $this->artisan('subscriptions:process-periods', ['--limit' => 0])
        ->expectsOutput('The --limit option must be an integer between 1 and 500.')
        ->assertFailed();
});
