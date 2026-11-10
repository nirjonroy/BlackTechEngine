<?php

namespace App\Models;

use App\Enums\PaymentStatus;
use Database\Factories\PaymentWebhookEventFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'payment_transaction_id', 'provider', 'provider_event_id', 'provider_reference',
    'event_type', 'payment_status', 'processing_status', 'payload_hash', 'failure_code', 'processed_at',
])]
class PaymentWebhookEvent extends Model
{
    /** @use HasFactory<PaymentWebhookEventFactory> */
    use HasFactory;

    protected $attributes = ['processing_status' => 'received'];

    public function payment(): BelongsTo
    {
        return $this->belongsTo(PaymentTransaction::class, 'payment_transaction_id');
    }

    protected function casts(): array
    {
        return [
            'payment_status' => PaymentStatus::class,
            'processed_at' => 'datetime',
        ];
    }
}
