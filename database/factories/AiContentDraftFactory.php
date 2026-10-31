<?php

namespace Database\Factories;

use App\Enums\AiContentDraftStatus;
use App\Enums\AiContentLength;
use App\Enums\AiContentType;
use App\Models\AiContentDraft;
use App\Models\Brand;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<AiContentDraft> */
class AiContentDraftFactory extends Factory
{
    public function definition(): array
    {
        return [
            'workspace_id' => Workspace::factory(),
            'brand_id' => fn (array $attributes) => Brand::factory()->create([
                'workspace_id' => $attributes['workspace_id'],
            ])->getKey(),
            'created_by_user_id' => User::factory(),
            'content_type' => AiContentType::SeoBlogArticle,
            'topic' => fake()->sentence(5),
            'primary_keyword' => fake()->words(3, true),
            'secondary_keywords' => ['content strategy'],
            'target_audience' => fake()->sentence(),
            'language' => 'English',
            'region' => 'United States',
            'content_length' => AiContentLength::Standard,
            'tone' => 'Clear, practical, and trustworthy.',
            'seo_title' => fake()->sentence(6),
            'meta_description' => fake()->sentence(14),
            'suggested_slug' => fake()->slug(),
            'outline' => ['Introduction', 'Main section', 'Conclusion'],
            'content' => fake()->paragraphs(3, true),
            'faq_entries' => [],
            'internal_link_opportunities' => [],
            'external_research_topics' => [],
            'verification_notes' => [],
            'status' => AiContentDraftStatus::Draft,
        ];
    }
}
