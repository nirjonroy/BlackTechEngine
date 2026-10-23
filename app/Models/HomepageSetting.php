<?php

namespace App\Models;

use Database\Factories\HomepageSettingFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HomepageSetting extends Model
{
    /** @use HasFactory<HomepageSettingFactory> */
    use HasFactory;

    /** @var array<string, string> */
    public const DESTINATION_OPTIONS = [
        'register' => 'Create account',
        'login' => 'Log in',
        'pricing' => 'Pricing page',
        'features' => 'Features page',
        'about' => 'About page',
        'blog.index' => 'Blog',
        'affiliate' => 'Affiliate page',
        'scanner' => 'Homepage scanner',
    ];

    protected $fillable = [
        'hero_badge',
        'hero_title',
        'hero_highlighted_title',
        'hero_description',
        'primary_cta_label',
        'primary_cta_destination',
        'secondary_cta_label',
        'secondary_cta_destination',
        'scanner_heading',
        'scanner_description',
        'scanner_button_label',
        'integrations_heading',
        'integrations_description',
        'features_badge',
        'features_heading',
        'features_description',
        'calculator_badge',
        'calculator_heading',
        'calculator_description',
        'calculator_note',
        'calculator_cta_label',
        'calculator_cta_destination',
        'final_cta_heading',
        'final_cta_description',
        'final_primary_cta_label',
        'final_primary_cta_destination',
        'final_secondary_cta_label',
        'final_secondary_cta_destination',
    ];
}
