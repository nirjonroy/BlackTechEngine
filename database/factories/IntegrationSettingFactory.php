<?php

namespace Database\Factories;

use App\Models\IntegrationSetting;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<IntegrationSetting>
 */
class IntegrationSettingFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'gemini_enabled' => false,
            'gemini_managed' => false,
            'stripe_mode' => 'test',
            'stripe_enabled' => false,
            'mail_enabled' => false,
        ];
    }
}
