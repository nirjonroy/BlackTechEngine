<?php

namespace App\Models;

use App\Enums\UsageEventStatus;
use Database\Factories\UsageEventFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'usage_counter_id', 'workspace_subscription_id', 'workspace_id', 'actor_user_id',
    'actor_admin_id', 'entitlement_key', 'idempotency_key', 'quantity', 'status',
    'reference_type', 'reference_id', 'metadata', 'expires_at', 'committed_at', 'released_at',
])]
class UsageEvent extends Model
{
    /** @use HasFactory<UsageEventFactory> */
    use HasFactory;

    protected $attributes = ['status' => 'reserved'];

    public function counter(): BelongsTo
    {
        return $this->belongsTo(UsageCounter::class, 'usage_counter_id');
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
            'quantity' => 'integer',
            'status' => UsageEventStatus::class,
            'metadata' => 'array',
            'expires_at' => 'datetime',
            'committed_at' => 'datetime',
            'released_at' => 'datetime',
        ];
    }
}
