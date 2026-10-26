<?php

namespace App\Models;

use App\Enums\AffiliateStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

#[Fillable(['user_id', 'application_id', 'referral_code', 'status', 'approved_at', 'suspended_at'])]
class Affiliate extends Model
{
    protected $attributes = [
        'status' => 'pending',
    ];

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<AffiliateApplication, $this> */
    public function application(): BelongsTo
    {
        return $this->belongsTo(AffiliateApplication::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', AffiliateStatus::Active->value);
    }

    protected function referralCode(): Attribute
    {
        return Attribute::make(
            set: fn (?string $value): ?string => filled($value)
                ? Str::upper(trim($value))
                : null,
        );
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => AffiliateStatus::class,
            'approved_at' => 'datetime',
            'suspended_at' => 'datetime',
        ];
    }
}
