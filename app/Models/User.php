<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /** @return HasMany<AffiliateApplication, $this> */
    public function affiliateApplications(): HasMany
    {
        return $this->hasMany(AffiliateApplication::class);
    }

    /** @return HasOne<Affiliate, $this> */
    public function affiliate(): HasOne
    {
        return $this->hasOne(Affiliate::class);
    }

    /** @return HasMany<Workspace, $this> */
    public function ownedWorkspaces(): HasMany
    {
        return $this->hasMany(Workspace::class, 'owner_id');
    }

    /** @return HasMany<AiContentDraft, $this> */
    public function contentDrafts(): HasMany
    {
        return $this->hasMany(AiContentDraft::class, 'created_by_user_id');
    }

    /** @return BelongsToMany<Workspace, $this> */
    public function workspaces(): BelongsToMany
    {
        return $this->belongsToMany(Workspace::class)
            ->withPivot('role')
            ->withTimestamps();
    }

    /** @return BelongsTo<Workspace, $this> */
    public function activeWorkspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class, 'active_workspace_id');
    }

    /** @return Builder<Workspace> */
    public function accessibleWorkspaces(): Builder
    {
        return Workspace::query()
            ->where(fn (Builder $query): Builder => $query
                ->where('owner_id', $this->getKey())
                ->orWhereHas('members', fn (Builder $members): Builder => $members->whereKey($this->getKey())));
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'active_workspace_id' => 'integer',
            'lock_version' => 'integer',
            'password' => 'hashed',
        ];
    }
}
