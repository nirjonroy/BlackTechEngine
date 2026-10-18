<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PricingPlan extends Model
{
    protected $fillable = [
        'name',
        'badge',
        'title',
        'description',
        'monthly_price',
        'annual_price',
        'is_popular',
        'button_text',
        'button_link',
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

    public function features()
    {
        return $this->hasMany(PricingPlanFeature::class)->orderBy('order_by');
    }
}
