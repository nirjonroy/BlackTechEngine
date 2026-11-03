<?php

namespace App\Models;

use App\Enums\BillingInterval;
use App\Enums\SubscriptionStatus;
use Database\Factories\WorkspaceSubscriptionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'workspace_id', 'pricing_plan_id', 'created_by_admin_id', 'status', 'billing_interval',
    'currency', 'price_minor', 'entitlement_snapshot', 'activation_source', 'external_reference',
    'lock_version', 'starts_at', 'current_period_starts_at', 'current_period_ends_at', 'renews_at',
    'trial_ends_at', 'cancel_at_period_end', 'cancelled_at', 'ended_at',
])]
class WorkspaceSubscription extends Model
{
    /** @use HasFactory<WorkspaceSubscriptionFactory> */
    use HasFactory;

    protected $attributes = [
        'status' => 'pending_activation',
        'billing_interval' => 'manual',
        'price_minor' => 0,
        'lock_version' => 0,
        'cancel_at_period_end' => false,
    ];

    /** @return BelongsTo<Workspace, $this> */
    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    /** @return BelongsTo<PricingPlan, $this> */
    public function plan(): BelongsTo
    {
        return $this->belongsTo(PricingPlan::class, 'pricing_plan_id');
    }

    /** @return BelongsTo<Admin, $this> */
    public function createdByAdmin(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'created_by_admin_id');
    }

    /** @return HasMany<SubscriptionHistory, $this> */
    public function histories(): HasMany
    {
        return $this->hasMany(SubscriptionHistory::class)->latest('id');
    }

    /** @return HasMany<UsageCounter, $this> */
    public function usageCounters(): HasMany
    {
        return $this->hasMany(UsageCounter::class);
    }

    /** @return HasMany<BillingInvoice, $this> */
    public function invoices(): HasMany
    {
        return $this->hasMany(BillingInvoice::class)->latest('id');
    }

    public function scopeEntitled(Builder $query): Builder
    {
        return $query->whereIn('status', [SubscriptionStatus::Trialing->value, SubscriptionStatus::Active->value]);
    }

    public function isEntitled(): bool
    {
        return $this->status->isEntitled()
            && ($this->ended_at === null || $this->ended_at->isFuture())
            && ($this->current_period_ends_at === null || $this->current_period_ends_at->isFuture());
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'status' => SubscriptionStatus::class,
            'billing_interval' => BillingInterval::class,
            'price_minor' => 'integer',
            'entitlement_snapshot' => 'array',
            'lock_version' => 'integer',
            'starts_at' => 'datetime',
            'current_period_starts_at' => 'datetime',
            'current_period_ends_at' => 'datetime',
            'renews_at' => 'datetime',
            'trial_ends_at' => 'datetime',
            'cancel_at_period_end' => 'boolean',
            'cancelled_at' => 'datetime',
            'ended_at' => 'datetime',
        ];
    }
}
