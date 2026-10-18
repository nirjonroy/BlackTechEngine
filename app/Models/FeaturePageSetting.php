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
        'calc_title',
        'calc_description',
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
    ];

    protected $casts = [
        'features_json' => 'array',
        'testimonials_json' => 'array',
    ];
}
