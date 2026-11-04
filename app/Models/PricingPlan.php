<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PricingPlan extends Model
{
    use HasFactory;

    protected $attributes = [
        'display_order' => 0,
        'is_active' => true,
        'show_on_pricing_page' => true,
        'billing_currency' => 'USD',
        'billing_version' => 1,
        'trial_days' => 0,
        'is_billable' => false,
    ];

    protected $fillable = [
        'name',
        'code',
        'badge',
        'title',
        'description',
        'monthly_price',
        'annual_price',
        'billing_currency',
        'monthly_price_minor',
        'annual_price_minor',
        'billing_version',
        'trial_days',
        'display_order',
        'is_active',
        'show_on_pricing_page',
        'is_billable',
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

    /** @return HasMany<PricingPlanEntitlement, $this> */
    public function entitlements(): HasMany
    {
        return $this->hasMany(PricingPlanEntitlement::class)->orderBy('key');
    }

    /** @return HasMany<WorkspaceSubscription, $this> */
    public function subscriptions(): HasMany
    {
        return $this->hasMany(WorkspaceSubscription::class);
    }

    public function scopePubliclyVisible(Builder $query): Builder
    {
        return $query
            ->where('is_active', true)
            ->where('show_on_pricing_page', true);
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
            'monthly_price_minor' => 'integer',
            'annual_price_minor' => 'integer',
            'billing_version' => 'integer',
            'trial_days' => 'integer',
            'display_order' => 'integer',
            'is_active' => 'boolean',
            'show_on_pricing_page' => 'boolean',
            'is_billable' => 'boolean',
            'is_popular' => 'boolean',
        ];
    }
}
