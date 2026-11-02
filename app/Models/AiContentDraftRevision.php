<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'ai_content_draft_id',
    'editor_user_id',
    'revision_number',
    'changed_fields',
    'snapshot',
])]
class AiContentDraftRevision extends Model
{
    /** @return BelongsTo<AiContentDraft, $this> */
    public function draft(): BelongsTo
    {
        return $this->belongsTo(AiContentDraft::class, 'ai_content_draft_id');
    }

    /** @return BelongsTo<User, $this> */
    public function editor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'editor_user_id');
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'revision_number' => 'integer',
            'changed_fields' => 'array',
            'snapshot' => 'array',
        ];
    }
}
