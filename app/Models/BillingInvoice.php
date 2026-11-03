<?php

namespace App\Models;

use App\Enums\InvoiceStatus;
use Database\Factories\BillingInvoiceFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'workspace_subscription_id', 'workspace_id', 'number', 'status', 'currency',
    'subtotal_minor', 'discount_minor', 'tax_minor', 'total_minor', 'refunded_minor',
    'period_starts_at', 'period_ends_at', 'due_at', 'paid_at', 'voided_at', 'metadata',
])]
class BillingInvoice extends Model
{
    /** @use HasFactory<BillingInvoiceFactory> */
    use HasFactory;

    protected $attributes = [
        'status' => 'draft',
        'subtotal_minor' => 0,
        'discount_minor' => 0,
        'tax_minor' => 0,
        'total_minor' => 0,
        'refunded_minor' => 0,
    ];

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(WorkspaceSubscription::class, 'workspace_subscription_id');
    }

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(BillingInvoiceItem::class)->orderBy('id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(PaymentTransaction::class)->latest('id');
    }

    protected function casts(): array
    {
        return [
            'status' => InvoiceStatus::class,
            'subtotal_minor' => 'integer',
            'discount_minor' => 'integer',
            'tax_minor' => 'integer',
            'total_minor' => 'integer',
            'refunded_minor' => 'integer',
            'period_starts_at' => 'datetime',
            'period_ends_at' => 'datetime',
            'due_at' => 'datetime',
            'paid_at' => 'datetime',
            'voided_at' => 'datetime',
            'metadata' => 'array',
        ];
    }
}
