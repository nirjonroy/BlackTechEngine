<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PricingPageSetting extends Model
{
    protected $fillable = [
        'page_name',
        'hero_badge_icon',
        'hero_badge_text',
        'hero_title_normal',
        'hero_title_highlighted',
        'hero_description',
        'calc_title',
        'calc_description',
        'faq_title',
        'faq_description',
        'faqs',
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
        'faqs' => 'array',
    ];
}
