<?php

namespace Database\Factories;

use App\Enums\WordPressConnectionStatus;
use App\Models\Brand;
use App\Models\User;
use App\Models\WordPressSiteConnection;
use App\Models\Workspace;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WordPressSiteConnection>
 */
class WordPressSiteConnectionFactory extends Factory
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
            'created_by_user_id' => User::factory(),
            'site_url' => 'https://example.com',
            'rest_api_endpoint' => 'https://example.com/wp-json/wp/v2',
            'username' => 'wp_admin',
            'application_password' => 'secret_application_password',
            'status' => WordPressConnectionStatus::Connected,
            'site_name' => fake()->company(),
            'wordpress_version' => '6.7.1',
            'last_verified_at' => now(),
            'last_error' => null,
        ];
    }
}
