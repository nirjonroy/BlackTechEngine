<?php

namespace Database\Factories;

use App\Models\Brand;
use App\Models\Workspace;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Brand>
 */
class BrandFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'workspace_id' => Workspace::factory(),
            'name' => fake()->company(),
            'website_url' => fake()->url(),
            'business_description' => fake()->paragraph(),
            'industry' => fake()->randomElement(['SaaS', 'E-commerce', 'Professional Services']),
            'target_audience' => fake()->sentence(),
            'primary_market' => fake()->country(),
            'preferred_language' => 'English',
            'voice_tone' => 'Clear, practical, and trustworthy.',
            'content_objectives' => ['Build qualified organic traffic'],
            'seo_topic_categories' => ['Technical SEO'],
            'is_active' => true,
        ];
    }
}
