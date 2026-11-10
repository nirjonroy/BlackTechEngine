<?php

namespace App\Services\Billing;

use App\Enums\BillingInterval;
use App\Enums\PaymentStatus;
use App\Enums\PaymentType;
use App\Enums\ReconciliationStatus;
use App\Enums\SubscriptionStatus;
use App\Models\PaymentTransaction;
use App\Models\PricingPlan;
use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceSubscription;
use App\Services\Billing\Contracts\PaymentGateway;
use App\Services\Subscriptions\InvoiceService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CheckoutService
{
    public function __construct(
        private readonly PaymentGateway $gateway,
        private readonly InvoiceService $invoices,
    ) {}

    public function initiate(
        User $user,
        Workspace $workspace,
        PricingPlan $plan,
        BillingInterval $interval,
        string $idempotencyKey,
    ): CheckoutSession {
        $this->validateCheckout($user, $workspace, $plan, $interval);

        $payment = DB::transaction(function () use ($idempotencyKey, $interval, $plan, $user, $workspace): PaymentTransaction {
            $subscription = WorkspaceSubscription::query()
                ->whereBelongsTo($workspace)
                ->lockForUpdate()
                ->firstOrFail();
            $existing = PaymentTransaction::query()
                ->where('idempotency_key', $idempotencyKey)
                ->lockForUpdate()
                ->first();

            if ($existing !== null) {
                $this->ensureCheckoutReplayMatches($existing, $workspace, $plan, $interval, $user);

                if ($existing->status !== PaymentStatus::Pending) {
                    throw ValidationException::withMessages(['idempotency_key' => 'This checkout has already been finalized.']);
                }

                return $existing;
            }

            if ($subscription->status === SubscriptionStatus::Active
                && $subscription->pricing_plan_id === $plan->getKey()
                && $subscription->billing_interval === $interval) {
                throw ValidationException::withMessages(['pricing_plan_id' => 'This plan and billing interval are already active.']);
            }

            $priceMinor = $this->priceFor($plan, $interval);
            $checkout = [
                'requested_by_user_id' => $user->getKey(),
                'pricing_plan_id' => $plan->getKey(),
                'billing_interval' => $interval->value,
                'subscription_lock_version' => $subscription->lock_version,
            ];
            $invoice = $this->invoices->create($subscription, [[
                'description' => $plan->name.' ('.$interval->value.')',
                'quantity' => 1,
                'unit_amount_minor' => $priceMinor,
            ]], ['checkout' => $checkout]);

            return PaymentTransaction::query()->create([
                'billing_invoice_id' => $invoice->getKey(),
                'workspace_subscription_id' => $subscription->getKey(),
                'workspace_id' => $workspace->getKey(),
                'provider' => $this->gateway->name(),
                'idempotency_key' => $idempotencyKey,
                'type' => PaymentType::Charge,
                'status' => PaymentStatus::Pending,
                'reconciliation_status' => ReconciliationStatus::Unreconciled,
                'currency' => strtoupper($plan->billing_currency),
                'amount_minor' => $priceMinor,
                'metadata' => ['checkout' => $checkout],
            ]);
        }, 3);

        $invoice = $payment->invoice()->firstOrFail();
        $returnUrl = route('subscriptions.invoices.show', [$workspace, $invoice]);
        $session = $this->gateway->initiateCheckout(
            $invoice,
            $idempotencyKey,
            $returnUrl,
            $returnUrl,
        );

        if ($session->provider !== $this->gateway->name()
            || trim($session->providerReference) === ''
            || ! $this->gateway->isTrustedCheckoutUrl($session->checkoutUrl)) {
            throw ValidationException::withMessages(['payment' => 'The payment provider returned an invalid checkout session.']);
        }

        DB::transaction(function () use ($payment, $session): void {
            $locked = PaymentTransaction::query()->lockForUpdate()->findOrFail($payment->getKey());

            if ($locked->provider_reference !== null && $locked->provider_reference !== $session->providerReference) {
                throw ValidationException::withMessages(['payment' => 'The checkout request resolved to a different payment session.']);
            }

            $locked->update([
                'provider_reference' => $session->providerReference,
                'metadata' => array_merge($locked->metadata ?? [], [
                    'checkout_expires_at' => $session->expiresAt?->toIso8601String(),
                ]),
            ]);
        }, 3);

        return $session;
    }

    private function validateCheckout(
        User $user,
        Workspace $workspace,
        PricingPlan $plan,
        BillingInterval $interval,
    ): void {
        if (! config('subscriptions.checkout_enabled') || ! $this->gateway->isConfigured()) {
            throw ValidationException::withMessages(['payment' => 'Online checkout is not configured.']);
        }

        if (! $workspace->isOwnedBy($user)) {
            abort(404);
        }

        if (! $plan->is_active || ! $plan->is_billable || $this->priceFor($plan, $interval) < 1) {
            throw ValidationException::withMessages(['pricing_plan_id' => 'This paid plan is not available for the selected billing interval.']);
        }
    }

    private function priceFor(PricingPlan $plan, BillingInterval $interval): int
    {
        $price = match ($interval) {
            BillingInterval::Monthly => $plan->monthly_price_minor,
            BillingInterval::Annual => $plan->annual_price_minor,
            BillingInterval::Manual => null,
        };

        if ($price === null) {
            throw ValidationException::withMessages(['billing_interval' => 'This plan has no configured price for the selected interval.']);
        }

        return (int) $price;
    }

    private function ensureCheckoutReplayMatches(
        PaymentTransaction $payment,
        Workspace $workspace,
        PricingPlan $plan,
        BillingInterval $interval,
        User $user,
    ): void {
        if ($payment->workspace_id !== $workspace->getKey()
            || data_get($payment->metadata, 'checkout.requested_by_user_id') !== $user->getKey()
            || data_get($payment->metadata, 'checkout.pricing_plan_id') !== $plan->getKey()
            || data_get($payment->metadata, 'checkout.billing_interval') !== $interval->value) {
            throw ValidationException::withMessages(['idempotency_key' => 'This checkout identifier was already used for a different request.']);
        }
    }
}
