<?php

namespace Database\Factories;

use App\Enums\AiProviderHealthStatus;
use App\Models\AiProvider;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<AiProvider> */
class AiProviderFactory extends Factory
{
    public function definition(): array
    {
        $key = fake()->unique()->slug(2);

        return [
            'key' => $key,
            'display_name' => fake()->words(2, true),
            'adapter_type' => null,
            'description' => fake()->optional()->sentence(),
            'is_enabled' => false,
            'health_status' => AiProviderHealthStatus::Unverified,
            'request_timeout_seconds' => AiProvider::DEFAULT_REQUEST_TIMEOUT_SECONDS,
            'retry_limit' => 0,
            'last_verified_at' => null,
        ];
    }
}
