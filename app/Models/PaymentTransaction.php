<?php

namespace App\Models;

use App\Enums\PaymentStatus;
use App\Enums\PaymentType;
use App\Enums\ReconciliationStatus;
use Database\Factories\PaymentTransactionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'billing_invoice_id', 'workspace_subscription_id', 'workspace_id', 'provider',
    'provider_reference', 'provider_event_id', 'idempotency_key', 'type', 'status',
    'reconciliation_status', 'currency', 'amount_minor', 'failure_code', 'metadata', 'processed_at',
])]
class PaymentTransaction extends Model
{
    /** @use HasFactory<PaymentTransactionFactory> */
    use HasFactory;

    protected $attributes = [
        'type' => 'charge',
        'status' => 'pending',
        'reconciliation_status' => 'unreconciled',
    ];

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(BillingInvoice::class, 'billing_invoice_id');
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(WorkspaceSubscription::class, 'workspace_subscription_id');
    }

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    protected function casts(): array
    {
        return [
            'type' => PaymentType::class,
            'status' => PaymentStatus::class,
            'reconciliation_status' => ReconciliationStatus::class,
            'amount_minor' => 'integer',
            'metadata' => 'array',
            'processed_at' => 'datetime',
        ];
    }
}
