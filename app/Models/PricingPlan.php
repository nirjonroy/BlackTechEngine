<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PricingPlan extends Model
{
    protected $attributes = [
        'display_order' => 0,
        'is_active' => true,
    ];

    protected $fillable = [
        'name',
        'badge',
        'title',
        'description',
        'monthly_price',
        'annual_price',
        'display_order',
        'is_active',
        'is_popular',
        'popular_label',
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

    /**
     * @return HasMany<PricingPlanFeature, $this>
     */
    public function features(): HasMany
    {
        return $this->hasMany(PricingPlanFeature::class)
            ->orderBy('order_by')
            ->orderBy('id');
    }

    public function scopePubliclyVisible(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeInDisplayOrder(Builder $query): Builder
    {
        return $query
            ->orderBy('display_order')
            ->orderBy('monthly_price')
            ->orderBy('id');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'monthly_price' => 'decimal:2',
            'annual_price' => 'decimal:2',
            'display_order' => 'integer',
            'is_active' => 'boolean',
            'is_popular' => 'boolean',
        ];
    }
}
