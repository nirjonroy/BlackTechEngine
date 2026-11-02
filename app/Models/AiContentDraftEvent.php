<?php

namespace App\Models;

use App\Enums\AiContentDraftStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'ai_content_draft_id',
    'workspace_id',
    'brand_id',
    'actor_user_id',
    'action',
    'from_status',
    'to_status',
    'lock_version',
    'notes',
    'context',
])]
class AiContentDraftEvent extends Model
{
    /** @return BelongsTo<AiContentDraft, $this> */
    public function draft(): BelongsTo
    {
        return $this->belongsTo(AiContentDraft::class, 'ai_content_draft_id');
    }

    /** @return BelongsTo<Workspace, $this> */
    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    /** @return BelongsTo<Brand, $this> */
    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    /** @return BelongsTo<User, $this> */
    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'from_status' => AiContentDraftStatus::class,
            'to_status' => AiContentDraftStatus::class,
            'lock_version' => 'integer',
            'context' => 'array',
        ];
    }
}
