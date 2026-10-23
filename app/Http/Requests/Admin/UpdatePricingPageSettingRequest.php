<?php

namespace App\Http\Requests\Admin;

use App\Services\PricingPresentation;
use Illuminate\Validation\Rule;

class UpdatePricingPageSettingRequest extends AdminFormRequest
{
    protected function prepareForValidation(): void
    {
        if ($this->filled('currency_code')) {
            $this->merge(['currency_code' => strtoupper($this->string('currency_code')->toString())]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'hero_badge_icon' => ['nullable', 'string', Rule::in(array_keys(PricingPresentation::ICON_OPTIONS))],
            'hero_badge_text' => ['nullable', 'string', 'max:255'],
            'hero_title_normal' => ['nullable', 'string', 'max:255'],
            'hero_title_highlighted' => ['nullable', 'string', 'max:255'],
            'hero_description' => ['nullable', 'string', 'max:5000'],
            'currency_code' => ['nullable', 'string', Rule::in(array_keys(PricingPresentation::CURRENCY_OPTIONS))],
            'plans_heading' => ['nullable', 'string', 'max:255'],
            'billing_monthly_label' => ['nullable', 'string', 'max:255'],
            'billing_annual_label' => ['nullable', 'string', 'max:255'],
            'annual_billing_description' => ['nullable', 'string', 'max:1000'],
            'monthly_period_label' => ['nullable', 'string', 'max:100'],
            'annual_period_label' => ['nullable', 'string', 'max:100'],
            'empty_state_title' => ['nullable', 'string', 'max:255'],
            'empty_state_description' => ['nullable', 'string', 'max:1000'],
            'calc_title' => ['nullable', 'string', 'max:255'],
            'calc_description' => ['nullable', 'string', 'max:5000'],
            'calc_articles_label' => ['nullable', 'string', 'max:255'],
            'calc_articles_unit' => ['nullable', 'string', 'max:100'],
            'calc_default_articles' => ['nullable', 'integer', 'between:4,60'],
            'calc_cost_label' => ['nullable', 'string', 'max:255'],
            'calc_default_cost_per_article' => ['nullable', 'numeric', 'between:50,400', 'decimal:0,2'],
            'calc_monthly_result_label' => ['nullable', 'string', 'max:255'],
            'calc_annual_result_label' => ['nullable', 'string', 'max:255'],
            'calc_disclaimer' => ['nullable', 'string', 'max:1000'],
            'faq_title' => ['nullable', 'string', 'max:255'],
            'faq_description' => ['nullable', 'string', 'max:5000'],
            'faqs' => ['nullable', 'array', 'max:100'],
            'faqs.*' => ['array:question,answer'],
            'faqs.*.question' => ['required', 'string', 'max:500'],
            'faqs.*.answer' => ['required', 'string', 'max:5000'],
            'faq_empty_state' => ['nullable', 'string', 'max:500'],
            'final_cta_heading' => ['nullable', 'string', 'max:255'],
            'final_cta_description' => ['nullable', 'string', 'max:5000'],
            'final_primary_cta_label' => ['nullable', 'string', 'max:255'],
            'final_primary_cta_destination' => ['nullable', 'string', Rule::in(array_keys(PricingPresentation::DESTINATION_OPTIONS))],
            'final_secondary_cta_label' => ['nullable', 'string', 'max:255'],
            'final_secondary_cta_destination' => ['nullable', 'string', Rule::in(array_keys(PricingPresentation::DESTINATION_OPTIONS))],
            'unavailable_plan_label' => ['nullable', 'string', 'max:255'],
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
