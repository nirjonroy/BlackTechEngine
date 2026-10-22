<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\FeaturePageSetting;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class FeaturesPageController extends Controller
{
    /** @var list<string> */
    private const FEATURE_ICONS = [
        'activity',
        'badge-check',
        'bar-chart-3',
        'brain-circuit',
        'calendar',
        'calendar-check',
        'calendar-days',
        'chart-no-axes-combined',
        'check',
        'check-circle-2',
        'circle-gauge',
        'cpu',
        'database',
        'dna',
        'file-check-2',
        'file-text',
        'globe-2',
        'layers',
        'lightbulb',
        'link-2',
        'map-pin',
        'megaphone',
        'messages-square',
        'network',
        'radar',
        'rocket',
        'search',
        'search-check',
        'share-2',
        'shield',
        'shield-check',
        'sliders',
        'sparkles',
        'target',
        'wand-sparkles',
        'workflow',
        'zap',
    ];

    public function __invoke(): View
    {
        $setting = FeaturePageSetting::query()->first();

        return view('frontend.features', [
            'setting' => $setting,
            'features' => $this->features($setting?->features_json),
            'testimonials' => $this->testimonials($setting?->testimonials_json),
            'seoFallbackTitle' => $setting?->hero_title_normal ?: 'Features | BlackTechEngine',
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

    /**
     * @param  array<int, array<string, mixed>>|null  $configuredFeatures
     * @return Collection<int, array{icon: string, title: string, description: string}>
     */
    private function features(?array $configuredFeatures): Collection
    {
        $referenceFeatures = collect([
            [
                'icon' => 'dna',
                'title' => 'Proprietary Brand DNA & Voice Extraction Engine',
                'description' => 'Build a consistent voice profile from your existing content and apply it across future publishing workflows.',
            ],
            [
                'icon' => 'cpu',
                'title' => 'Generative Engine Optimization (GEO 3.0)',
                'description' => 'Structure useful, citation-ready content for modern answer engines while retaining traditional search foundations.',
            ],
            [
                'icon' => 'calendar',
                'title' => 'Autonomous Content Calendar & Auto-Publisher',
                'description' => 'Organize a rolling publishing queue and coordinate approved content across supported publishing destinations.',
            ],
            [
                'icon' => 'share-2',
                'title' => 'Omnichannel Social Drip Engine',
                'description' => 'Repurpose approved articles into channel-specific formats for consistent distribution across social platforms.',
            ],
            [
                'icon' => 'map-pin',
                'title' => 'Local SEO & Google Business Autopilot',
                'description' => 'Support local publishing and reputation workflows designed for location-focused businesses and agencies.',
            ],
            [
                'icon' => 'search',
                'title' => 'Continuous Semantic Keyword & Gap Hunter',
                'description' => 'Monitor topic opportunities and organize content ideas around relevant search intent and competitive gaps.',
            ],
        ]);
        $configured = collect($configuredFeatures ?? [])->values();

        $features = $referenceFeatures->map(function (array $reference, int $index) use ($configured): array {
            $feature = $configured->get($index);

            if (! is_array($feature)) {
                return $reference;
            }

            return [
                'icon' => $this->safeLucideIcon($feature['icon'] ?? null, $reference['icon']),
                'title' => $this->nonEmptyString($feature['title'] ?? null) ?? $reference['title'],
                'description' => $this->nonEmptyString($feature['description'] ?? null) ?? $reference['description'],
            ];
        });

        $additionalFeatures = $configured
            ->slice($referenceFeatures->count())
            ->filter(fn (mixed $feature): bool => is_array($feature)
                && $this->nonEmptyString($feature['title'] ?? null) !== null
                && $this->nonEmptyString($feature['description'] ?? null) !== null)
            ->map(fn (array $feature): array => [
                'icon' => $this->safeLucideIcon($feature['icon'] ?? null),
                'title' => $this->nonEmptyString($feature['title']) ?? '',
                'description' => $this->nonEmptyString($feature['description']) ?? '',
            ]);

        return $features->concat($additionalFeatures)->values();
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
            ->values();
    }

    private function safeLucideIcon(mixed $icon, string $fallback = 'sparkles'): string
    {
        return is_string($icon) && in_array($icon, self::FEATURE_ICONS, true)
            ? $icon
            : $fallback;
    }

    private function nonEmptyString(mixed $value): ?string
    {
        return is_string($value) && filled(trim($value)) ? $value : null;
    }
}
