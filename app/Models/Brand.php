<?php

namespace App\Models;

use Database\Factories\BrandFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

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

    /** @return HasMany<SocialPost, $this> */
    public function socialPosts(): HasMany
    {
        return $this->hasMany(SocialPost::class);
    }

    /** @return HasOne<WordPressSiteConnection, $this> */
    public function wordPressConnection(): HasOne
    {
        return $this->hasOne(WordPressSiteConnection::class);
    }

    /** @return HasMany<WordPressPublishedPost, $this> */
    public function wordPressPublications(): HasMany
    {
        return $this->hasMany(WordPressPublishedPost::class);
    }

    /** @return HasMany<BrandTopicCluster, $this> */
    public function topicClusters(): HasMany
    {
        return $this->hasMany(BrandTopicCluster::class);
    }

    /** @return HasMany<BrandTopicCluster, $this> */
    public function brandTopicClusters(): HasMany
    {
        return $this->topicClusters();
    }

    /** @return HasMany<BrandKeyword, $this> */
    public function keywords(): HasMany
    {
        return $this->hasMany(BrandKeyword::class);
    }

    /** @return HasMany<BrandKeyword, $this> */
    public function brandKeywords(): HasMany
    {
        return $this->keywords();
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

    /** @return HasMany<BrandDnaProfile, $this> */
    public function brandDnaProfiles(): HasMany
    {
        return $this->hasMany(BrandDnaProfile::class);
    }

    /** @return HasOne<BrandDnaProfile, $this> */
    public function brandDna(): HasOne
    {
        return $this->hasOne(BrandDnaProfile::class)->where('is_approved', true)->latestOfMany();
    }

    /** @return HasOne<BrandDnaProfile, $this> */
    public function latestBrandDnaProfile(): HasOne
    {
        return $this->hasOne(BrandDnaProfile::class)->latestOfMany();
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
