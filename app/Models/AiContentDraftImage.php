<?php

namespace App\Models;

use Database\Factories\AiContentDraftImageFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

#[Fillable([
    'workspace_id',
    'brand_id',
    'ai_content_draft_id',
    'ai_model_id',
    'created_by_user_id',
    'prompt',
    'user_instructions',
    'image_path',
    'file_name',
    'mime_type',
    'file_size_bytes',
    'width',
    'height',
    'alt_text',
    'status',
    'error_message',
    'wordpress_media_id',
    'is_active',
])]
class AiContentDraftImage extends Model
{
    /** @use HasFactory<AiContentDraftImageFactory> */
    use HasFactory;

    protected $table = 'ai_content_draft_images';

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

    /** @return BelongsTo<AiContentDraft, $this> */
    public function draft(): BelongsTo
    {
        return $this->belongsTo(AiContentDraft::class, 'ai_content_draft_id');
    }

    /** @return BelongsTo<AiModel, $this> */
    public function model(): BelongsTo
    {
        return $this->belongsTo(AiModel::class, 'ai_model_id');
    }

    /** @return BelongsTo<User, $this> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function getUrlAttribute(): string
    {
        return Storage::disk('public')->url($this->image_path);
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'file_size_bytes' => 'integer',
            'width' => 'integer',
            'height' => 'integer',
            'is_active' => 'boolean',
            'wordpress_media_id' => 'integer',
        ];
    }
}

