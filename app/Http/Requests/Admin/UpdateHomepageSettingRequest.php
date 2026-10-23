<?php

namespace App\Http\Requests\Admin;

use App\Models\HomepageSetting;
use Illuminate\Validation\Rule;

class UpdateHomepageSettingRequest extends AdminFormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $shortText = ['nullable', 'string', 'max:255'];
        $longText = ['nullable', 'string', 'max:5000'];
        $destination = ['nullable', 'string', Rule::in(array_keys(HomepageSetting::DESTINATION_OPTIONS))];

        return [
            'hero_badge' => $shortText,
            'hero_title' => $shortText,
            'hero_highlighted_title' => $shortText,
            'hero_description' => $longText,
            'primary_cta_label' => $shortText,
            'primary_cta_destination' => $destination,
            'secondary_cta_label' => $shortText,
            'secondary_cta_destination' => $destination,
            'scanner_heading' => $shortText,
            'scanner_description' => $longText,
            'scanner_button_label' => $shortText,
            'integrations_heading' => $shortText,
            'integrations_description' => $longText,
            'features_badge' => $shortText,
            'features_heading' => $shortText,
            'features_description' => $longText,
            'calculator_badge' => $shortText,
            'calculator_heading' => $shortText,
            'calculator_description' => $longText,
            'calculator_note' => $longText,
            'calculator_cta_label' => $shortText,
            'calculator_cta_destination' => $destination,
            'final_cta_heading' => $shortText,
            'final_cta_description' => $longText,
            'final_primary_cta_label' => $shortText,
            'final_primary_cta_destination' => $destination,
            'final_secondary_cta_label' => $shortText,
            'final_secondary_cta_destination' => $destination,
        ];
    }
}
