<?php

namespace App\Services\Subscriptions;

use App\Enums\InvoiceStatus;
use App\Enums\PaymentStatus;
use App\Enums\PaymentType;
use App\Enums\ReconciliationStatus;
use App\Models\BillingInvoice;
use App\Models\PaymentTransaction;
use App\Models\WorkspaceSubscription;
use App\Services\Billing\PaymentResult;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class InvoiceService
{
    public function __construct(private readonly SubscriptionManager $subscriptions) {}

    /**
     * @param  list<array{description: string, quantity: int, unit_amount_minor: int, metadata?: array<string, mixed>}>  $items
     * @param  array<string, mixed>  $metadata
     */
    public function create(WorkspaceSubscription $subscription, array $items, array $metadata = []): BillingInvoice
    {
        if ($items === []) {
            throw ValidationException::withMessages(['items' => 'An invoice requires at least one item.']);
        }

        $normalized = collect($items)->map(function (array $item): array {
            $quantity = (int) $item['quantity'];
            $unitAmount = (int) $item['unit_amount_minor'];

            if ($quantity < 1 || $unitAmount < 0) {
                throw ValidationException::withMessages(['items' => 'Invoice quantities and amounts must be valid non-negative integers.']);
            }

            return [
                'description' => $item['description'],
                'quantity' => $quantity,
                'unit_amount_minor' => $unitAmount,
                'total_amount_minor' => $quantity * $unitAmount,
                'metadata' => $item['metadata'] ?? null,
            ];
        });
        $subtotal = (int) $normalized->sum('total_amount_minor');

        return DB::transaction(function () use ($metadata, $normalized, $subtotal, $subscription): BillingInvoice {
            $locked = WorkspaceSubscription::query()->lockForUpdate()->findOrFail($subscription->getKey());
            $invoice = BillingInvoice::query()->create([
                'workspace_subscription_id' => $locked->getKey(),
                'workspace_id' => $locked->workspace_id,
                'number' => 'INV-'.now('UTC')->format('Ym').'-'.Str::upper(Str::random(10)),
                'status' => InvoiceStatus::Open,
                'currency' => $locked->currency,
                'subtotal_minor' => $subtotal,
                'total_minor' => $subtotal,
                'period_starts_at' => $locked->current_period_starts_at,
                'period_ends_at' => $locked->current_period_ends_at,
                'due_at' => now('UTC')->addDays((int) config('subscriptions.invoice_due_days', 7)),
                'metadata' => $metadata === [] ? null : $metadata,
            ]);
            $invoice->items()->createMany($normalized->all());

            return $invoice->load('items');
        }, 3);
    }

    public function recordPayment(BillingInvoice $invoice, PaymentResult $result, string $idempotencyKey): PaymentTransaction
    {
        if (strtoupper($result->currency) !== $invoice->currency || $result->amountMinor !== $invoice->total_minor) {
            throw ValidationException::withMessages(['payment' => 'The verified payment amount or currency does not match the invoice.']);
        }

        $payment = DB::transaction(function () use ($idempotencyKey, $invoice, $result): PaymentTransaction {
            $lockedInvoice = BillingInvoice::query()->lockForUpdate()->findOrFail($invoice->getKey());
            $existing = PaymentTransaction::query()->where('idempotency_key', $idempotencyKey)->first();

            if ($existing !== null) {
                $this->ensurePaymentReplayMatches($existing, $lockedInvoice, $result);

                return $existing;
            }

            if ($result->providerEventId !== null) {
                $event = PaymentTransaction::query()
                    ->where('provider', $result->provider)
                    ->where('provider_event_id', $result->providerEventId)
                    ->first();

                if ($event !== null) {
                    $this->ensurePaymentReplayMatches($event, $lockedInvoice, $result);

                    return $event;
                }
            }

            $payment = PaymentTransaction::query()->create([
                'billing_invoice_id' => $lockedInvoice->getKey(),
                'workspace_subscription_id' => $lockedInvoice->workspace_subscription_id,
                'workspace_id' => $lockedInvoice->workspace_id,
                'provider' => $result->provider,
                'provider_reference' => $result->providerReference,
                'provider_event_id' => $result->providerEventId,
                'idempotency_key' => $idempotencyKey,
                'type' => PaymentType::Charge,
                'status' => $result->status,
                'reconciliation_status' => $result->status === PaymentStatus::Succeeded
                    ? ReconciliationStatus::Matched
                    : ReconciliationStatus::Unreconciled,
                'currency' => strtoupper($result->currency),
                'amount_minor' => $result->amountMinor,
                'failure_code' => $result->failureCode,
                'processed_at' => now('UTC'),
            ]);

            if ($result->status === PaymentStatus::Succeeded) {
                $lockedInvoice->update(['status' => InvoiceStatus::Paid, 'paid_at' => now('UTC')]);
            }

            return $payment;
        }, 3);

        if ($payment->status === PaymentStatus::Succeeded) {
            $this->subscriptions->activateFromPayment($payment);
        }

        return $payment;
    }

    private function ensurePaymentReplayMatches(
        PaymentTransaction $payment,
        BillingInvoice $invoice,
        PaymentResult $result,
    ): void {
        if ($payment->billing_invoice_id !== $invoice->getKey()
            || $payment->amount_minor !== $result->amountMinor
            || $payment->currency !== strtoupper($result->currency)) {
            throw ValidationException::withMessages(['payment' => 'The payment identifier was already used for a different invoice or amount.']);
        }
    }
}
