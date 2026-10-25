<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\FeaturePageSetting;
use App\Services\FeaturePageContent;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class FeaturesPageController extends Controller
{
    public function __invoke(FeaturePageContent $content): View
    {
        $setting = FeaturePageSetting::query()->first();
        $features = $content->features($setting?->features_json);

        return view('frontend.features', [
            'setting' => $setting,
            'deepDiveFeatures' => $features->take(4),
            'additionalFeatures' => $features->slice(4)->values(),
            'testimonials' => $this->testimonials($setting?->testimonials_json),
            'primaryCtaUrl' => $content->destinationUrl($setting?->final_primary_cta_destination, 'register'),
            'secondaryCtaUrl' => $content->destinationUrl($setting?->final_secondary_cta_destination, 'pricing'),
            'seoFallbackTitle' => $setting?->hero_title_normal ?: 'Features | BlackTechEngine',
            'seoFallbackDescription' => $setting?->hero_description
                ?: 'Explore BlackTechEngine features for content workflows, search optimization, and generative engine visibility.',
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

    private function nonEmptyString(mixed $value): ?string
    {
        return is_string($value) && filled(trim($value)) ? $value : null;
    }
}
