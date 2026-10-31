<?php

namespace App\Models;

use Database\Factories\BrandFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'workspace_id',
    'name',
    'website_url',
    'business_description',
    'industry',
    'target_audience',
    'primary_market',
    'preferred_language',
    'voice_tone',
    'content_objectives',
    'seo_topic_categories',
    'is_active',
])]
class Brand extends Model
{
    /** @use HasFactory<BrandFactory> */
    use HasFactory;

    /** @return BelongsTo<Workspace, $this> */
    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    /** @return HasMany<AiContentDraft, $this> */
    public function contentDrafts(): HasMany
    {
        return $this->hasMany(AiContentDraft::class);
    }

    /** @return HasMany<AiContentDraft, $this> */
    public function aiContentDrafts(): HasMany
    {
        return $this->contentDrafts();
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'content_objectives' => 'array',
            'seo_topic_categories' => 'array',
            'is_active' => 'boolean',
        ];
    }
}
