<?php

namespace Database\Factories;

use App\Models\AiConfigurationAudit;
use App\Models\AiProvider;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<AiConfigurationAudit> */
class AiConfigurationAuditFactory extends Factory
{
    public function definition(): array
    {
        return [
            'acting_admin_id' => null,
            'subject_type' => AiProvider::class,
            'subject_id' => AiProvider::factory(),
            'action' => 'configuration_created',
            'request_id' => Str::uuid(),
            'changes' => [
                'changed_fields' => ['display_name'],
            ],
        ];
    }
}
