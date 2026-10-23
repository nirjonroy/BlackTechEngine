<?php

namespace App\Http\Requests\Admin;

use App\Services\FeaturePageContent;
use Closure;
use Illuminate\Validation\Rule;

class UpdateFeaturePageSettingRequest extends AdminFormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $iconRule = Rule::in(array_keys(FeaturePageContent::ICON_OPTIONS));

        return [
            'hero_badge' => ['nullable', 'string', 'max:255'],
            'hero_title_normal' => ['nullable', 'string', 'max:255'],
            'hero_title_highlighted' => ['nullable', 'string', 'max:255'],
            'hero_description' => ['nullable', 'string', 'max:5000'],
            'features_json' => ['nullable', 'array', 'max:30'],
            'features_json.*' => ['array:id,order,icon,title,description,supporting_points,highlights,preview_heading,preview_label,preview_badge,preview_description,preview_items,footer_label'],
            'features_json.*.id' => ['nullable', 'string', 'max:80', 'distinct', $this->featureIdRule()],
            'features_json.*.order' => ['nullable', 'integer', 'between:1,10000'],
            'features_json.*.icon' => ['nullable', 'string', $iconRule],
            'features_json.*.title' => ['required', 'string', 'max:255'],
            'features_json.*.description' => ['required', 'string', 'max:5000'],
            'features_json.*.supporting_points' => ['nullable', 'array', 'max:12'],
            'features_json.*.supporting_points.*' => ['array:icon,title,description'],
            'features_json.*.supporting_points.*.icon' => ['nullable', 'string', $iconRule],
            'features_json.*.supporting_points.*.title' => ['required', 'string', 'max:255'],
            'features_json.*.supporting_points.*.description' => ['nullable', 'string', 'max:1000'],
            'features_json.*.highlights' => ['nullable', 'array', 'max:12'],
            'features_json.*.highlights.*' => ['array:label,value,description'],
            'features_json.*.highlights.*.label' => ['required', 'string', 'max:255'],
            'features_json.*.highlights.*.value' => ['nullable', 'string', 'max:255'],
            'features_json.*.highlights.*.description' => ['nullable', 'string', 'max:1000'],
            'features_json.*.preview_heading' => ['nullable', 'string', 'max:255'],
            'features_json.*.preview_label' => ['nullable', 'string', 'max:255'],
            'features_json.*.preview_badge' => ['nullable', 'string', 'max:255'],
            'features_json.*.preview_description' => ['nullable', 'string', 'max:1000'],
            'features_json.*.preview_items' => ['nullable', 'array', 'max:12'],
            'features_json.*.preview_items.*' => ['array:icon,label,status,description'],
            'features_json.*.preview_items.*.icon' => ['nullable', 'string', $iconRule],
            'features_json.*.preview_items.*.label' => ['required', 'string', 'max:255'],
            'features_json.*.preview_items.*.status' => ['nullable', 'string', 'max:255'],
            'features_json.*.preview_items.*.description' => ['nullable', 'string', 'max:1000'],
            'features_json.*.footer_label' => ['nullable', 'string', 'max:255'],
            'additional_features_heading' => ['nullable', 'string', 'max:255'],
            'additional_features_description' => ['nullable', 'string', 'max:5000'],
            'calc_title' => ['nullable', 'string', 'max:255'],
            'calc_description' => ['nullable', 'string', 'max:5000'],
            'calc_disclaimer' => ['nullable', 'string', 'max:1000'],
            'testimonial_badge' => ['nullable', 'string', 'max:255'],
            'testimonial_title' => ['nullable', 'string', 'max:255'],
            'testimonials_json' => ['nullable', 'array', 'max:100'],
            'testimonials_json.*' => ['array:quote,author_initials,author_name,author_role'],
            'testimonials_json.*.quote' => ['required', 'string', 'max:5000'],
            'testimonials_json.*.author_initials' => ['nullable', 'string', 'max:20'],
            'testimonials_json.*.author_name' => ['required', 'string', 'max:255'],
            'testimonials_json.*.author_role' => ['nullable', 'string', 'max:255'],
            'final_cta_heading' => ['nullable', 'string', 'max:255'],
            'final_cta_description' => ['nullable', 'string', 'max:5000'],
            'final_primary_cta_label' => ['nullable', 'string', 'max:255'],
            'final_primary_cta_destination' => ['nullable', 'string', Rule::in(array_keys(FeaturePageContent::DESTINATION_OPTIONS))],
            'final_secondary_cta_label' => ['nullable', 'string', 'max:255'],
            'final_secondary_cta_destination' => ['nullable', 'string', Rule::in(array_keys(FeaturePageContent::DESTINATION_OPTIONS))],
            'seo_title' => ['nullable', 'string', 'max:1000'],
            'seo_description' => ['nullable', 'string', 'max:5000'],
            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:5000'],
            'meta_image' => $this->imageRules(),
            'author' => ['nullable', 'string', 'max:255'],
            'publisher' => ['nullable', 'string', 'max:255'],
            'copyright' => ['nullable', 'string', 'max:255'],
            'site_name' => ['nullable', 'string', 'max:255'],
            'keywords' => ['nullable', 'string', 'max:5000'],
        ];
    }

    private function featureIdRule(): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail): void {
            if ($value === null || in_array($value, FeaturePageContent::PRIMARY_IDS, true)) {
                return;
            }

            if (! is_string($value) || preg_match('/\Aadditional-[a-z0-9-]+\z/', $value) !== 1) {
                $fail('The :attribute must identify a known feature layout or an additional feature.');
            }
        };
    }
}
