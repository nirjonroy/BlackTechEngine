<?php

namespace App\Http\Requests\Admin;

class SavePricingPlanRequest extends AdminFormRequest
{
    protected function prepareForValidation(): void
    {
        $features = collect($this->input('features', []))
            ->map(function (mixed $feature): mixed {
                if (! is_array($feature)) {
                    return $feature;
                }

                $feature['is_included'] = filter_var($feature['is_included'] ?? false, FILTER_VALIDATE_BOOL);

                return $feature;
            })
            ->all();

        $this->merge([
            'is_popular' => $this->boolean('is_popular'),
            'features' => $features,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'badge' => ['nullable', 'string', 'max:255'],
            'title' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'monthly_price' => ['nullable', 'numeric', 'min:0', 'max:999999.99', 'decimal:0,2'],
            'annual_price' => ['nullable', 'numeric', 'min:0', 'max:999999.99', 'decimal:0,2'],
            'is_popular' => ['required', 'boolean'],
            'button_text' => ['nullable', 'string', 'max:255'],
            'button_link' => ['nullable', 'url:http,https', 'max:2048'],
            'features' => ['nullable', 'array', 'max:100'],
            'features.*' => ['array:name,is_included'],
            'features.*.name' => ['required', 'string', 'max:255'],
            'features.*.is_included' => ['required', 'boolean'],
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
