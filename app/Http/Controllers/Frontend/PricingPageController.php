<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\PricingPageSetting;
use App\Models\PricingPlan;
use Illuminate\View\View;

class PricingPageController extends Controller
{
    public function __invoke(): View
    {
        $plans = PricingPlan::query()
            ->with('features')
            ->orderBy('monthly_price')
            ->orderBy('id')
            ->get();
        $setting = PricingPageSetting::query()->first();

        return view('frontend.pricing', [
            'plans' => $plans,
            'setting' => $setting,
            'planCtaUrls' => $plans->mapWithKeys(fn (PricingPlan $plan): array => [
                $plan->getKey() => $this->safeHttpUrl($plan->button_link),
            ]),
            'heroBadgeIcon' => $this->safeLucideIcon($setting?->hero_badge_icon),
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

    private function safeHttpUrl(?string $url): ?string
    {
        if (! filter_var($url, FILTER_VALIDATE_URL)) {
            return null;
        }

        return in_array(strtolower((string) parse_url($url, PHP_URL_SCHEME)), ['http', 'https'], true)
            ? $url
            : null;
    }

    private function safeLucideIcon(?string $icon): string
    {
        return is_string($icon) && preg_match('/^[a-z0-9-]+$/', $icon) === 1
            ? $icon
            : 'tag';
    }
}
