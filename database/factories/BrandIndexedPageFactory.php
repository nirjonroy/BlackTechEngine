<?php

namespace Database\Factories;

use App\Models\Brand;
use App\Models\BrandIndexedPage;
use App\Models\Workspace;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BrandIndexedPage>
 */
class BrandIndexedPageFactory extends Factory
{
    public function definition(): array
    {
        $slug = fake()->slug(3);
        $url = 'https://example.com/blog/'.$slug;

        return [
            'workspace_id' => Workspace::factory(),
            'brand_id' => Brand::factory(),
            'wordpress_post_id' => fake()->numberBetween(10, 9999),
            'url' => $url,
            'url_hash' => hash('sha256', $url),
            'title' => fake()->sentence(4),
            'slug' => $slug,
            'page_type' => 'post',
            'snippet' => fake()->paragraph(),
            'keywords' => [fake()->word(), fake()->word()],
            'source' => 'wordpress_api',
            'last_indexed_at' => now(),
        ];
    }
}
