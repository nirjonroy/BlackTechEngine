<?php

namespace Database\Factories;

use App\Models\HomepageSetting;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<HomepageSetting>
 */
class HomepageSettingFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'hero_badge' => fake()->sentence(3),
            'hero_title' => fake()->sentence(5),
            'hero_highlighted_title' => fake()->words(2, true),
            'hero_description' => fake()->paragraph(),
            'primary_cta_label' => 'Create Account',
            'primary_cta_destination' => 'register',
            'secondary_cta_label' => 'View Pricing',
            'secondary_cta_destination' => 'pricing',
        ];
    }
}
