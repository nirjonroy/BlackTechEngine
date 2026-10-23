<?php

namespace App\Http\Requests\Admin;

use App\Models\AffiliatePageSetting;
use Illuminate\Validation\Rule;

class UpdateAffiliatePageSettingRequest extends AdminFormRequest
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
        $destination = ['nullable', 'string', Rule::in(array_keys(AffiliatePageSetting::DESTINATION_OPTIONS))];

        return [
            'hero_badge' => $shortText,
            'hero_title' => $shortText,
            'hero_highlighted_title' => $shortText,
            'hero_description' => $longText,
            'primary_cta_label' => $shortText,
            'primary_cta_destination' => $destination,
            'secondary_cta_label' => $shortText,
            'secondary_cta_destination' => $destination,
            'hero_disclosure' => $longText,
            'status_heading' => $shortText,
            'status_description' => $longText,
            'terms_heading' => $shortText,
            'availability_status' => ['required', 'string', Rule::in(array_keys(AffiliatePageSetting::AVAILABILITY_OPTIONS))],
            'availability_description' => $longText,
            'workflow_heading' => $shortText,
            'workflow_description' => $longText,
            'workflow_steps' => ['required', 'array', 'size:3'],
            'workflow_steps.*' => ['required', 'array:icon,title,description'],
            'workflow_steps.*.icon' => ['required', 'string', Rule::in(AffiliatePageSetting::WORKFLOW_ICONS)],
            'workflow_steps.*.title' => ['required', 'string', 'max:255'],
            'workflow_steps.*.description' => ['required', 'string', 'max:5000'],
            'resources_badge' => $shortText,
            'resources_heading' => $shortText,
            'resources_description' => $longText,
            'resources_availability_status' => ['required', 'string', Rule::in(array_keys(AffiliatePageSetting::RESOURCE_AVAILABILITY_OPTIONS))],
            'final_cta_label' => $shortText,
            'final_cta_destination' => ['nullable', 'string', Rule::in(array_keys(AffiliatePageSetting::INFORMATIONAL_DESTINATION_OPTIONS))],
        ];
    }
}
