<?php

namespace App\Services\BrandDna;

use App\Models\Brand;
use Illuminate\Support\Str;

class BrandDnaAnalyzer
{
    /**
     * Analyze crawled content and produce a structured Brand DNA profile.
     *
     * @param  array{
     *     crawled_urls: list<string>,
     *     raw_summary: string,
     *     meta_titles: list<string>,
     *     meta_descriptions: list<string>,
     *     headings: list<string>,
     *     keywords: list<string>
     * }  $crawlData
     * @return array{
     *     brand_identity: string,
     *     business_description: string,
     *     industry: string,
     *     target_audience: string,
     *     primary_market: string,
     *     voice_tone: string,
     *     key_services: list<string>,
     *     suggested_keywords: list<string>,
     *     content_themes: list<string>,
     *     brand_values: list<string>,
     *     competitor_differentiation: string
     * }
     */
    public function analyze(Brand $brand, array $crawlData): array
    {
        // When AI generation is enabled, this would invoke the AI model.
        // For development safety, we provide a deterministic, intelligent heuristic analyzer.
        return $this->heuristicAnalysis($brand, $crawlData);
    }

    /**
     * Heuristic analysis of crawled website content.
     *
     * @param  array{
     *     crawled_urls: list<string>,
     *     raw_summary: string,
     *     meta_titles: list<string>,
     *     meta_descriptions: list<string>,
     *     headings: list<string>,
     *     keywords: list<string>
     * }  $crawlData
     * @return array<string, mixed>
     */
    private function heuristicAnalysis(Brand $brand, array $crawlData): array
    {
        $metaDesc = ! empty($crawlData['meta_descriptions'])
            ? $crawlData['meta_descriptions'][0]
            : ($brand->business_description ?: "{$brand->name} provides digital products and solutions.");

        $title = ! empty($crawlData['meta_titles'])
            ? $crawlData['meta_titles'][0]
            : "{$brand->name} - {$brand->industry}";

        // Key services from headings or seed categories
        $services = [];
        foreach ($crawlData['headings'] as $heading) {
            if (count($services) >= 5) {
                break;
            }
            if (Str::length($heading) >= 5 && Str::length($heading) <= 60 && ! Str::contains(strtolower($heading), ['cookie', 'privacy', 'term', 'menu', 'contact'])) {
                $services[] = $heading;
            }
        }

        if (empty($services)) {
            $services = $brand->seo_topic_categories ?: ['Core Product Solutions', 'Professional Services'];
        }

        // Keywords
        $keywords = array_values(array_unique(array_merge(
            $crawlData['keywords'],
            array_map(fn ($s) => strtolower($s), array_slice($services, 0, 3)),
            [$brand->industry ? strtolower($brand->industry) : 'saas platform'],
        )));

        // Content themes
        $contentThemes = array_values(array_unique(array_merge(
            $brand->seo_topic_categories ?: [],
            array_slice($services, 0, 4),
            ['Industry Insights', 'Best Practices', 'Product Guides']
        )));

        // Voice Tone
        $voiceTone = $brand->voice_tone ?: 'Clear, authoritative, and practical';

        // Identity
        $brandIdentity = "{$brand->name} is dedicated to delivering {$brand->industry} solutions, positioning itself as a reliable and innovative partner for {$brand->target_audience}.";

        // Competitor Differentiation
        $differentiation = 'Focused on delivering direct, measurable value through streamlined workflows and reliable customer support.';

        // Brand Values
        $brandValues = ['Reliability', 'Customer Centricity', 'Continuous Innovation', 'Transparency'];

        return [
            'brand_identity' => $brandIdentity,
            'business_description' => $metaDesc,
            'industry' => $brand->industry ?: 'SaaS',
            'target_audience' => $brand->target_audience ?: 'Business professionals and digital teams',
            'primary_market' => $brand->primary_market ?: 'United States',
            'voice_tone' => $voiceTone,
            'key_services' => array_values(array_slice($services, 0, 8)),
            'suggested_keywords' => array_values(array_slice($keywords, 0, 15)),
            'content_themes' => array_values(array_slice($contentThemes, 0, 8)),
            'brand_values' => $brandValues,
            'competitor_differentiation' => $differentiation,
        ];
    }
}
