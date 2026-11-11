<?php

namespace Database\Factories;

use App\Enums\SearchIntent;
use App\Models\Brand;
use App\Models\BrandKeyword;
use App\Models\BrandTopicCluster;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BrandKeyword>
 */
class BrandKeywordFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $kw = fake()->unique()->words(3, true);

        return [
            'workspace_id' => Workspace::factory(),
            'brand_id' => Brand::factory(),
            'brand_topic_cluster_id' => BrandTopicCluster::factory(),
            'created_by_user_id' => User::factory(),
            'keyword' => $kw,
            'normalized_keyword' => BrandKeyword::normalize($kw),
            'search_intent' => fake()->randomElement(SearchIntent::cases()),
            'search_volume' => null,
            'difficulty_score' => null,
            'notes' => fake()->optional()->sentence(),
            'is_starred' => false,
        ];
    }
}
