<?php

namespace App\Services\BrandDna;

use App\Models\Brand;
use App\Models\BrandDnaProfile;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class BrandDnaService
{
    public function __construct(
        private readonly BrandDnaCrawler $crawler,
        private readonly BrandDnaAnalyzer $analyzer,
    ) {}

    /**
     * Analyze brand website and create a structured Brand DNA profile awaiting review.
     *
     * @param  list<string>|null  $customUrls
     *
     * @throws ValidationException
     */
    public function analyze(Brand $brand, User $user, ?array $customUrls = null): BrandDnaProfile
    {
        $crawlData = $this->crawler->crawl($brand, $customUrls);
        $analysis = $this->analyzer->analyze($brand, $crawlData);

        return DB::transaction(function () use ($brand, $user, $crawlData, $analysis) {
            /** @var BrandDnaProfile $profile */
            $profile = BrandDnaProfile::query()->create([
                'workspace_id' => $brand->workspace_id,
                'brand_id' => $brand->getKey(),
                'created_by_user_id' => $user->getKey(),
                'status' => 'pending_review',
                'source_urls' => $crawlData['crawled_urls'],
                'extracted_raw_summary' => $crawlData['raw_summary'],
                'brand_identity' => $analysis['brand_identity'],
                'business_description' => $analysis['business_description'],
                'industry' => $analysis['industry'],
                'target_audience' => $analysis['target_audience'],
                'primary_market' => $analysis['primary_market'],
                'voice_tone' => $analysis['voice_tone'],
                'key_services' => $analysis['key_services'],
                'suggested_keywords' => $analysis['suggested_keywords'],
                'content_themes' => $analysis['content_themes'],
                'brand_values' => $analysis['brand_values'],
                'competitor_differentiation' => $analysis['competitor_differentiation'],
                'is_approved' => false,
                'last_analyzed_at' => now(),
            ]);

            return $profile;
        });
    }

    /**
     * Update Brand DNA profile fields following customer review/editing.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(BrandDnaProfile $profile, User $user, array $data): BrandDnaProfile
    {
        $profile->update([
            'brand_identity' => $data['brand_identity'] ?? $profile->brand_identity,
            'business_description' => $data['business_description'] ?? $profile->business_description,
            'industry' => $data['industry'] ?? $profile->industry,
            'target_audience' => $data['target_audience'] ?? $profile->target_audience,
            'primary_market' => $data['primary_market'] ?? $profile->primary_market,
            'voice_tone' => $data['voice_tone'] ?? $profile->voice_tone,
            'key_services' => $data['key_services'] ?? $profile->key_services,
            'suggested_keywords' => $data['suggested_keywords'] ?? $profile->suggested_keywords,
            'content_themes' => $data['content_themes'] ?? $profile->content_themes,
            'brand_values' => $data['brand_values'] ?? $profile->brand_values,
            'competitor_differentiation' => $data['competitor_differentiation'] ?? $profile->competitor_differentiation,
        ]);

        return $profile->fresh();
    }

    /**
     * Approve Brand DNA profile and optionally apply it to the Brand's core settings.
     */
    public function approve(BrandDnaProfile $profile, User $user, bool $syncToBrand = false): BrandDnaProfile
    {
        return DB::transaction(function () use ($profile, $user, $syncToBrand) {
            // Deactivate any prior approved profile for this brand
            $profile->brand->brandDnaProfiles()
                ->where('id', '!=', $profile->getKey())
                ->update(['is_approved' => false]);

            $profile->update([
                'is_approved' => true,
                'approved_at' => now(),
                'approved_by_user_id' => $user->getKey(),
                'status' => 'approved',
            ]);

            if ($syncToBrand) {
                $this->syncToBrand($profile);
            }

            return $profile->fresh();
        });
    }

    /**
     * Apply approved Brand DNA fields to the brand's core record.
     */
    public function syncToBrand(BrandDnaProfile $profile): void
    {
        $brand = $profile->brand;

        $brand->update([
            'business_description' => $profile->business_description ?: $brand->business_description,
            'industry' => $profile->industry ?: $brand->industry,
            'target_audience' => $profile->target_audience ?: $brand->target_audience,
            'primary_market' => $profile->primary_market ?: $brand->primary_market,
            'voice_tone' => $profile->voice_tone ?: $brand->voice_tone,
            'seo_topic_categories' => ! empty($profile->content_themes) ? $profile->content_themes : $brand->seo_topic_categories,
        ]);
    }
}

