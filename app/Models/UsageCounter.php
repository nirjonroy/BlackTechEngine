<?php

namespace App\Models;

use Database\Factories\UsageCounterFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'workspace_subscription_id', 'workspace_id', 'entitlement_key', 'period_starts_at',
    'period_ends_at', 'used', 'reserved', 'manual_adjustment', 'lock_version',
])]
class UsageCounter extends Model
{
    /** @use HasFactory<UsageCounterFactory> */
    use HasFactory;

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(WorkspaceSubscription::class, 'workspace_subscription_id');
    }

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(UsageEvent::class);
    }

    public function consumed(): int
    {
        return max(0, $this->used + $this->reserved + $this->manual_adjustment);
    }

    protected function casts(): array
    {
        return [
            'period_starts_at' => 'datetime',
            'period_ends_at' => 'datetime',
            'used' => 'integer',
            'reserved' => 'integer',
            'manual_adjustment' => 'integer',
            'lock_version' => 'integer',
        ];
    }
}
