<?php

namespace Database\Factories;

use App\Models\Brand;
use App\Models\BrandTopicCluster;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BrandTopicCluster>
 */
class BrandTopicClusterFactory extends Factory
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
            'name' => fake()->words(2, true).' Strategy',
            'description' => fake()->sentence(),
            'pillar_keyword' => fake()->words(3, true),
        ];
    }
}
