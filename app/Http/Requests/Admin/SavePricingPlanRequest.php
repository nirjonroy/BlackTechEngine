<?php

namespace App\Http\Requests\Admin;

use App\Services\PricingPresentation;
use Illuminate\Validation\Rule;

class SavePricingPlanRequest extends AdminFormRequest
{
    protected function prepareForValidation(): void
    {
        $hasFeatures = $this->has('features');
        $features = collect($this->input('features', []))
            ->map(function (mixed $feature): mixed {
                if (! is_array($feature)) {
                    return $feature;
                }

                $feature['is_included'] = filter_var($feature['is_included'] ?? false, FILTER_VALIDATE_BOOL);

                return $feature;
            })
            ->all();

        $prepared = [
            'is_popular' => $this->boolean('is_popular'),
            'is_active' => $this->has('is_active') ? $this->boolean('is_active') : true,
            'display_order' => $this->input('display_order', 0),
        ];

        if ($hasFeatures) {
            $prepared['features'] = $features;
        }

        $this->merge($prepared);
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
            'monthly_price' => ['required', 'numeric', 'min:0', 'max:999999.99', 'decimal:0,2'],
            'annual_price' => ['required', 'numeric', 'min:0', 'max:999999.99', 'decimal:0,2'],
            'display_order' => ['required', 'integer', 'between:0,10000'],
            'is_active' => ['required', 'boolean'],
            'is_popular' => ['required', 'boolean'],
            'popular_label' => ['nullable', 'string', 'max:255'],
            'button_text' => ['nullable', 'string', 'max:255'],
            'button_link' => ['nullable', 'string', Rule::in(array_keys(PricingPresentation::DESTINATION_OPTIONS))],
            'features_present' => ['nullable', 'boolean'],
            'features' => ['nullable', 'array', 'max:100'],
            'features.*' => ['array:name,is_included,order_by'],
            'features.*.name' => ['required', 'string', 'max:255'],
            'features.*.is_included' => ['required', 'boolean'],
            'features.*.order_by' => ['nullable', 'integer', 'between:0,10000'],
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
