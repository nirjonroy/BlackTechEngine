<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\PricingPageSetting;
use App\Models\PricingPlan;
use App\Services\PricingPresentation;
use Illuminate\View\View;

class PricingPageController extends Controller
{
    public function __invoke(PricingPresentation $presentation): View
    {
        $setting = PricingPageSetting::query()->first();
        $currencyCode = $presentation->currencyCode($setting?->currency_code);
        $plans = PricingPlan::query()
            ->with('features')
            ->publiclyVisible()
            ->inDisplayOrder()
            ->get();

        return view('frontend.pricing', [
            'plans' => $plans,
            'setting' => $setting,
            'planCtaUrls' => $plans->mapWithKeys(fn (PricingPlan $plan): array => [
                $plan->getKey() => $presentation->destinationUrl($plan->button_link),
            ]),
            'formattedPlanPrices' => $plans->mapWithKeys(fn (PricingPlan $plan): array => [
                $plan->getKey() => [
                    'monthly' => $presentation->format($plan->monthly_price, $currencyCode),
                    'annual' => $presentation->format($plan->annual_price, $currencyCode),
                ],
            ]),
            'currencyCode' => $currencyCode,
            'heroBadgeIcon' => $presentation->safeIcon($setting?->hero_badge_icon),
            'finalPrimaryCtaUrl' => $presentation->destinationUrl($setting?->final_primary_cta_destination, 'register'),
            'finalSecondaryCtaUrl' => $presentation->destinationUrl($setting?->final_secondary_cta_destination, 'features'),
            'seoFallbackTitle' => $setting?->hero_title_normal ?: 'Pricing | BlackTechEngine',
            'seoFallbackDescription' => $setting?->hero_description,
            'pageSeo' => $setting?->only([
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
            ]) ?? [],
        ]);
    }
}
