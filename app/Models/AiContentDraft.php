<?php

namespace App\Models;

use App\Enums\AiContentDraftStatus;
use App\Enums\AiContentLength;
use App\Enums\AiContentType;
use Database\Factories\AiContentDraftFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'workspace_id', 'brand_id', 'created_by_user_id', 'ai_provider_id', 'ai_model_id',
    'content_type', 'topic', 'primary_keyword', 'secondary_keywords', 'target_audience',
    'language', 'region', 'content_length', 'tone', 'seo_title', 'meta_description',
    'suggested_slug', 'outline', 'content', 'faq_entries', 'internal_link_opportunities',
    'external_research_topics', 'verification_notes', 'status',
])]
class AiContentDraft extends Model
{
    /** @use HasFactory<AiContentDraftFactory> */
    use HasFactory;

    protected $attributes = [
        'secondary_keywords' => '[]',
        'outline' => '[]',
        'faq_entries' => '[]',
        'internal_link_opportunities' => '[]',
        'external_research_topics' => '[]',
        'verification_notes' => '[]',
        'status' => 'draft',
    ];

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
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    /** @return BelongsTo<AiProvider, $this> */
    public function provider(): BelongsTo
    {
        return $this->belongsTo(AiProvider::class, 'ai_provider_id');
    }

    /** @return BelongsTo<AiModel, $this> */
    public function model(): BelongsTo
    {
        return $this->belongsTo(AiModel::class, 'ai_model_id');
    }

    /** @return HasMany<AiGenerationAttempt, $this> */
    public function generationAttempts(): HasMany
    {
        return $this->hasMany(AiGenerationAttempt::class);
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'content_type' => AiContentType::class,
            'content_length' => AiContentLength::class,
            'secondary_keywords' => 'array',
            'outline' => 'array',
            'faq_entries' => 'array',
            'internal_link_opportunities' => 'array',
            'external_research_topics' => 'array',
            'verification_notes' => 'array',
            'status' => AiContentDraftStatus::class,
        ];
    }
}
