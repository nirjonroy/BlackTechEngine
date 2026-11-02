<?php

namespace App\Models;

use App\Enums\SocialPostStatus;
use Database\Factories\SocialPostFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'workspace_id', 'brand_id', 'owner_user_id', 'created_by_user_id', 'source_ai_content_draft_id',
    'approved_by_user_id', 'title', 'campaign', 'idempotency_key', 'request_fingerprint', 'status', 'lock_version', 'current_revision',
    'source_revision', 'source_snapshot', 'review_feedback', 'review_submitted_at', 'approved_at',
    'planned_at', 'published_at', 'published_url', 'cancelled_at',
])]
class SocialPost extends Model
{
    /** @use HasFactory<SocialPostFactory> */
    use HasFactory;

    protected $attributes = ['status' => 'draft', 'lock_version' => 0, 'current_revision' => 1];

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_user_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function sourceDraft(): BelongsTo
    {
        return $this->belongsTo(AiContentDraft::class, 'source_ai_content_draft_id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by_user_id');
    }

    public function variants(): HasMany
    {
        return $this->hasMany(SocialPostVariant::class)->orderBy('id');
    }

    public function revisions(): HasMany
    {
        return $this->hasMany(SocialPostRevision::class)->latest('revision_number');
    }

    public function events(): HasMany
    {
        return $this->hasMany(SocialPostEvent::class)->latest('id');
    }

    public function sourceHasChanged(): bool
    {
        return $this->source_ai_content_draft_id !== null
            && $this->sourceDraft !== null
            && $this->source_revision !== $this->sourceDraft->current_revision;
    }

    protected function casts(): array
    {
        return [
            'status' => SocialPostStatus::class,
            'lock_version' => 'integer', 'current_revision' => 'integer', 'source_revision' => 'integer',
            'source_snapshot' => 'array', 'review_submitted_at' => 'datetime', 'approved_at' => 'datetime',
            'planned_at' => 'datetime', 'published_at' => 'datetime', 'cancelled_at' => 'datetime',
        ];
    }
}
