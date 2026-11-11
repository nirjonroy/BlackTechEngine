<?php

namespace App\Services\BrandDna;

use App\Models\Brand;
use App\Services\WordPress\WordPressUrlValidator;
use DOMDocument;
use DOMXPath;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;
use Throwable;

class BrandDnaCrawler
{
    public function __construct(
        private readonly WordPressUrlValidator $urlValidator,
    ) {}

    /**
     * Crawl the brand's public website to extract content for Brand DNA analysis.
     *
     * @param  list<string>|null  $customUrls
     * @return array{
     *     crawled_urls: list<string>,
     *     raw_summary: string,
     *     meta_titles: list<string>,
     *     meta_descriptions: list<string>,
     *     headings: list<string>,
     *     keywords: list<string>
     * }
     *
     * @throws ValidationException
     */
    public function crawl(Brand $brand, ?array $customUrls = null): array
    {
        $siteUrl = $brand->website_url;
        if (empty($siteUrl)) {
            throw ValidationException::withMessages([
                'website_url' => 'Brand website URL is required for Brand DNA analysis.',
            ]);
        }

        $resolved = $this->urlValidator->validateAndResolve($siteUrl);
        $baseUrl = rtrim($resolved['normalized_url'], '/');

        $urlsToVisit = [];
        if (! empty($customUrls)) {
            foreach ($customUrls as $customUrl) {
                try {
                    $customResolved = $this->urlValidator->validateAndResolve($customUrl);
                    if ($customResolved['host'] === $resolved['host']) {
                        $urlsToVisit[] = $customResolved['normalized_url'];
                    }
                } catch (Throwable) {
                    // Skip invalid custom URLs
                }
            }
        }

        if (empty($urlsToVisit)) {
            $urlsToVisit = [
                $baseUrl,
                $baseUrl.'/about',
                $baseUrl.'/about-us',
                $baseUrl.'/services',
            ];
        }

        $urlsToVisit = array_unique($urlsToVisit);
        $maxPages = (int) config('ai.brand_dna_max_crawl_pages', 5);
        $crawledUrls = [];
        $metaTitles = [];
        $metaDescriptions = [];
        $headings = [];
        $discoveredKeywords = [];
        $textSections = [];

        foreach ($urlsToVisit as $url) {
            if (count($crawledUrls) >= $maxPages) {
                break;
            }

            try {
                $pageResolved = $this->urlValidator->validateAndResolve($url);
                if ($pageResolved['host'] !== $resolved['host']) {
                    continue;
                }

                $response = $this->fetchPage($pageResolved);
                if (! $response->successful()) {
                    continue;
                }

                $html = $response->body();
                if (strlen($html) > 1048576) {
                    $html = substr($html, 0, 1048576);
                }

                $parsed = $this->parseHtml($html);
                $crawledUrls[] = $url;

                if (! empty($parsed['title'])) {
                    $metaTitles[] = $parsed['title'];
                }
                if (! empty($parsed['description'])) {
                    $metaDescriptions[] = $parsed['description'];
                }
                if (! empty($parsed['keywords'])) {
                    $discoveredKeywords = array_merge($discoveredKeywords, $parsed['keywords']);
                }
                if (! empty($parsed['headings'])) {
                    $headings = array_merge($headings, $parsed['headings']);
                }
                if (! empty($parsed['clean_text'])) {
                    $textSections[] = "=== Page: {$url} ===\n".$parsed['clean_text'];
                }
            } catch (Throwable) {
                // Continue crawling remaining pages
            }
        }

        if (empty($crawledUrls)) {
            throw ValidationException::withMessages([
                'website_url' => 'Unable to reach or extract public content from the brand website. Please check the URL and connectivity.',
            ]);
        }

        $rawSummary = implode("\n\n", $textSections);
        if (strlen($rawSummary) > 15000) {
            $rawSummary = substr($rawSummary, 0, 15000);
        }

        return [
            'crawled_urls' => $crawledUrls,
            'raw_summary' => $rawSummary,
            'meta_titles' => array_values(array_unique($metaTitles)),
            'meta_descriptions' => array_values(array_unique($metaDescriptions)),
            'headings' => array_values(array_unique(array_slice($headings, 0, 30))),
            'keywords' => array_values(array_unique(array_slice($discoveredKeywords, 0, 20))),
        ];
    }

    /**
     * Fetch HTML with pinned IP and strict SSRF timeout options.
     *
     * @param  array{normalized_url: string, host: string, port: int, pinned_ip: ?string}  $resolved
     */
    private function fetchPage(array $resolved): Response
    {
        $options = [];
        if (! empty($resolved['pinned_ip'])) {
            $options['curl'] = [
                CURLOPT_RESOLVE => [
                    "{$resolved['host']}:{$resolved['port']}:{$resolved['pinned_ip']}",
                ],
            ];
        }

        /** @var PendingRequest $request */
        $request = Http::timeout((int) config('ai.brand_dna_crawl_timeout', 10))
            ->connectTimeout(5)
            ->withUserAgent('BlackTechEngine-BrandDNA/1.0')
            ->withoutRedirecting()
            ->withOptions($options);

        return $request->get($resolved['normalized_url']);
    }

    /**
     * Safely parse and sanitize HTML to extract title, meta tags, headings, and clean text.
     *
     * @return array{title: string, description: string, keywords: list<string>, headings: list<string>, clean_text: string}
     */
    private function parseHtml(string $html): array
    {
        $title = '';
        $description = '';
        $keywords = [];
        $headings = [];

        // Suppress XML/HTML parsing errors for messy HTML
        $libxmlState = libxml_use_internal_errors(true);
        $dom = new DOMDocument;
        @$dom->loadHTML('<?xml encoding="utf-8" ?>'.$html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();
        libxml_use_internal_errors($libxmlState);

        $xpath = new DOMXPath($dom);

        // Extract Title
        $titleNodes = $xpath->query('//title');
        if ($titleNodes && $titleNodes->length > 0) {
            $title = trim($titleNodes->item(0)->textContent);
        }

        // Extract Meta Description
        $descNodes = $xpath->query('//meta[translate(@name, "DESCRIPTION", "description")="description"]/@content');
        if ($descNodes && $descNodes->length > 0) {
            $description = trim($descNodes->item(0)->nodeValue);
        }

        if (empty($description)) {
            $ogDescNodes = $xpath->query('//meta[@property="og:description"]/@content');
            if ($ogDescNodes && $ogDescNodes->length > 0) {
                $description = trim($ogDescNodes->item(0)->nodeValue);
            }
        }

        // Extract Meta Keywords
        $kwNodes = $xpath->query('//meta[translate(@name, "KEYWORDS", "keywords")="keywords"]/@content');
        if ($kwNodes && $kwNodes->length > 0) {
            $rawKw = $kwNodes->item(0)->nodeValue;
            $keywords = array_values(array_filter(array_map('trim', explode(',', $rawKw))));
        }

        // Extract Headings
        $headingNodes = $xpath->query('//h1 | //h2 | //h3');
        if ($headingNodes) {
            foreach ($headingNodes as $h) {
                $hText = trim(preg_replace('/\s+/', ' ', $h->textContent));
                if (strlen($hText) > 3 && strlen($hText) < 150) {
                    $headings[] = $hText;
                }
            }
        }

        // Remove script, style, nav, footer, header tags for clean text extraction
        $removeNodes = $xpath->query('//script | //style | //nav | //footer | //header | //noscript | //svg');
        if ($removeNodes) {
            foreach ($removeNodes as $node) {
                $node->parentNode?->removeChild($node);
            }
        }

        $bodyText = '';
        $bodyNodes = $xpath->query('//body');
        if ($bodyNodes && $bodyNodes->length > 0) {
            $bodyText = $bodyNodes->item(0)->textContent;
        } else {
            $bodyText = $dom->textContent;
        }

        $cleanText = trim(preg_replace('/\s+/', ' ', $bodyText));
        if (strlen($cleanText) > 5000) {
            $cleanText = substr($cleanText, 0, 5000);
        }

        return [
            'title' => $title,
            'description' => $description,
            'keywords' => $keywords,
            'headings' => $headings,
            'clean_text' => $cleanText,
        ];
    }
}

