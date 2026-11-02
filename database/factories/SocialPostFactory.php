<?php

namespace Database\Factories;

use App\Enums\SocialPostStatus;
use App\Models\Brand;
use App\Models\SocialPost;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<SocialPost> */
class SocialPostFactory extends Factory
{
    public function definition(): array
    {
        return [
            'workspace_id' => Workspace::factory(),
            'brand_id' => fn (array $attributes) => Brand::factory()->create(['workspace_id' => $attributes['workspace_id']])->getKey(),
            'owner_user_id' => User::factory(),
            'created_by_user_id' => User::factory(),
            'title' => fake()->sentence(5),
            'campaign' => fake()->optional()->words(3, true),
            'idempotency_key' => Str::uuid()->toString(),
            'request_fingerprint' => hash('sha256', Str::random(40)),
            'status' => SocialPostStatus::Draft,
            'lock_version' => 0,
            'current_revision' => 1,
        ];
    }
}
