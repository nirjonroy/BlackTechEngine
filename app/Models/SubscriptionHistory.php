<?php

namespace App\Models;

use Database\Factories\SubscriptionHistoryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'workspace_subscription_id', 'workspace_id', 'actor_user_id', 'actor_admin_id',
    'action', 'from_status', 'to_status', 'lock_version', 'context',
])]
class SubscriptionHistory extends Model
{
    /** @use HasFactory<SubscriptionHistoryFactory> */
    use HasFactory;

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(WorkspaceSubscription::class, 'workspace_subscription_id');
    }

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    public function actorUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }

    public function actorAdmin(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'actor_admin_id');
    }

    protected function casts(): array
    {
        return ['lock_version' => 'integer', 'context' => 'array'];
    }
}
