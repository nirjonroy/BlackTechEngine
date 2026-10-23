<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SiteSetting extends Model
{
    protected $fillable = [
        'site_name',
        'logo_dark',
        'logo_light',
        'favicon',
        'email',
        'mobile',
        'address',
        'facebook_url',
        'header_navigation',
        'footer_description',
        'footer_columns',
        'footer_copyright',
        'x_url',
        'linkedin_url',
        'github_url',
        'youtube_url',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'header_navigation' => 'array',
            'footer_columns' => 'array',
        ];
    }
}
