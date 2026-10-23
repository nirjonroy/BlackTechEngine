<?php

namespace Database\Factories;

use App\Models\AffiliatePageSetting;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AffiliatePageSetting>
 */
class AffiliatePageSettingFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'hero_badge' => 'Affiliate program preview',
            'hero_title' => 'Partner With BlackTechEngine',
            'hero_highlighted_title' => 'When Applications Open',
            'hero_description' => fake()->paragraph(),
            'primary_cta_label' => 'Review Program Status',
            'primary_cta_destination' => 'program-status',
            'secondary_cta_label' => 'Create a Standard Account',
            'secondary_cta_destination' => 'register',
            'availability_status' => 'not_open',
            'resources_availability_status' => 'not_available',
            'workflow_steps' => AffiliatePageSetting::DEFAULT_WORKFLOW_STEPS,
        ];
    }
}
