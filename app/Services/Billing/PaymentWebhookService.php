<?php

namespace App\Services\Billing;

use App\Enums\InvoiceStatus;
use App\Enums\PaymentStatus;
use App\Enums\PaymentType;
use App\Enums\ReconciliationStatus;
use App\Models\PaymentTransaction;
use App\Models\PaymentWebhookEvent;
use App\Services\Subscriptions\SubscriptionManager;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PaymentWebhookService
{
    public function __construct(private readonly SubscriptionManager $subscriptions) {}

    public function handle(PaymentEvent $event): PaymentWebhookEvent
    {
        if (trim($event->providerEventId) === ''
            || trim($event->providerReference) === ''
            || ! ctype_xdigit($event->payloadHash)
            || strlen($event->payloadHash) !== 64) {
            throw ValidationException::withMessages(['webhook' => 'The verified payment event is incomplete.']);
        }

        return DB::transaction(function () use ($event): PaymentWebhookEvent {
            $existing = PaymentWebhookEvent::query()
                ->where('provider', $event->provider)
                ->where('provider_event_id', $event->providerEventId)
                ->lockForUpdate()
                ->first();

            if ($existing !== null) {
                if ($existing->provider_reference !== $event->providerReference
                    || ! hash_equals($existing->payload_hash, $event->payloadHash)) {
                    throw ValidationException::withMessages(['webhook' => 'The payment event identifier was replayed with different data.']);
                }

                return $existing;
            }

            $payment = PaymentTransaction::query()
                ->where('provider', $event->provider)
                ->where(function ($query) use ($event): void {
                    $query->where('provider_reference', $event->providerReference);

                    if ($event->providerPaymentReference !== null) {
                        $query->orWhere('provider_payment_reference', $event->providerPaymentReference);
                    }
                })
                ->lockForUpdate()
                ->first();
            $webhook = PaymentWebhookEvent::query()->create([
                'payment_transaction_id' => $payment?->getKey(),
                'provider' => $event->provider,
                'provider_event_id' => $event->providerEventId,
                'provider_reference' => $event->providerReference,
                'event_type' => $event->type->value.'.'.$event->status->value,
                'payment_status' => $event->status,
                'payload_hash' => $event->payloadHash,
                'failure_code' => $event->failureCode,
            ]);

            if ($payment === null || ! $this->eventMatchesPayment($event, $payment)) {
                return $this->finish($webhook, 'rejected');
            }

            if (! $this->transitionAllowed($payment->status, $event)) {
                return $this->finish($webhook, 'rejected');
            }

            $invoice = $payment->invoice()->lockForUpdate()->firstOrFail();
            $payment->update([
                'provider_event_id' => $event->providerEventId,
                'type' => $event->type,
                'status' => $event->status,
                'reconciliation_status' => in_array($event->status, [
                    PaymentStatus::Succeeded,
                    PaymentStatus::Refunded,
                    PaymentStatus::Disputed,
                ], true) ? ReconciliationStatus::Matched : ReconciliationStatus::Unreconciled,
                'failure_code' => $event->failureCode,
                'provider_payment_reference' => $event->providerPaymentReference
                    ?? $payment->provider_payment_reference,
                'processed_at' => now('UTC'),
            ]);

            if ($event->status === PaymentStatus::Succeeded) {
                $invoice->update(['status' => InvoiceStatus::Paid, 'paid_at' => now('UTC')]);

                try {
                    $this->subscriptions->activateCheckoutFromPayment($payment->refresh());
                } catch (ValidationException) {
                    return $this->finish($webhook, 'requires_review');
                }
            } elseif ($event->status === PaymentStatus::Failed) {
                $invoice->update(['status' => InvoiceStatus::Uncollectible]);
            } elseif (in_array($event->status, [PaymentStatus::Cancelled, PaymentStatus::Expired], true)) {
                $invoice->update(['status' => InvoiceStatus::Void, 'voided_at' => now('UTC')]);
            } elseif ($event->status === PaymentStatus::Refunded) {
                $invoice->update(['refunded_minor' => $event->amountMinor]);
                $this->subscriptions->suspendPaidAccess($payment->refresh(), 'payment_refunded');
            } elseif ($event->status === PaymentStatus::Disputed) {
                $invoice->update(['status' => InvoiceStatus::Uncollectible]);
                $this->subscriptions->suspendPaidAccess($payment->refresh(), 'payment_disputed');
            }

            return $this->finish($webhook, 'processed');
        }, 3);
    }

    private function eventMatchesPayment(PaymentEvent $event, PaymentTransaction $payment): bool
    {
        $invoice = $payment->invoice;

        return $payment->amount_minor === $event->amountMinor
            && $payment->currency === strtoupper($event->currency)
            && $invoice !== null
            && $invoice->total_minor === $event->amountMinor
            && $invoice->currency === strtoupper($event->currency)
            && $payment->workspace_subscription_id === $invoice->workspace_subscription_id
            && $payment->workspace_id === $invoice->workspace_id;
    }

    private function transitionAllowed(PaymentStatus $current, PaymentEvent $event): bool
    {
        if ($current === $event->status) {
            return match ($current) {
                PaymentStatus::Refunded => $event->type === PaymentType::Refund,
                PaymentStatus::Disputed => $event->type === PaymentType::Chargeback,
                default => $event->type === PaymentType::Charge,
            };
        }

        if ($current === PaymentStatus::Pending && $event->type === PaymentType::Charge) {
            return in_array($event->status, [
                PaymentStatus::Succeeded,
                PaymentStatus::Failed,
                PaymentStatus::Cancelled,
                PaymentStatus::Expired,
            ], true);
        }

        if ($current === PaymentStatus::Succeeded) {
            return ($event->type === PaymentType::Refund && $event->status === PaymentStatus::Refunded)
                || ($event->type === PaymentType::Chargeback && $event->status === PaymentStatus::Disputed);
        }

        return false;
    }

    private function finish(PaymentWebhookEvent $webhook, string $status): PaymentWebhookEvent
    {
        $webhook->update(['processing_status' => $status, 'processed_at' => now('UTC')]);

        return $webhook->refresh();
    }
}
