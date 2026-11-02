<?php

namespace Database\Factories;

use App\Enums\SocialPlatform;
use App\Models\SocialPost;
use App\Models\SocialPostVariant;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<SocialPostVariant> */
class SocialPostVariantFactory extends Factory
{
    public function definition(): array
    {
        return [
            'social_post_id' => SocialPost::factory(),
            'platform' => SocialPlatform::LinkedIn,
            'caption' => fake()->paragraph(),
            'hashtags' => ['#SEO', '#ContentMarketing'],
            'cta_text' => 'Read more',
            'target_url' => 'https://example.com/article',
            'image_alt_text' => 'Illustration supporting the social post.',
        ];
    }
}
