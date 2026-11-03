<?php

namespace App\Models;

use Database\Factories\BillingInvoiceItemFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['billing_invoice_id', 'description', 'quantity', 'unit_amount_minor', 'total_amount_minor', 'metadata'])]
class BillingInvoiceItem extends Model
{
    /** @use HasFactory<BillingInvoiceItemFactory> */
    use HasFactory;

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(BillingInvoice::class, 'billing_invoice_id');
    }

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'unit_amount_minor' => 'integer',
            'total_amount_minor' => 'integer',
            'metadata' => 'array',
        ];
    }
}
