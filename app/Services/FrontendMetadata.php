<?php

namespace App\Services;

use App\Models\SeoSetting;
use App\Models\SiteSetting;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class FrontendMetadata
{
    /**
     * @return array{
     *     siteSetting: SiteSetting|null,
     *     seo: array<string, string|null>,
     *     analytics: array{provider: 'ga4'|'gtm', id: string}|null
     * }
     */
    public function resolve(
        ?string $routeName,
        ?string $fallbackTitle = null,
        ?string $fallbackDescription = null,
        array $pageSeo = [],
    ): array {
        $siteSetting = SiteSetting::query()->first();
        $seoSetting = $this->seoSettingFor($routeName);
        $title = ($pageSeo['meta_title'] ?? null)
            ?: ($pageSeo['seo_title'] ?? null)
            ?: $seoSetting?->meta_title
            ?: $seoSetting?->seo_title
            ?: $fallbackTitle
            ?: $siteSetting?->site_name
            ?: 'BlackTechEngine';
        $description = ($pageSeo['meta_description'] ?? null)
            ?: ($pageSeo['seo_description'] ?? null)
            ?: $seoSetting?->meta_description
            ?: $seoSetting?->seo_description
            ?: $fallbackDescription;

        return [
            'siteSetting' => $siteSetting,
            'seo' => [
                'title' => $title,
                'description' => $description,
                'keywords' => ($pageSeo['keywords'] ?? null) ?: $seoSetting?->keywords,
                'author' => ($pageSeo['author'] ?? null) ?: $seoSetting?->author,
                'publisher' => ($pageSeo['publisher'] ?? null) ?: $seoSetting?->publisher,
                'copyright' => ($pageSeo['copyright'] ?? null) ?: $seoSetting?->copyright,
                'siteName' => ($pageSeo['site_name'] ?? null) ?: $seoSetting?->site_name ?: $siteSetting?->site_name ?: 'BlackTechEngine',
                'image' => $this->metaImageUrl($pageSeo['meta_image'] ?? null) ?: $this->metaImageUrl($seoSetting?->meta_image),
                'canonical' => url()->current(),
                'favicon' => $siteSetting?->favicon ? asset('storage/'.$siteSetting->favicon) : null,
            ],
            'analytics' => $this->analyticsConfiguration(),
        ];
    }

    private function seoSettingFor(?string $routeName): ?SeoSetting
    {
        $pageNames = match ($routeName) {
            'home' => ['home', 'homepage', 'home page'],
            'about' => ['about', 'about page', 'about geo', 'about us'],
            'features' => ['features', 'features page', 'platform features', 'product features'],
            'pricing' => ['pricing', 'pricing page', 'plans', 'plans and pricing'],
            default => [],
        };
        $globalNames = ['', 'default', 'global', 'site'];
        $eligibleNames = [...$pageNames, ...$globalNames];
        $placeholders = implode(', ', array_fill(0, count($eligibleNames), '?'));
        $pagePlaceholders = implode(', ', array_fill(0, max(count($pageNames), 1), '?'));
        $pageBindings = $pageNames === [] ? [''] : $pageNames;

        return SeoSetting::query()
            ->whereRaw("LOWER(TRIM(COALESCE(page_name, ''))) IN ($placeholders)", $eligibleNames)
            ->orderByRaw("CASE WHEN LOWER(TRIM(COALESCE(page_name, ''))) IN ($pagePlaceholders) THEN 0 ELSE 1 END", $pageBindings)
            ->orderBy('id')
            ->first();
    }

    private function metaImageUrl(?string $image): ?string
    {
        if (! filled($image)) {
            return null;
        }

        if (filter_var($image, FILTER_VALIDATE_URL)) {
            $scheme = strtolower((string) parse_url($image, PHP_URL_SCHEME));

            return in_array($scheme, ['http', 'https'], true) ? $image : null;
        }

        if (str_contains($image, '..')) {
            return null;
        }

        return asset('storage/'.ltrim($image, '/'));
    }

    /**
     * @return array{provider: 'ga4'|'gtm', id: string}|null
     */
    private function analyticsConfiguration(): ?array
    {
        if (! Schema::hasTable('google_analytics')) {
            return null;
        }

        $record = DB::table('google_analytics')->orderBy('id')->first();

        if (! $record || (property_exists($record, 'status') && (int) $record->status !== 1)) {
            return null;
        }

        foreach ([$record->analytic_id ?? null, $record->analytic_script ?? null] as $candidate) {
            if (! is_string($candidate)) {
                continue;
            }

            if (preg_match('/(?<![A-Z0-9-])(GTM-[A-Z0-9]{4,20})(?![A-Z0-9])/i', $candidate, $matches) === 1) {
                return ['provider' => 'gtm', 'id' => strtoupper($matches[1])];
            }

            if (preg_match('/(?<![A-Z0-9-])(G-[A-Z0-9]{4,20})(?![A-Z0-9])/i', $candidate, $matches) === 1) {
                return ['provider' => 'ga4', 'id' => strtoupper($matches[1])];
            }
        }

        return null;
    }
}
