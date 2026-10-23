<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FeaturePageSetting extends Model
{
    protected $fillable = [
        'page_name',
        'hero_badge',
        'hero_title_normal',
        'hero_title_highlighted',
        'hero_description',
        'features_json',
        'additional_features_heading',
        'additional_features_description',
        'calc_title',
        'calc_description',
        'calc_disclaimer',
        'testimonial_badge',
        'testimonial_title',
        'testimonials_json',
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
        'final_cta_heading',
        'final_cta_description',
        'final_primary_cta_label',
        'final_primary_cta_destination',
        'final_secondary_cta_label',
        'final_secondary_cta_destination',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'features_json' => 'array',
            'testimonials_json' => 'array',
        ];
    }
}
