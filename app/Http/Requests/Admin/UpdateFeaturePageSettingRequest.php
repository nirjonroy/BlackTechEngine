<?php

namespace App\Http\Requests\Admin;

class UpdateFeaturePageSettingRequest extends AdminFormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'hero_badge' => ['nullable', 'string', 'max:255'],
            'hero_title_normal' => ['nullable', 'string', 'max:255'],
            'hero_title_highlighted' => ['nullable', 'string', 'max:255'],
            'hero_description' => ['nullable', 'string', 'max:5000'],
            'features_json' => ['nullable', 'array', 'max:100'],
            'features_json.*' => ['array:icon,title,description'],
            'features_json.*.icon' => ['nullable', 'string', 'max:255'],
            'features_json.*.title' => ['required', 'string', 'max:255'],
            'features_json.*.description' => ['required', 'string', 'max:5000'],
            'calc_title' => ['nullable', 'string', 'max:255'],
            'calc_description' => ['nullable', 'string', 'max:5000'],
            'testimonial_badge' => ['nullable', 'string', 'max:255'],
            'testimonial_title' => ['nullable', 'string', 'max:255'],
            'testimonials_json' => ['nullable', 'array', 'max:100'],
            'testimonials_json.*' => ['array:quote,author_initials,author_name,author_role'],
            'testimonials_json.*.quote' => ['required', 'string', 'max:5000'],
            'testimonials_json.*.author_initials' => ['nullable', 'string', 'max:20'],
            'testimonials_json.*.author_name' => ['required', 'string', 'max:255'],
            'testimonials_json.*.author_role' => ['nullable', 'string', 'max:255'],
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
}
