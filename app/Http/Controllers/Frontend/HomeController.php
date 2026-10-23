<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\AboutPage;
use App\Models\FeaturePageSetting;
use App\Models\HomepageSetting;
use App\Models\PricingPlan;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class HomeController extends Controller
{
    /** @var list<string> */
    private const FEATURE_ICONS = [
        'calendar',
        'cpu',
        'dna',
        'file-text',
        'layout',
        'map-pin',
        'search',
        'share-2',
        'shield-check',
        'shopping-bag',
        'sparkles',
    ];

    public function index(): View
    {
        $featureSetting = FeaturePageSetting::query()->first();
        $homepageSetting = HomepageSetting::query()->first();
        $aboutStats = AboutPage::query()
            ->with('stats')
            ->first()
            ?->stats
            ->take(4) ?? collect();

        return view('frontend.home', [
            'featureSetting' => $featureSetting,
            'homepageSetting' => $homepageSetting,
            'primaryCtaUrl' => $this->destinationUrl($homepageSetting?->primary_cta_destination, 'register'),
            'secondaryCtaUrl' => $this->destinationUrl($homepageSetting?->secondary_cta_destination, 'scanner'),
            'calculatorCtaUrl' => $this->destinationUrl($homepageSetting?->calculator_cta_destination, 'pricing'),
            'finalPrimaryCtaUrl' => $this->destinationUrl($homepageSetting?->final_primary_cta_destination, 'register'),
            'finalSecondaryCtaUrl' => $this->destinationUrl($homepageSetting?->final_secondary_cta_destination, 'pricing'),
            'homepageFeatures' => $this->features($featureSetting?->features_json),
            'testimonials' => $this->testimonials($featureSetting?->testimonials_json),
            'aboutStats' => $aboutStats,
            'startingMonthlyPrice' => PricingPlan::query()
                ->where('monthly_price', '>', 0)
                ->orderBy('monthly_price')
                ->value('monthly_price'),
            'seoFallbackDescription' => 'Autonomous SEO and generative engine optimization for modern growth teams.',
        ]);
    }

    /**
     * @param  array<int, array<string, mixed>>|null  $configuredFeatures
     * @return Collection<int, array{icon: string, title: string, description: string}>
     */
    private function features(?array $configuredFeatures): Collection
    {
        $referenceFeatures = collect([
            ['icon' => 'calendar', 'title' => 'Visual Content Calendar', 'description' => 'Coordinate topic research, drafting, review, and publishing in one visible workflow.'],
            ['icon' => 'dna', 'title' => 'Brand DNA Extractor', 'description' => 'Apply approved voice guidance, terminology, and internal-linking context to content workflows.'],
            ['icon' => 'share-2', 'title' => 'Social Media Studio', 'description' => 'Prepare channel-specific social formats from approved long-form content.'],
            ['icon' => 'map-pin', 'title' => 'Google Business Workflows', 'description' => 'Support location-focused publishing and customer-response preparation.'],
            ['icon' => 'cpu', 'title' => 'GEO Content Structure', 'description' => 'Organize direct answers, entities, and source context for modern answer engines.'],
            ['icon' => 'file-text', 'title' => 'SEO Content Editor', 'description' => 'Review content structure and publishing readiness before sending work to a connected CMS.'],
        ]);
        $configured = collect($configuredFeatures ?? [])->values();

        return $referenceFeatures
            ->map(function (array $reference, int $index) use ($configured): array {
                $feature = $configured->get($index);

                if (! is_array($feature)) {
                    return $reference;
                }

                return [
                    'icon' => $this->safeLucideIcon($feature['icon'] ?? null, $reference['icon']),
                    'title' => $this->nonEmptyString($feature['title'] ?? null) ?? $reference['title'],
                    'description' => $this->nonEmptyString($feature['description'] ?? null) ?? $reference['description'],
                ];
            })
            ->values();
    }

    /**
     * @param  array<int, array<string, mixed>>|null  $configuredTestimonials
     * @return Collection<int, array{quote: string, author_initials: string|null, author_name: string, author_role: string|null}>
     */
    private function testimonials(?array $configuredTestimonials): Collection
    {
        return collect($configuredTestimonials ?? [])
            ->filter(fn (mixed $testimonial): bool => is_array($testimonial)
                && $this->nonEmptyString($testimonial['quote'] ?? null) !== null
                && $this->nonEmptyString($testimonial['author_name'] ?? null) !== null)
            ->map(fn (array $testimonial): array => [
                'quote' => $this->nonEmptyString($testimonial['quote']) ?? '',
                'author_initials' => $this->nonEmptyString($testimonial['author_initials'] ?? null),
                'author_name' => $this->nonEmptyString($testimonial['author_name']) ?? '',
                'author_role' => $this->nonEmptyString($testimonial['author_role'] ?? null),
            ])
            ->take(3)
            ->values();
    }

    private function safeLucideIcon(mixed $icon, string $fallback): string
    {
        return is_string($icon) && in_array($icon, self::FEATURE_ICONS, true)
            ? $icon
            : $fallback;
    }

    private function nonEmptyString(mixed $value): ?string
    {
        return is_string($value) && filled(trim($value)) ? $value : null;
    }

    private function destinationUrl(?string $destination, string $fallback): string
    {
        $destination = array_key_exists((string) $destination, HomepageSetting::DESTINATION_OPTIONS)
            ? $destination
            : $fallback;

        return $destination === 'scanner'
            ? route('home').'#website-scanner'
            : route($destination);
    }
}
