<?php

namespace Database\Factories;

use App\Enums\AiContentLength;
use App\Enums\AiContentType;
use App\Enums\ArticleScheduleFrequency;
use App\Enums\ArticleScheduleTargetStatus;
use App\Models\ArticleSchedule;
use App\Models\Brand;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ArticleSchedule>
 */
class ArticleScheduleFactory extends Factory
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
            'brand_keyword_id' => null,
            'brand_topic_cluster_id' => null,
            'title' => fake()->sentence(4),
            'primary_keyword' => fake()->words(2, true),
            'secondary_keywords' => [fake()->words(2, true), fake()->words(2, true)],
            'content_type' => AiContentType::SeoBlogArticle,
            'content_length' => AiContentLength::Standard,
            'tone' => 'Professional, authoritative',
            'target_audience' => 'Business professionals',
            'language' => 'en',
            'region' => 'US',
            'frequency' => ArticleScheduleFrequency::Daily,
            'scheduled_date' => null,
            'scheduled_time' => '09:00',
            'timezone' => 'UTC',
            'days_of_week' => [1, 3, 5],
            'target_status' => ArticleScheduleTargetStatus::Draft,
            'publish_as_status' => 'draft',
            'auto_publish_to_wordpress' => false,
            'is_active' => true,
            'next_run_at' => now()->addHour(),
            'last_run_at' => null,
            'last_error' => null,
            'failure_count' => 0,
            'total_runs' => 0,
        ];
    }

    public function paused(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    public function due(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => true,
            'next_run_at' => now()->subMinute(),
        ]);
    }
}
