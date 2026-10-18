<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AboutPage extends Model
{
    protected $fillable = [
        'hero_badge',
        'hero_title',
        'hero_description',
        'comparison_title',
        'comparison_description',
        'architecture_badge',
        'architecture_title',
        'architecture_description',
        'core_values_title',
        'core_values_description',
        'cta_title',
        'cta_description',
        'cta_btn1_text',
        'cta_btn1_link',
        'cta_btn2_text',
        'cta_btn2_link',
    ];

    // Relationships
    public function stats()
    {
        return $this->hasMany(AboutStat::class)->orderBy('order_by');
    }

    public function comparisons()
    {
        return $this->hasMany(AboutComparison::class)->orderBy('order_by');
    }

    public function architectures()
    {
        return $this->hasMany(AboutArchitecture::class)->orderBy('order_by');
    }

    public function coreValues()
    {
        return $this->hasMany(AboutCoreValue::class)->orderBy('order_by');
    }
}
