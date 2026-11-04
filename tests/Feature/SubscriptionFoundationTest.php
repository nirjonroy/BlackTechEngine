<?php

use App\Enums\BillingInterval;
use App\Enums\EntitlementKey;
use App\Enums\PaymentStatus;
use App\Enums\SubscriptionStatus;
use App\Enums\UsageEventStatus;
use App\Models\Admin;
use App\Models\PricingPlan;
use App\Models\PricingPlanEntitlement;
use App\Models\Workspace;
use App\Services\Billing\PaymentResult;
use App\Services\Subscriptions\EntitlementService;
use App\Services\Subscriptions\InvoiceService;
use App\Services\Subscriptions\SubscriptionManager;
use App\Services\Subscriptions\UsageMeter;
use Illuminate\Validation\ValidationException;

test('subscription enforcement remains fail open while disabled', function () {
    config()->set('subscriptions.enforcement_enabled', false);
    $workspace = Workspace::factory()->create();

    $decision = app(EntitlementService::class)->decision($workspace, EntitlementKey::ContentStudio);

    expect($decision->allowed)->toBeTrue()
        ->and(app(UsageMeter::class)->reserve($workspace, EntitlementKey::ContentDraftsPerPeriod, 1, fake()->uuid()))->toBeNull();
});

test('entitlements are read from the immutable subscription snapshot', function () {
    config()->set('subscriptions.enforcement_enabled', true);
    [, $workspace, $plan] = subscriptionFixture([EntitlementKey::MaximumBrands->value => 2]);

    $plan->entitlements()->where('key', EntitlementKey::MaximumBrands->value)->update(['limit' => 99]);
    $decision = app(EntitlementService::class)->decision($workspace, EntitlementKey::MaximumBrands, 1);

    expect($decision->allowed)->toBeTrue()->and($decision->limit)->toBe(2)->and($decision->remaining)->toBe(1);
});

test('usage reservations are idempotent and commit atomically', function () {
    config()->set('subscriptions.enforcement_enabled', true);
    [$user, $workspace] = subscriptionFixture([EntitlementKey::ContentDraftsPerPeriod->value => 2]);
    $meter = app(UsageMeter::class);
    $key = fake()->uuid();

    $first = $meter->reserve($workspace, EntitlementKey::ContentDraftsPerPeriod, 1, $key, $user);
    $replay = $meter->reserve($workspace, EntitlementKey::ContentDraftsPerPeriod, 1, $key, $user);
    $meter->commit($first, 'draft', 123);

    expect($replay->is($first))->toBeTrue()
        ->and($first->refresh()->status)->toBe(UsageEventStatus::Committed)
        ->and($first->counter->fresh()->used)->toBe(1)
        ->and($first->counter->fresh()->reserved)->toBe(0);
});

test('usage limits reject over reservation and release restores capacity', function () {
    config()->set('subscriptions.enforcement_enabled', true);
    [, $workspace] = subscriptionFixture([EntitlementKey::SocialPostsPerPeriod->value => 1]);
    $meter = app(UsageMeter::class);
    $event = $meter->reserve($workspace, EntitlementKey::SocialPostsPerPeriod, 1, fake()->uuid());

    expect(fn () => $meter->reserve($workspace, EntitlementKey::SocialPostsPerPeriod, 1, fake()->uuid()))
        ->toThrow(ValidationException::class);

    $meter->release($event);
    expect($meter->remaining($workspace, EntitlementKey::SocialPostsPerPeriod))->toBe(1);
});

test('multiple usage reservations reconcile in one transaction', function () {
    config()->set('subscriptions.enforcement_enabled', true);
    [, $workspace] = subscriptionFixture([
        EntitlementKey::AiGenerationAttempts->value => 2,
        EntitlementKey::AiTokens->value => 100,
    ]);
    $meter = app(UsageMeter::class);
    $key = fake()->uuid();
    $attempt = $meter->reserve($workspace, EntitlementKey::AiGenerationAttempts, 1, $key);
    $tokens = $meter->reserve($workspace, EntitlementKey::AiTokens, 100, $key);

    expect(fn () => $meter->commitMany([
        ['event' => $attempt, 'actual_quantity' => 1],
        ['event' => $tokens, 'actual_quantity' => 101],
    ]))->toThrow(InvalidArgumentException::class);

    expect($attempt?->fresh()->status)->toBe(UsageEventStatus::Reserved)
        ->and($tokens?->fresh()->status)->toBe(UsageEventStatus::Reserved)
        ->and($attempt?->counter->fresh()->used)->toBe(0)
        ->and($tokens?->counter->fresh()->used)->toBe(0);
});

test('admin grants create one subscription and immutable audit history', function () {
    $admin = Admin::query()->create(['name' => 'Billing Admin', 'email' => 'billing@example.test', 'password' => bcrypt('password')]);
    $workspace = Workspace::factory()->create();
    $plan = PricingPlan::factory()->create();

    $subscription = app(SubscriptionManager::class)->grant($workspace, $plan, $admin, BillingInterval::Monthly);

    expect($subscription->status)->toBe(SubscriptionStatus::Active)
        ->and($subscription->histories()->where('action', 'admin_granted')->count())->toBe(1)
        ->and(fn () => app(SubscriptionManager::class)->grant($workspace, $plan, $admin, BillingInterval::Monthly))
        ->toThrow(ValidationException::class);
});

test('the designated non-billable Free plan is assigned once with an audit history', function () {
    config()->set('subscriptions.free_plan_code', 'free');
    $workspace = Workspace::factory()->create();
    $plan = PricingPlan::factory()->create([
        'code' => 'free',
        'is_billable' => false,
        'show_on_pricing_page' => false,
        'monthly_price' => 0,
        'annual_price' => 0,
        'monthly_price_minor' => 0,
        'annual_price_minor' => 0,
    ]);
    PricingPlanEntitlement::factory()->for($plan, 'plan')->create([
        'key' => EntitlementKey::MaximumWorkspaces->value,
        'limit' => 1,
    ]);
    $manager = app(SubscriptionManager::class);

    $subscription = $manager->assignComplimentaryFree($workspace);
    $replay = $manager->assignComplimentaryFree($workspace);

    expect($replay->is($subscription))->toBeTrue()
        ->and($subscription->status)->toBe(SubscriptionStatus::Active)
        ->and($subscription->billing_interval)->toBe(BillingInterval::Monthly)
        ->and($subscription->price_minor)->toBe(0)
        ->and($subscription->activation_source)->toBe('complimentary_free')
        ->and($plan->refresh()->show_on_pricing_page)->toBeFalse()
        ->and(data_get($subscription->entitlement_snapshot, 'plan.code'))->toBe('free')
        ->and($subscription->histories()->where('action', 'complimentary_free_assigned')->count())->toBe(1);
});

test('the complimentary path rejects a billable plan even when its code is configured as Free', function () {
    config()->set('subscriptions.free_plan_code', 'free');
    PricingPlan::factory()->create(['code' => 'free', 'is_billable' => true]);

    expect(fn () => app(SubscriptionManager::class)->assignComplimentaryFree(Workspace::factory()->create()))
        ->toThrow(ValidationException::class);
});

test('annual subscriptions receive monthly usage periods without advancing billing', function () {
    $this->travelTo('2026-01-15 12:00:00');
    config()->set('subscriptions.enforcement_enabled', true);
    [, $workspace, , $subscription] = subscriptionFixture([
        EntitlementKey::ContentDraftsPerPeriod->value => 1,
    ]);
    $subscription->update([
        'billing_interval' => BillingInterval::Annual,
        'current_period_starts_at' => '2026-01-01 00:00:00',
        'current_period_ends_at' => '2027-01-01 00:00:00',
        'renews_at' => '2027-01-01 00:00:00',
    ]);

    $january = app(UsageMeter::class)->reserve(
        $workspace,
        EntitlementKey::ContentDraftsPerPeriod,
        1,
        fake()->uuid(),
    );
    $this->travelTo('2026-02-01 00:00:01');
    $february = app(UsageMeter::class)->reserve(
        $workspace,
        EntitlementKey::ContentDraftsPerPeriod,
        1,
        fake()->uuid(),
    );

    expect($january?->counter->period_starts_at->toDateTimeString())->toBe('2026-01-01 00:00:00')
        ->and($january?->counter->period_ends_at->toDateTimeString())->toBe('2026-02-01 00:00:00')
        ->and($february?->counter->period_starts_at->toDateTimeString())->toBe('2026-02-01 00:00:00')
        ->and($february?->counter->period_ends_at->toDateTimeString())->toBe('2026-03-01 00:00:00')
        ->and($subscription->usageCounters()->count())->toBe(2)
        ->and($subscription->fresh()->current_period_starts_at->toDateTimeString())->toBe('2026-01-01 00:00:00')
        ->and($subscription->fresh()->current_period_ends_at->toDateTimeString())->toBe('2027-01-01 00:00:00');
});

test('unpriced billing intervals cannot be granted', function () {
    $admin = Admin::query()->create(['name' => 'Billing Admin', 'email' => 'billing2@example.test', 'password' => bcrypt('password')]);
    $plan = PricingPlan::factory()->create(['annual_price_minor' => null]);

    expect(fn () => app(SubscriptionManager::class)->grant(Workspace::factory()->create(), $plan, $admin, BillingInterval::Annual))
        ->toThrow(ValidationException::class);
});

test('plan changes use optimistic locking and cancellation supports period end', function () {
    $admin = Admin::query()->create(['name' => 'Lifecycle Admin', 'email' => 'lifecycle@example.test', 'password' => bcrypt('password')]);
    [, , , $subscription] = subscriptionFixture();
    $newPlan = PricingPlan::factory()->create(['monthly_price_minor' => 4900]);
    $manager = app(SubscriptionManager::class);

    $changed = $manager->changePlan($subscription, $newPlan, $admin, BillingInterval::Monthly, 0);

    expect($changed->lock_version)->toBe(1)
        ->and(data_get($changed->entitlement_snapshot, 'plan.id'))->toBe($newPlan->id)
        ->and(fn () => $manager->changePlan($changed, $newPlan, $admin, BillingInterval::Monthly, 0))
        ->toThrow(ValidationException::class);

    $cancelled = $manager->cancel($changed, $admin, 1, true);
    expect($cancelled->cancel_at_period_end)->toBeTrue()
        ->and($cancelled->status)->toBe(SubscriptionStatus::Active)
        ->and($cancelled->histories()->where('action', 'cancellation_scheduled')->exists())->toBeTrue();
});

test('verified payments use integer minor units and cannot be replayed across invoices', function () {
    [, , , $subscription] = subscriptionFixture();
    $subscription->update(['status' => SubscriptionStatus::PendingActivation]);
    $service = app(InvoiceService::class);
    $invoice = $service->create($subscription, [['description' => 'Monthly plan', 'quantity' => 1, 'unit_amount_minor' => 1900]]);
    $result = new PaymentResult('test', 'payment-1', 'event-1', PaymentStatus::Succeeded, 1900, 'USD');

    $first = $service->recordPayment($invoice, $result, 'payment-key-1');
    $replay = $service->recordPayment($invoice, $result, 'payment-key-1');
    $otherInvoice = $service->create($subscription, [['description' => 'Another invoice', 'quantity' => 1, 'unit_amount_minor' => 1900]]);

    expect($first->is($replay))->toBeTrue()
        ->and($invoice->fresh()->status->value)->toBe('paid')
        ->and($subscription->fresh()->status)->toBe(SubscriptionStatus::Active)
        ->and(fn () => $service->recordPayment($otherInvoice, $result, 'payment-key-1'))
        ->toThrow(ValidationException::class);
});
