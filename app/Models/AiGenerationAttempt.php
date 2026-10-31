<?php

namespace App\Models;

use App\Enums\AiGenerationAttemptStatus;
use Database\Factories\AiGenerationAttemptFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'correlation_id', 'idempotency_key', 'workspace_id', 'brand_id', 'user_id',
    'ai_content_draft_id', 'ai_model_id', 'status', 'error_category', 'input_tokens',
    'output_tokens', 'prompt_hash', 'completed_at',
])]
class AiGenerationAttempt extends Model
{
    /** @use HasFactory<AiGenerationAttemptFactory> */
    use HasFactory;

    protected $attributes = ['status' => 'pending'];

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
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
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

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'status' => AiGenerationAttemptStatus::class,
            'input_tokens' => 'integer',
            'output_tokens' => 'integer',
            'completed_at' => 'datetime',
        ];
    }
}
