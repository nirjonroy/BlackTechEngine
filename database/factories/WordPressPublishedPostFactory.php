<?php

namespace Database\Factories;

use App\Enums\WordPressPostStatus;
use App\Models\AiContentDraft;
use App\Models\Brand;
use App\Models\User;
use App\Models\WordPressPublishedPost;
use App\Models\WordPressSiteConnection;
use App\Models\Workspace;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WordPressPublishedPost>
 */
class WordPressPublishedPostFactory extends Factory
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
            'brand_id' => Brand::factory(),
            'wordpress_site_connection_id' => WordPressSiteConnection::factory(),
            'ai_content_draft_id' => AiContentDraft::factory(),
            'created_by_user_id' => User::factory(),
            'wordpress_post_id' => fake()->numberBetween(100, 99999),
            'post_url' => fake()->url(),
            'status' => WordPressPostStatus::Draft,
            'published_at' => now(),
            'last_error' => null,
            'transferred_payload' => [],
        ];
    }
}
