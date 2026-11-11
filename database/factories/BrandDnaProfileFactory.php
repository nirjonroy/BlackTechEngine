<?php

namespace Database\Factories;

use App\Models\Brand;
use App\Models\BrandDnaProfile;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BrandDnaProfile>
 */
class BrandDnaProfileFactory extends Factory
{
    public function definition(): array
    {
        return [
            'workspace_id' => Workspace::factory(),
            'brand_id' => Brand::factory(),
            'created_by_user_id' => User::factory(),
            'status' => 'completed',
            'source_urls' => ['https://example.com', 'https://example.com/about'],
            'extracted_raw_summary' => fake()->paragraph(5),
            'brand_identity' => fake()->sentence(10),
            'business_description' => fake()->paragraph(2),
            'industry' => 'SaaS',
            'target_audience' => 'B2B Marketing & Product Teams',
            'primary_market' => 'United States',
            'voice_tone' => 'Authoritative, clear, and actionable',
            'key_services' => ['SEO Content Engine', 'Keyword Clustering', 'WordPress Automation'],
            'suggested_keywords' => ['programmatic seo', 'b2b content marketing', 'seo autopilot'],
            'content_themes' => ['Technical SEO', 'Organic Growth', 'Content Strategy'],
            'brand_values' => ['Transparency', 'Precision', 'Scalability'],
            'competitor_differentiation' => 'Fully automated SEO pipelines with strict human editorial controls.',
            'is_approved' => false,
            'approved_at' => null,
            'approved_by_user_id' => null,
            'last_error' => null,
            'last_analyzed_at' => now(),
        ];
    }

    public function approved(): static
    {
        return $this->state(fn () => [
            'is_approved' => true,
            'approved_at' => now(),
            'status' => 'approved',
        ]);
    }
}

