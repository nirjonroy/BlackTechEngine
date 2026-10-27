<?php

namespace Database\Factories;

use App\Enums\AiModelAvailabilityStatus;
use App\Enums\AiModelModality;
use App\Models\AiModel;
use App\Models\AiProvider;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<AiModel> */
class AiModelFactory extends Factory
{
    public function definition(): array
    {
        return [
            'ai_provider_id' => AiProvider::factory(),
            'provider_model_id' => fake()->unique()->slug(3),
            'display_name' => fake()->words(3, true),
            'modality' => AiModelModality::Text,
            'capabilities' => [],
            'is_enabled' => false,
            'availability_status' => AiModelAvailabilityStatus::Unverified,
            'last_verified_at' => null,
        ];
    }
}
