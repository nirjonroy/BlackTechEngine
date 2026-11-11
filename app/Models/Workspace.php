<?php

namespace App\Models;

use Database\Factories\WorkspaceFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable(['owner_id', 'name', 'settings'])]
class Workspace extends Model
{
    /** @use HasFactory<WorkspaceFactory> */
    use HasFactory;

    /** @return BelongsTo<User, $this> */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    /** @return BelongsToMany<User, $this> */
    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
            ->withPivot('role')
            ->withTimestamps();
    }

    /** @return HasMany<Brand, $this> */
    public function brands(): HasMany
    {
        return $this->hasMany(Brand::class);
    }

    /** @return HasMany<AiContentDraft, $this> */
    public function contentDrafts(): HasMany
    {
        return $this->hasMany(AiContentDraft::class);
    }

    /** @return HasMany<SocialPost, $this> */
    public function socialPosts(): HasMany
    {
        return $this->hasMany(SocialPost::class);
    }

    /** @return HasOne<WorkspaceSubscription, $this> */
    public function subscription(): HasOne
    {
        return $this->hasOne(WorkspaceSubscription::class);
    }

    /** @return HasMany<BillingInvoice, $this> */
    public function billingInvoices(): HasMany
    {
        return $this->hasMany(BillingInvoice::class);
    }

    /** @return HasMany<ArticleSchedule, $this> */
    public function articleSchedules(): HasMany
    {
        return $this->hasMany(ArticleSchedule::class);
    }

    /** @return HasMany<ArticleScheduleRun, $this> */
    public function articleScheduleRuns(): HasMany
    {
        return $this->hasMany(ArticleScheduleRun::class);
    }

    /** @return HasMany<BrandIndexedPage, $this> */
    public function indexedPages(): HasMany
    {
        return $this->hasMany(BrandIndexedPage::class);
    }

    /** @return HasMany<BrandIndexingAudit, $this> */
    public function indexingAudits(): HasMany
    {
        return $this->hasMany(BrandIndexingAudit::class);
    }

    public function isAccessibleBy(User $user): bool
    {
        return $this->owner_id === $user->getKey()
            || $this->members()->whereKey($user->getKey())->exists();
    }

    public function isOwnedBy(User $user): bool
    {
        return $this->owner_id === $user->getKey();
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'settings' => 'array',
        ];
    }
}
