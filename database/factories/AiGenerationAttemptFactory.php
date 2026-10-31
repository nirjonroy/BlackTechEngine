<?php

namespace Database\Factories;

use App\Enums\AiGenerationAttemptStatus;
use App\Models\AiGenerationAttempt;
use App\Models\Brand;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<AiGenerationAttempt> */
class AiGenerationAttemptFactory extends Factory
{
    public function definition(): array
    {
        return [
            'correlation_id' => (string) Str::uuid(),
            'idempotency_key' => (string) Str::uuid(),
            'workspace_id' => Workspace::factory(),
            'brand_id' => fn (array $attributes) => Brand::factory()->create([
                'workspace_id' => $attributes['workspace_id'],
            ])->getKey(),
            'user_id' => User::factory(),
            'status' => AiGenerationAttemptStatus::Pending,
            'prompt_hash' => hash('sha256', fake()->sentence()),
        ];
    }
}
