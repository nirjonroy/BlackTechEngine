<?php

namespace App\Services\Subscriptions;

use App\Enums\BillingInterval;
use App\Enums\InvoiceStatus;
use App\Enums\PaymentStatus;
use App\Enums\ReconciliationStatus;
use App\Enums\SubscriptionStatus;
use App\Models\Admin;
use App\Models\PaymentTransaction;
use App\Models\PricingPlan;
use App\Models\SubscriptionHistory;
use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceSubscription;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class SubscriptionManager
{
    public function assignComplimentaryFree(Workspace $workspace): WorkspaceSubscription
    {
        return DB::transaction(function () use ($workspace): WorkspaceSubscription {
            $lockedWorkspace = Workspace::query()->lockForUpdate()->findOrFail($workspace->getKey());
            $existing = WorkspaceSubscription::query()->whereBelongsTo($lockedWorkspace)->first();

            if ($existing !== null) {
                if ($existing->activation_source === 'complimentary_free'
                    && data_get($existing->entitlement_snapshot, 'plan.code') === config('subscriptions.free_plan_code')) {
                    return $existing;
                }

                throw ValidationException::withMessages(['workspace' => 'This workspace already has a subscription record.']);
            }

            $planCode = config('subscriptions.free_plan_code');

            if (! is_string($planCode) || trim($planCode) === '') {
                throw ValidationException::withMessages(['subscription' => 'The complimentary Free plan is not configured.']);
            }

            $plan = PricingPlan::query()->where('code', $planCode)->lockForUpdate()->first();

            if ($plan === null
                || $plan->code !== $planCode
                || ! $plan->is_active
                || $plan->is_billable
                || ($plan->monthly_price_minor !== null && $plan->monthly_price_minor !== 0)
                || ($plan->annual_price_minor !== null && $plan->annual_price_minor !== 0)) {
                throw ValidationException::withMessages(['subscription' => 'The designated complimentary Free plan is unavailable.']);
            }

            $startsAt = CarbonImmutable::now('UTC')->startOfSecond();
            $periodEndsAt = $this->periodEnd($startsAt, BillingInterval::Monthly);
            $subscription = WorkspaceSubscription::query()->create([
                'workspace_id' => $lockedWorkspace->getKey(),
                'pricing_plan_id' => $plan->getKey(),
                'status' => SubscriptionStatus::Active,
                'billing_interval' => BillingInterval::Monthly,
                'currency' => strtoupper($plan->billing_currency),
                'price_minor' => 0,
                'entitlement_snapshot' => $this->snapshot($plan, BillingInterval::Monthly),
                'activation_source' => 'complimentary_free',
                'starts_at' => $startsAt,
                'current_period_starts_at' => $startsAt,
                'current_period_ends_at' => $periodEndsAt,
                'renews_at' => $periodEndsAt,
            ]);
            $this->record(
                $subscription,
                'complimentary_free_assigned',
                null,
                SubscriptionStatus::Active,
                null,
                null,
                ['pricing_plan_id' => $plan->getKey(), 'billing_version' => $plan->billing_version],
            );

            return $subscription;
        }, 3);
    }

    public function grant(
        Workspace $workspace,
        PricingPlan $plan,
        Admin $admin,
        BillingInterval $interval,
        bool $trial = false,
    ): WorkspaceSubscription {
        $this->ensureBillable($plan, $interval);

        return DB::transaction(function () use ($admin, $interval, $plan, $trial, $workspace): WorkspaceSubscription {
            Workspace::query()->lockForUpdate()->findOrFail($workspace->getKey());

            if (WorkspaceSubscription::query()->whereBelongsTo($workspace)->exists()) {
                throw ValidationException::withMessages(['workspace' => 'This workspace already has a subscription record.']);
            }

            $startsAt = CarbonImmutable::now('UTC')->startOfSecond();
            $periodEndsAt = $this->periodEnd($startsAt, $interval);
            $trialEndsAt = $trial && $plan->trial_days > 0 ? $startsAt->addDays($plan->trial_days) : null;
            $status = $trialEndsAt !== null ? SubscriptionStatus::Trialing : SubscriptionStatus::Active;
            $subscription = WorkspaceSubscription::query()->create([
                'workspace_id' => $workspace->getKey(),
                'pricing_plan_id' => $plan->getKey(),
                'created_by_admin_id' => $admin->getKey(),
                'status' => $status,
                'billing_interval' => $interval,
                'currency' => strtoupper($plan->billing_currency),
                'price_minor' => $this->priceFor($plan, $interval),
                'entitlement_snapshot' => $this->snapshot($plan, $interval),
                'activation_source' => 'admin_grant',
                'starts_at' => $startsAt,
                'current_period_starts_at' => $startsAt,
                'current_period_ends_at' => $periodEndsAt,
                'renews_at' => $interval === BillingInterval::Manual ? null : $periodEndsAt,
                'trial_ends_at' => $trialEndsAt,
            ]);
            $this->record($subscription, 'admin_granted', null, $status, null, $admin, [
                'pricing_plan_id' => $plan->getKey(),
                'billing_version' => $plan->billing_version,
            ]);

            return $subscription;
        }, 3);
    }

    public function changePlan(
        WorkspaceSubscription $subscription,
        PricingPlan $plan,
        Admin $admin,
        BillingInterval $interval,
        int $expectedVersion,
    ): WorkspaceSubscription {
        $this->ensureBillable($plan, $interval);

        return DB::transaction(function () use ($admin, $expectedVersion, $interval, $plan, $subscription): WorkspaceSubscription {
            $locked = WorkspaceSubscription::query()->lockForUpdate()->findOrFail($subscription->getKey());
            $this->ensureVersion($locked, $expectedVersion);
            $previousPlanId = $locked->pricing_plan_id;
            $locked->update([
                'pricing_plan_id' => $plan->getKey(),
                'billing_interval' => $interval,
                'currency' => strtoupper($plan->billing_currency),
                'price_minor' => $this->priceFor($plan, $interval),
                'entitlement_snapshot' => $this->snapshot($plan, $interval),
                'lock_version' => $locked->lock_version + 1,
            ]);
            $this->record($locked, 'plan_changed', $locked->status, $locked->status, null, $admin, [
                'previous_pricing_plan_id' => $previousPlanId,
                'pricing_plan_id' => $plan->getKey(),
                'billing_version' => $plan->billing_version,
            ]);

            return $locked->refresh();
        }, 3);
    }

    public function requestPlanChange(
        WorkspaceSubscription $subscription,
        PricingPlan $plan,
        User $user,
        BillingInterval $interval,
    ): void {
        $this->ensureBillable($plan, $interval);

        DB::transaction(function () use ($interval, $plan, $subscription, $user): void {
            $locked = WorkspaceSubscription::query()->lockForUpdate()->findOrFail($subscription->getKey());
            $this->record($locked, 'plan_change_requested', $locked->status, $locked->status, $user, null, [
                'requested_pricing_plan_id' => $plan->getKey(),
                'requested_billing_interval' => $interval->value,
            ]);
        });
    }

    public function requestCancellation(WorkspaceSubscription $subscription, User $user, string $reason): void
    {
        DB::transaction(function () use ($reason, $subscription, $user): void {
            $locked = WorkspaceSubscription::query()->lockForUpdate()->findOrFail($subscription->getKey());
            $this->record($locked, 'cancellation_requested', $locked->status, $locked->status, $user, null, [
                'reason' => $reason,
            ]);
        });
    }

    public function cancel(
        WorkspaceSubscription $subscription,
        Admin $admin,
        int $expectedVersion,
        bool $atPeriodEnd,
    ): WorkspaceSubscription {
        return DB::transaction(function () use ($admin, $atPeriodEnd, $expectedVersion, $subscription): WorkspaceSubscription {
            $locked = WorkspaceSubscription::query()->lockForUpdate()->findOrFail($subscription->getKey());
            $this->ensureVersion($locked, $expectedVersion);
            $from = $locked->status;

            if ($atPeriodEnd) {
                if ($locked->current_period_ends_at === null) {
                    throw ValidationException::withMessages(['cancel_at_period_end' => 'This subscription has no billing-period end.']);
                }

                $locked->update([
                    'cancel_at_period_end' => true,
                    'cancelled_at' => now(),
                    'renews_at' => null,
                    'lock_version' => $locked->lock_version + 1,
                ]);
                $action = 'cancellation_scheduled';
            } else {
                $locked->update([
                    'status' => SubscriptionStatus::Cancelled,
                    'cancel_at_period_end' => false,
                    'cancelled_at' => now(),
                    'ended_at' => now(),
                    'renews_at' => null,
                    'lock_version' => $locked->lock_version + 1,
                ]);
                $action = 'cancelled_immediately';
            }

            $this->record($locked, $action, $from, $locked->status, null, $admin);

            return $locked->refresh();
        }, 3);
    }

    public function activateFromPayment(PaymentTransaction $payment): WorkspaceSubscription
    {
        return DB::transaction(function () use ($payment): WorkspaceSubscription {
            $lockedPayment = PaymentTransaction::query()->lockForUpdate()->findOrFail($payment->getKey());
            $invoice = $lockedPayment->invoice()->lockForUpdate()->firstOrFail();

            if ($lockedPayment->status !== PaymentStatus::Succeeded
                || $lockedPayment->reconciliation_status !== ReconciliationStatus::Matched
                || $invoice->status !== InvoiceStatus::Paid
                || $invoice->workspace_subscription_id !== $lockedPayment->workspace_subscription_id
                || $invoice->workspace_id !== $lockedPayment->workspace_id
                || $invoice->currency !== $lockedPayment->currency
                || $invoice->total_minor !== $lockedPayment->amount_minor) {
                throw ValidationException::withMessages(['payment' => 'Only a reconciled successful payment can activate a subscription.']);
            }

            $locked = WorkspaceSubscription::query()->lockForUpdate()->findOrFail($lockedPayment->workspace_subscription_id);
            $from = $locked->status;

            if (! in_array($from, [SubscriptionStatus::PendingActivation, SubscriptionStatus::PastDue], true)) {
                return $locked;
            }

            $changes = [
                'status' => SubscriptionStatus::Active,
                'activation_source' => 'verified_payment',
                'lock_version' => $locked->lock_version + 1,
            ];
            $action = 'payment_activated';

            if ($from === SubscriptionStatus::PastDue
                && $locked->current_period_ends_at !== null
                && $locked->current_period_ends_at->isPast()) {
                $periodStartsAt = CarbonImmutable::instance($locked->current_period_ends_at);
                $periodEndsAt = $this->periodEnd($periodStartsAt, $locked->billing_interval);
                $changes += [
                    'current_period_starts_at' => $periodStartsAt,
                    'current_period_ends_at' => $periodEndsAt,
                    'renews_at' => $locked->billing_interval === BillingInterval::Manual ? null : $periodEndsAt,
                    'ended_at' => null,
                ];
                $action = 'payment_renewed';
            }

            $locked->update($changes);
            $this->record($locked, $action, $from, SubscriptionStatus::Active, null, null, [
                'payment_transaction_id' => $lockedPayment->getKey(),
            ]);

            return $locked->refresh();
        }, 3);
    }

    public function activateCheckoutFromPayment(PaymentTransaction $payment): WorkspaceSubscription
    {
        return DB::transaction(function () use ($payment): WorkspaceSubscription {
            $lockedPayment = PaymentTransaction::query()->lockForUpdate()->findOrFail($payment->getKey());
            $invoice = $lockedPayment->invoice()->lockForUpdate()->firstOrFail();
            $checkout = data_get($invoice->metadata, 'checkout');

            if (! is_array($checkout)
                || $lockedPayment->status !== PaymentStatus::Succeeded
                || $lockedPayment->reconciliation_status !== ReconciliationStatus::Matched
                || $invoice->status !== InvoiceStatus::Paid
                || $invoice->workspace_subscription_id !== $lockedPayment->workspace_subscription_id
                || $invoice->workspace_id !== $lockedPayment->workspace_id
                || $invoice->currency !== $lockedPayment->currency
                || $invoice->total_minor !== $lockedPayment->amount_minor) {
                throw ValidationException::withMessages(['payment' => 'Only a verified checkout payment can activate a paid subscription.']);
            }

            $alreadyActivated = SubscriptionHistory::query()
                ->where('workspace_subscription_id', $lockedPayment->workspace_subscription_id)
                ->whereIn('action', ['payment_checkout_activated', 'payment_checkout_plan_changed', 'payment_checkout_renewed'])
                ->where('context->payment_transaction_id', $lockedPayment->getKey())
                ->exists();
            $subscription = WorkspaceSubscription::query()->lockForUpdate()->findOrFail($lockedPayment->workspace_subscription_id);

            if ($alreadyActivated) {
                return $subscription;
            }

            $plan = PricingPlan::query()->lockForUpdate()->findOrFail((int) ($checkout['pricing_plan_id'] ?? 0));
            $interval = BillingInterval::tryFrom((string) ($checkout['billing_interval'] ?? ''));
            $expectedVersion = (int) ($checkout['subscription_lock_version'] ?? -1);

            if ($subscription->workspace_id !== $lockedPayment->workspace_id
                || $subscription->lock_version !== $expectedVersion
                || $subscription->status === SubscriptionStatus::Cancelled
                || $interval === null
                || $interval === BillingInterval::Manual
                || ! $plan->is_active
                || ! $plan->is_billable
                || strtoupper($plan->billing_currency) !== $lockedPayment->currency
                || $this->priceFor($plan, $interval) !== $lockedPayment->amount_minor) {
                throw ValidationException::withMessages(['payment' => 'The paid plan or subscription changed before payment confirmation.']);
            }

            $from = $subscription->status;
            $previousPlanId = $subscription->pricing_plan_id;
            $startsAt = $from === SubscriptionStatus::PastDue
                && $subscription->current_period_ends_at !== null
                && $subscription->current_period_ends_at->isPast()
                    ? CarbonImmutable::instance($subscription->current_period_ends_at)
                    : CarbonImmutable::now('UTC')->startOfSecond();
            $endsAt = $this->periodEnd($startsAt, $interval);
            $subscription->update([
                'pricing_plan_id' => $plan->getKey(),
                'status' => SubscriptionStatus::Active,
                'billing_interval' => $interval,
                'currency' => strtoupper($plan->billing_currency),
                'price_minor' => $lockedPayment->amount_minor,
                'entitlement_snapshot' => $this->snapshot($plan, $interval),
                'activation_source' => 'verified_payment',
                'lock_version' => $subscription->lock_version + 1,
                'starts_at' => $startsAt,
                'current_period_starts_at' => $startsAt,
                'current_period_ends_at' => $endsAt,
                'renews_at' => $endsAt,
                'trial_ends_at' => null,
                'cancel_at_period_end' => false,
                'cancelled_at' => null,
                'ended_at' => null,
            ]);
            $action = $previousPlanId !== $plan->getKey()
                ? 'payment_checkout_plan_changed'
                : ($from === SubscriptionStatus::PastDue ? 'payment_checkout_renewed' : 'payment_checkout_activated');
            $this->record($subscription, $action, $from, SubscriptionStatus::Active, null, null, [
                'payment_transaction_id' => $lockedPayment->getKey(),
                'previous_pricing_plan_id' => $previousPlanId,
                'pricing_plan_id' => $plan->getKey(),
                'billing_interval' => $interval->value,
            ]);

            return $subscription->refresh();
        }, 3);
    }

    public function suspendPaidAccess(PaymentTransaction $payment, string $action): WorkspaceSubscription
    {
        return DB::transaction(function () use ($action, $payment): WorkspaceSubscription {
            $lockedPayment = PaymentTransaction::query()->lockForUpdate()->findOrFail($payment->getKey());
            $subscription = WorkspaceSubscription::query()->lockForUpdate()->findOrFail($lockedPayment->workspace_subscription_id);
            $latestActivation = $subscription->histories()
                ->whereIn('action', ['payment_checkout_activated', 'payment_checkout_plan_changed', 'payment_checkout_renewed'])
                ->first();

            if ((int) data_get($latestActivation?->context, 'payment_transaction_id') !== $lockedPayment->getKey()
                || $subscription->status !== SubscriptionStatus::Active) {
                return $subscription;
            }

            $subscription->update([
                'status' => SubscriptionStatus::PastDue,
                'renews_at' => null,
                'lock_version' => $subscription->lock_version + 1,
            ]);
            $this->record($subscription, $action, SubscriptionStatus::Active, SubscriptionStatus::PastDue, null, null, [
                'payment_transaction_id' => $lockedPayment->getKey(),
            ]);

            return $subscription->refresh();
        }, 3);
    }

    public function processDuePeriod(int $subscriptionId, CarbonImmutable $asOf): ?string
    {
        return DB::transaction(function () use ($asOf, $subscriptionId): ?string {
            $subscription = WorkspaceSubscription::query()->lockForUpdate()->findOrFail($subscriptionId);

            if (! in_array($subscription->status, [
                SubscriptionStatus::Active,
                SubscriptionStatus::Trialing,
                SubscriptionStatus::PastDue,
            ], true)) {
                return null;
            }

            $periodEnded = $subscription->current_period_ends_at !== null
                && ! $subscription->current_period_ends_at->isAfter($asOf);
            $trialEnded = $subscription->status === SubscriptionStatus::Trialing
                && $subscription->trial_ends_at !== null
                && ! $subscription->trial_ends_at->isAfter($asOf);

            if (! $periodEnded && ! $trialEnded) {
                return null;
            }

            $from = $subscription->status;

            if ($periodEnded && $subscription->cancel_at_period_end) {
                $subscription->update([
                    'status' => SubscriptionStatus::Cancelled,
                    'cancel_at_period_end' => false,
                    'ended_at' => $subscription->current_period_ends_at,
                    'renews_at' => null,
                    'lock_version' => $subscription->lock_version + 1,
                ]);
                $this->record($subscription, 'period_end_cancelled', $from, SubscriptionStatus::Cancelled, null, null);

                return 'cancelled';
            }

            if ($trialEnded || $subscription->billing_interval === BillingInterval::Manual) {
                return $this->expire($subscription, $from, $asOf, $trialEnded ? 'trial_expired' : 'manual_period_expired');
            }

            if ($from === SubscriptionStatus::Active
                && $subscription->price_minor === 0
                && in_array($subscription->activation_source, ['admin_grant', 'complimentary_free'], true)) {
                return $this->advanceComplimentaryPeriod($subscription, $asOf);
            }

            if ($from === SubscriptionStatus::Active) {
                $subscription->update([
                    'status' => SubscriptionStatus::PastDue,
                    'renews_at' => null,
                    'lock_version' => $subscription->lock_version + 1,
                ]);
                $this->record($subscription, 'payment_became_due', $from, SubscriptionStatus::PastDue, null, null);

                return 'past_due';
            }

            $graceDays = max(0, (int) config('subscriptions.past_due_grace_days', 7));
            $expiresAt = CarbonImmutable::instance($subscription->current_period_ends_at)->addDays($graceDays);

            if ($expiresAt->isAfter($asOf)) {
                return null;
            }

            return $this->expire($subscription, $from, $asOf, 'past_due_expired');
        }, 3);
    }

    /** @return array<string, mixed> */
    public function snapshot(PricingPlan $plan, BillingInterval $interval): array
    {
        $plan->loadMissing('entitlements');

        return [
            'plan' => [
                'id' => $plan->getKey(),
                'code' => $plan->code,
                'name' => $plan->name,
                'version' => $plan->billing_version,
                'currency' => strtoupper($plan->billing_currency),
                'billing_interval' => $interval->value,
                'price_minor' => $this->priceFor($plan, $interval),
            ],
            'entitlements' => $plan->entitlements->mapWithKeys(fn ($entitlement): array => [
                $entitlement->key => [
                    'enabled' => $entitlement->is_enabled,
                    'limit' => $entitlement->limit,
                    'unlimited' => $entitlement->is_unlimited,
                    'unit' => $entitlement->unit,
                ],
            ])->all(),
        ];
    }

    private function advanceComplimentaryPeriod(
        WorkspaceSubscription $subscription,
        CarbonImmutable $asOf,
    ): string {
        $previousPeriodStartsAt = CarbonImmutable::instance($subscription->current_period_starts_at);
        $previousPeriodEndsAt = CarbonImmutable::instance($subscription->current_period_ends_at);
        $periodStartsAt = $previousPeriodEndsAt;
        $periodEndsAt = $this->periodEnd($periodStartsAt, $subscription->billing_interval);
        $advancedPeriods = 1;

        while (! $periodEndsAt->isAfter($asOf) && $advancedPeriods < 1200) {
            $periodStartsAt = $periodEndsAt;
            $periodEndsAt = $this->periodEnd($periodStartsAt, $subscription->billing_interval);
            $advancedPeriods++;
        }

        if (! $periodEndsAt->isAfter($asOf)) {
            throw new RuntimeException('Subscription period advancement exceeded the safety bound.');
        }

        $subscription->update([
            'current_period_starts_at' => $periodStartsAt,
            'current_period_ends_at' => $periodEndsAt,
            'renews_at' => $periodEndsAt,
            'lock_version' => $subscription->lock_version + 1,
        ]);
        $this->record($subscription, 'period_advanced', $subscription->status, $subscription->status, null, null, [
            'previous_period_starts_at' => $previousPeriodStartsAt->toIso8601String(),
            'previous_period_ends_at' => $previousPeriodEndsAt->toIso8601String(),
            'period_starts_at' => $periodStartsAt->toIso8601String(),
            'period_ends_at' => $periodEndsAt->toIso8601String(),
            'advanced_periods' => $advancedPeriods,
        ]);

        return 'advanced';
    }

    private function expire(
        WorkspaceSubscription $subscription,
        SubscriptionStatus $from,
        CarbonImmutable $asOf,
        string $action,
    ): string {
        $subscription->update([
            'status' => SubscriptionStatus::Expired,
            'ended_at' => $asOf,
            'renews_at' => null,
            'lock_version' => $subscription->lock_version + 1,
        ]);
        $this->record($subscription, $action, $from, SubscriptionStatus::Expired, null, null);

        return 'expired';
    }

    private function ensureBillable(PricingPlan $plan, BillingInterval $interval): void
    {
        if (! $plan->is_active || ! $plan->is_billable) {
            throw ValidationException::withMessages(['pricing_plan_id' => 'This pricing plan is not enabled for subscriptions.']);
        }

        if ($interval !== BillingInterval::Manual && $this->configuredPriceFor($plan, $interval) === null) {
            throw ValidationException::withMessages(['billing_interval' => 'This plan has no configured price for the selected interval.']);
        }
    }

    private function priceFor(PricingPlan $plan, BillingInterval $interval): int
    {
        return (int) ($this->configuredPriceFor($plan, $interval) ?? 0);
    }

    private function configuredPriceFor(PricingPlan $plan, BillingInterval $interval): ?int
    {
        return match ($interval) {
            BillingInterval::Monthly => $plan->monthly_price_minor,
            BillingInterval::Annual => $plan->annual_price_minor,
            BillingInterval::Manual => 0,
        };
    }

    private function periodEnd(CarbonImmutable $startsAt, BillingInterval $interval): CarbonImmutable
    {
        return match ($interval) {
            BillingInterval::Annual => $startsAt->addYear(),
            BillingInterval::Monthly, BillingInterval::Manual => $startsAt->addMonthNoOverflow(),
        };
    }

    private function ensureVersion(WorkspaceSubscription $subscription, int $expectedVersion): void
    {
        if ($subscription->lock_version !== $expectedVersion) {
            throw ValidationException::withMessages(['lock_version' => 'The subscription changed. Refresh and review the latest state.']);
        }
    }

    /** @param array<string, mixed> $context */
    private function record(
        WorkspaceSubscription $subscription,
        string $action,
        ?SubscriptionStatus $from,
        SubscriptionStatus $to,
        ?User $user,
        ?Admin $admin,
        array $context = [],
    ): void {
        SubscriptionHistory::query()->create([
            'workspace_subscription_id' => $subscription->getKey(),
            'workspace_id' => $subscription->workspace_id,
            'actor_user_id' => $user?->getKey(),
            'actor_admin_id' => $admin?->getKey(),
            'action' => $action,
            'from_status' => $from?->value,
            'to_status' => $to->value,
            'lock_version' => $subscription->lock_version,
            'context' => $context,
        ]);
    }
}
