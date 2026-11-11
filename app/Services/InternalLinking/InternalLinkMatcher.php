<?php

namespace App\Services\InternalLinking;

use App\Models\AiContentDraft;
use App\Models\BrandIndexedPage;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class InternalLinkMatcher
{
    /**
     * Common stopwords to avoid suggesting as isolated anchor text.
     *
     * @var list<string>
     */
    private const STOPWORDS = [
        'the', 'and', 'for', 'that', 'this', 'with', 'from', 'your', 'have',
        'more', 'about', 'will', 'what', 'when', 'which', 'there', 'their',
        'into', 'some', 'these', 'them', 'then', 'other', 'also', 'here',
    ];

    /**
     * Analyze draft content and match relevant internal links from indexed pages.
     *
     * @param  Collection<int, BrandIndexedPage>|null  $indexedPages
     * @return list<array{
     *     id: string,
     *     target_page_id: int,
     *     url: string,
     *     title: string,
     *     anchor: string,
     *     relevance_score: int,
     *     context: string
     * }>
     */
    public function suggestLinks(AiContentDraft $draft, ?Collection $indexedPages = null): array
    {
        $content = $draft->content;
        if (blank($content)) {
            return [];
        }

        $pages = $indexedPages ?? $draft->brand->indexedPages()->get();
        if ($pages->isEmpty()) {
            return [];
        }

        // 1. Identify already linked URLs and anchors in draft
        $existingUrls = $this->extractExistingUrls($content);
        $existingAnchors = $this->extractExistingAnchors($content);

        // 2. Identify self-link criteria for the draft
        $selfSlugs = array_filter([
            $draft->suggested_slug,
            Str::slug($draft->seo_title ?: ''),
            Str::slug($draft->topic ?: ''),
        ]);

        $suggestions = [];
        $usedAnchors = $existingAnchors;
        $usedUrls = $existingUrls;

        foreach ($pages as $page) {
            // Exclude already linked URLs
            if (in_array(strtolower($page->url), $usedUrls, true)) {
                continue;
            }

            // Exclude self-links by slug or post ID
            if (! empty($page->slug) && in_array(strtolower($page->slug), $selfSlugs, true)) {
                continue;
            }

            // Exclude broken / invalid URLs
            if (! filter_var($page->url, FILTER_VALIDATE_URL)) {
                continue;
            }

            // Derive candidate anchor phrases from page title, slug, and keywords
            $candidates = $this->extractCandidateAnchors($page);

            foreach ($candidates as $candidate) {
                $lowerCandidate = strtolower($candidate);

                // Skip if anchor already used or is a stopword
                if (in_array($lowerCandidate, $usedAnchors, true) || in_array($lowerCandidate, self::STOPWORDS, true)) {
                    continue;
                }

                if (mb_strlen($candidate) < 3) {
                    continue;
                }

                // Check if candidate phrase occurs naturally in the draft body (outside headers/code)
                $occurrence = $this->findEligibleOccurrence($content, $candidate);
                if ($occurrence === null) {
                    continue;
                }

                // Calculate relevance score
                $score = $this->calculateScore($candidate, $page, $draft);

                $suggestions[] = [
                    'id' => Str::random(8),
                    'target_page_id' => $page->getKey(),
                    'url' => $page->url,
                    'title' => $page->title,
                    'anchor' => $occurrence['matched_text'],
                    'relevance_score' => $score,
                    'context' => $occurrence['context'],
                ];

                $usedAnchors[] = $lowerCandidate;
                $usedUrls[] = strtolower($page->url);
                break; // One link per target page
            }
        }

        // Sort by relevance score descending
        usort($suggestions, fn ($a, $b) => $b['relevance_score'] <=> $a['relevance_score']);

        return array_slice($suggestions, 0, 10);
    }

    /**
     * Safely insert accepted internal links into markdown content.
     *
     * @param  list<array{anchor: string, url: string}>  $linksToApply
     */
    public function applyLinks(string $content, array $linksToApply): string
    {
        $updated = $content;

        foreach ($linksToApply as $link) {
            $anchor = trim($link['anchor'] ?? '');
            $url = trim($link['url'] ?? '');

            if ($anchor === '' || ! filter_var($url, FILTER_VALIDATE_URL)) {
                continue;
            }

            $pattern = '/(?<!\[)\b('.preg_quote($anchor, '/').')\b(?![^\[]*\])(?![^\(]*\))/i';

            // Only replace the first occurrence that is outside headers and code blocks
            $lines = explode("\n", $updated);
            $inCodeBlock = false;
            $replaced = false;

            foreach ($lines as $i => $line) {
                if (str_starts_with(trim($line), '```')) {
                    $inCodeBlock = ! $inCodeBlock;

                    continue;
                }

                if ($inCodeBlock || str_starts_with(trim($line), '#') || $replaced) {
                    continue;
                }

                if (preg_match($pattern, $line, $matches, PREG_OFFSET_CAPTURE)) {
                    $matchText = $matches[1][0];
                    $matchPos = $matches[1][1];

                    $markdownLink = "[{$matchText}]({$url})";
                    $lines[$i] = substr_replace($line, $markdownLink, $matchPos, strlen($matchText));
                    $replaced = true;
                    break;
                }
            }

            $updated = implode("\n", $lines);
        }

        return $updated;
    }

    /**
     * @return list<string>
     */
    private function extractExistingUrls(string $content): array
    {
        $urls = [];

        // Markdown links: [text](url)
        if (preg_match_all('/\[[^\]]+\]\((https?:\/\/[^\s\)]+)\)/i', $content, $matches)) {
            foreach ($matches[1] as $url) {
                $urls[] = strtolower(trim($url));
            }
        }

        // HTML links: <a href="url">
        if (preg_match_all('/<a\s[^>]*href=["\'](https?:\/\/[^"\']+)["\']/i', $content, $matches)) {
            foreach ($matches[1] as $url) {
                $urls[] = strtolower(trim($url));
            }
        }

        return array_unique($urls);
    }

    /**
     * @return list<string>
     */
    private function extractExistingAnchors(string $content): array
    {
        $anchors = [];

        if (preg_match_all('/\[([^\]]+)\]\([^\)]+\)/', $content, $matches)) {
            foreach ($matches[1] as $text) {
                $anchors[] = strtolower(trim($text));
            }
        }

        return array_unique($anchors);
    }

    /**
     * @return list<string>
     */
    private function extractCandidateAnchors(BrandIndexedPage $page): array
    {
        $candidates = [];

        // 1. Full title (if concise)
        $cleanTitle = trim(preg_replace('/[-|–].*$/', '', $page->title) ?? '');
        if (mb_strlen($cleanTitle) >= 3 && mb_strlen($cleanTitle) <= 60) {
            $candidates[] = $cleanTitle;
        }

        // 2. Slug words
        if (! empty($page->slug)) {
            $slugPhrase = trim(str_replace(['-', '_'], ' ', $page->slug));
            if (mb_strlen($slugPhrase) >= 3 && mb_strlen($slugPhrase) <= 50) {
                $candidates[] = $slugPhrase;
            }
        }

        // 3. Keywords if present
        if (is_array($page->keywords)) {
            foreach ($page->keywords as $kw) {
                if (is_string($kw) && mb_strlen($kw) >= 3) {
                    $candidates[] = trim($kw);
                }
            }
        }

        return array_values(array_unique($candidates));
    }

    /**
     * @return array{matched_text: string, context: string}|null
     */
    private function findEligibleOccurrence(string $content, string $phrase): ?array
    {
        $lines = explode("\n", $content);
        $inCodeBlock = false;
        $pattern = '/(?<!\[)\b('.preg_quote($phrase, '/').')\b(?![^\[]*\])/i';

        foreach ($lines as $line) {
            if (str_starts_with(trim($line), '```')) {
                $inCodeBlock = ! $inCodeBlock;

                continue;
            }

            // Exclude code blocks and headers
            if ($inCodeBlock || str_starts_with(trim($line), '#')) {
                continue;
            }

            if (preg_match($pattern, $line, $matches, PREG_OFFSET_CAPTURE)) {
                $matchText = $matches[1][0];
                $offset = $matches[1][1];

                $start = max(0, $offset - 30);
                $length = strlen($matchText) + 60;
                $context = '...'.trim(substr($line, $start, $length)).'...';

                return [
                    'matched_text' => $matchText,
                    'context' => $context,
                ];
            }
        }

        return null;
    }

    private function calculateScore(string $phrase, BrandIndexedPage $page, AiContentDraft $draft): int
    {
        $score = 50;

        if (strcasecmp($phrase, $page->title) === 0) {
            $score += 30;
        }

        if (! empty($draft->primary_keyword) && stripos($draft->primary_keyword, $phrase) !== false) {
            $score += 20;
        }

        if (! empty($page->snippet) && stripos($page->snippet, $draft->topic) !== false) {
            $score += 15;
        }

        return min(100, $score);
    }
}
