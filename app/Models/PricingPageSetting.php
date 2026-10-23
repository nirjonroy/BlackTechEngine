<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PricingPageSetting extends Model
{
    protected $attributes = [
        'currency_code' => 'USD',
        'calc_default_articles' => 16,
        'calc_default_cost_per_article' => 150,
    ];

    protected $fillable = [
        'page_name',
        'hero_badge_icon',
        'hero_badge_text',
        'hero_title_normal',
        'hero_title_highlighted',
        'hero_description',
        'currency_code',
        'plans_heading',
        'billing_monthly_label',
        'billing_annual_label',
        'annual_billing_description',
        'monthly_period_label',
        'annual_period_label',
        'empty_state_title',
        'empty_state_description',
        'calc_title',
        'calc_description',
        'calc_articles_label',
        'calc_articles_unit',
        'calc_default_articles',
        'calc_cost_label',
        'calc_default_cost_per_article',
        'calc_monthly_result_label',
        'calc_annual_result_label',
        'calc_disclaimer',
        'faq_title',
        'faq_description',
        'faqs',
        'faq_empty_state',
        'final_cta_heading',
        'final_cta_description',
        'final_primary_cta_label',
        'final_primary_cta_destination',
        'final_secondary_cta_label',
        'final_secondary_cta_destination',
        'unavailable_plan_label',
        'seo_title',
        'seo_description',
        'meta_title',
        'meta_description',
        'meta_image',
        'author',
        'publisher',
        'copyright',
        'site_name',
        'keywords',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'faqs' => 'array',
            'calc_default_articles' => 'integer',
            'calc_default_cost_per_article' => 'decimal:2',
        ];
    }
}
