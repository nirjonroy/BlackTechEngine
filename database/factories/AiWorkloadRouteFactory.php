<?php

namespace Database\Factories;

use App\Models\AiWorkloadRoute;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<AiWorkloadRoute> */
class AiWorkloadRouteFactory extends Factory
{
    public function definition(): array
    {
        return [
            'workload_key' => fake()->unique()->slug(3),
            'primary_ai_model_id' => null,
            'fallback_ai_model_id' => null,
            'is_enabled' => false,
            'settings' => null,
        ];
    }
}
