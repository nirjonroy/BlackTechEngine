<?php

namespace Database\Factories;

use App\Enums\SocialPostStatus;
use App\Models\SocialPost;
use App\Models\SocialPostEvent;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<SocialPostEvent> */
class SocialPostEventFactory extends Factory
{
    public function definition(): array
    {
        return [
            'social_post_id' => SocialPost::factory(),
            'workspace_id' => fn (array $attributes) => SocialPost::findOrFail($attributes['social_post_id'])->workspace_id,
            'brand_id' => fn (array $attributes) => SocialPost::findOrFail($attributes['social_post_id'])->brand_id,
            'actor_user_id' => User::factory(), 'action' => 'created', 'to_status' => SocialPostStatus::Draft,
            'lock_version' => 0,
        ];
    }
}
