<?php

namespace App\Models;

use App\Enums\AffiliateApplicationStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable(['user_id', 'status', 'submitted_at', 'reviewed_at', 'reviewed_by_admin_id', 'review_reason'])]
class AffiliateApplication extends Model
{
    protected $attributes = [
        'status' => 'pending',
    ];

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<Admin, $this> */
    public function reviewedByAdmin(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'reviewed_by_admin_id');
    }

    /** @return HasOne<Affiliate, $this> */
    public function affiliate(): HasOne
    {
        return $this->hasOne(Affiliate::class, 'application_id');
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', AffiliateApplicationStatus::Pending->value);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => AffiliateApplicationStatus::class,
            'submitted_at' => 'datetime',
            'reviewed_at' => 'datetime',
        ];
    }
}
