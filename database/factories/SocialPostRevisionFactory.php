<?php

namespace Database\Factories;

use App\Models\SocialPost;
use App\Models\SocialPostRevision;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<SocialPostRevision> */
class SocialPostRevisionFactory extends Factory
{
    public function definition(): array
    {
        return ['social_post_id' => SocialPost::factory(), 'editor_user_id' => User::factory(), 'revision_number' => 1, 'changed_fields' => ['title'], 'snapshot' => []];
    }
}
