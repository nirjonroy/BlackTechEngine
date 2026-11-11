<?php

namespace Database\Factories;

use App\Models\AiContentDraft;
use App\Models\AiContentDraftImage;
use App\Models\Brand;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AiContentDraftImage>
 */
class AiContentDraftImageFactory extends Factory
{
    public function definition(): array
    {
        $fileName = fake()->slug(2).'-featured.jpg';

        return [
            'workspace_id' => Workspace::factory(),
            'brand_id' => Brand::factory(),
            'ai_content_draft_id' => AiContentDraft::factory(),
            'ai_model_id' => null,
            'created_by_user_id' => User::factory(),
            'prompt' => fake()->sentence(8),
            'user_instructions' => fake()->optional()->sentence(),
            'image_path' => 'featured-images/'.fake()->uuid().'.jpg',
            'file_name' => $fileName,
            'mime_type' => 'image/jpeg',
            'file_size_bytes' => fake()->numberBetween(50000, 500000),
            'width' => 1200,
            'height' => 630,
            'alt_text' => fake()->sentence(6),
            'status' => 'completed',
            'error_message' => null,
            'wordpress_media_id' => null,
            'is_active' => true,
        ];
    }
}

