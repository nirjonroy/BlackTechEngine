<?php

namespace App\Services\InternalLinking;

use App\Models\Brand;
use App\Models\BrandIndexedPage;
use App\Models\BrandIndexingAudit;
use App\Models\User;
use App\Services\WordPress\WordPressUrlValidator;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use SimpleXMLElement;
use Throwable;

class WordPressSiteIndexer
{
    public function __construct(
        private readonly WordPressUrlValidator $urlValidator,
    ) {}

    /**
     * Crawl and index verified WordPress site posts, pages, or sitemap.
     *
     * @throws ValidationException
     */
    public function indexSite(Brand $brand, User $user, string $source = 'wordpress_api'): BrandIndexingAudit
    {
        $startTime = microtime(true);
        $connection = $brand->wordPressConnection;

        if ($connection === null || ! $connection->isConnected()) {
            throw ValidationException::withMessages([
                'wordpress' => 'WordPress site is not connected or requires re-verification before indexing.',
            ]);
        }

        $resolved = $this->urlValidator->validateAndResolve($connection->site_url);
        $allowedHost = $resolved['host'];
        $cleanPassword = str_replace(' ', '', (string) $connection->application_password);

        $discovered = [];
        $errorMessage = null;
        $status = 'completed';

        try {
            if ($source === 'sitemap') {
                $discovered = $this->crawlSitemap($resolved, $connection->username, $cleanPassword);
            } else {
                $discovered = $this->crawlRestApi($resolved, $connection->username, $cleanPassword);
            }
        } catch (Throwable $e) {
            $status = 'failed';
            $errorMessage = $e->getMessage();
        }

        $indexedCount = 0;

        if ($discovered !== [] && $status === 'completed') {
            $indexedCount = $this->saveDiscoveredPages($brand, $discovered, $allowedHost, $source);
        }

        $durationMs = (int) round((microtime(true) - $startTime) * 1000);

        return BrandIndexingAudit::query()->create([
            'workspace_id' => $brand->workspace_id,
            'brand_id' => $brand->getKey(),
            'created_by_user_id' => $user->getKey(),
            'status' => $status,
            'source' => $source,
            'pages_discovered' => count($discovered),
            'pages_indexed' => $indexedCount,
            'error_message' => $errorMessage,
            'duration_ms' => $durationMs,
        ]);
    }

    /**
     * Crawl posts and pages via standard WordPress REST API.
     *
     * @param  array{normalized_url: string, host: string, port: int, pinned_ip: ?string}  $resolved
     * @return list<array{url: string, title: string, slug: ?string, snippet: ?string, post_id: ?int, type: string}>
     *
     * @throws ValidationException
     */
    private function crawlRestApi(array $resolved, string $username, string $password): array
    {
        $results = [];
        $maxPages = (int) config('wordpress.indexing.max_pages_per_crawl', 200);

        foreach (['posts' => 'post', 'pages' => 'page'] as $endpoint => $pageType) {
            if (count($results) >= $maxPages) {
                break;
            }

            $url = rtrim($resolved['normalized_url'], '/')."/wp-json/wp/v2/{$endpoint}?per_page=100&_fields=id,link,title,slug,excerpt";

            $response = $this->executeSafeRequest(function () use ($url, $username, $password, $resolved) {
                return $this->newClient($resolved)
                    ->withBasicAuth($username, $password)
                    ->acceptJson()
                    ->get($url);
            });

            if (! $response->successful()) {
                continue;
            }

            $data = $response->json();
            if (! is_array($data)) {
                continue;
            }

            foreach ($data as $item) {
                if (count($results) >= $maxPages) {
                    break;
                }

                if (! is_array($item) || empty($item['link'])) {
                    continue;
                }

                $title = '';
                if (isset($item['title'])) {
                    $rawTitle = is_array($item['title']) ? ($item['title']['rendered'] ?? '') : $item['title'];
                    $title = html_entity_decode(strip_tags((string) $rawTitle), ENT_QUOTES | ENT_HTML5, 'UTF-8');
                }

                $snippet = '';
                if (isset($item['excerpt'])) {
                    $rawSnippet = is_array($item['excerpt']) ? ($item['excerpt']['rendered'] ?? '') : $item['excerpt'];
                    $snippet = html_entity_decode(strip_tags((string) $rawSnippet), ENT_QUOTES | ENT_HTML5, 'UTF-8');
                }

                $results[] = [
                    'url' => (string) $item['link'],
                    'title' => trim($title) ?: 'Untitled',
                    'slug' => ! empty($item['slug']) ? (string) $item['slug'] : null,
                    'snippet' => trim($snippet) ?: null,
                    'post_id' => ! empty($item['id']) ? (int) $item['id'] : null,
                    'type' => $pageType,
                ];
            }
        }

        return $results;
    }

    /**
     * Crawl and parse WordPress XML sitemap safely without entity loading.
     *
     * @param  array{normalized_url: string, host: string, port: int, pinned_ip: ?string}  $resolved
     * @return list<array{url: string, title: string, slug: ?string, snippet: ?string, post_id: ?int, type: string}>
     *
     * @throws ValidationException
     */
    private function crawlSitemap(array $resolved, string $username, string $password): array
    {
        $maxPages = (int) config('wordpress.indexing.max_pages_per_crawl', 200);
        $maxBytes = (int) config('wordpress.indexing.max_response_bytes', 2097152); // 2MB

        $candidates = [
            rtrim($resolved['normalized_url'], '/').'/wp-sitemap.xml',
            rtrim($resolved['normalized_url'], '/').'/sitemap.xml',
            rtrim($resolved['normalized_url'], '/').'/sitemap_index.xml',
        ];

        $body = null;
        foreach ($candidates as $candidateUrl) {
            $response = $this->executeSafeRequest(function () use ($candidateUrl, $resolved) {
                return $this->newClient($resolved)->get($candidateUrl);
            });

            if ($response->successful() && strlen($response->body()) <= $maxBytes) {
                $body = $response->body();
                break;
            }
        }

        if ($body === null) {
            return [];
        }

        // Defend against XML external entity injection (XXE) and billion laughs attacks
        $previousEntityLoader = libxml_disable_entity_loader(true);
        $xml = @simplexml_load_string($body, SimpleXMLElement::class, LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING);
        libxml_disable_entity_loader($previousEntityLoader);

        if ($xml === false) {
            return [];
        }

        $discovered = [];

        // Check <url><loc> elements
        if (isset($xml->url)) {
            foreach ($xml->url as $urlNode) {
                if (count($discovered) >= $maxPages) {
                    break;
                }
                $loc = (string) $urlNode->loc;
                if ($loc !== '') {
                    $slug = basename(parse_url($loc, PHP_URL_PATH) ?? '');
                    $title = Str::title(str_replace(['-', '_'], ' ', $slug));
                    $discovered[] = [
                        'url' => $loc,
                        'title' => $title ?: $loc,
                        'slug' => $slug ?: null,
                        'snippet' => null,
                        'post_id' => null,
                        'type' => 'sitemap_url',
                    ];
                }
            }
        }

        // Check <sitemap><loc> elements (sitemap index)
        if (empty($discovered) && isset($xml->sitemap)) {
            foreach ($xml->sitemap as $sitemapNode) {
                if (count($discovered) >= $maxPages) {
                    break;
                }
                $subUrl = (string) $sitemapNode->loc;
                // Parse first sub-sitemap if bounded
                if ($subUrl !== '') {
                    $subResponse = $this->executeSafeRequest(function () use ($subUrl, $resolved) {
                        return $this->newClient($resolved)->get($subUrl);
                    });
                    if ($subResponse->successful() && strlen($subResponse->body()) <= $maxBytes) {
                        $subXml = @simplexml_load_string($subResponse->body(), SimpleXMLElement::class, LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING);
                        if ($subXml && isset($subXml->url)) {
                            foreach ($subXml->url as $subUrlNode) {
                                if (count($discovered) >= $maxPages) {
                                    break;
                                }
                                $loc = (string) $subUrlNode->loc;
                                if ($loc !== '') {
                                    $slug = basename(parse_url($loc, PHP_URL_PATH) ?? '');
                                    $title = Str::title(str_replace(['-', '_'], ' ', $slug));
                                    $discovered[] = [
                                        'url' => $loc,
                                        'title' => $title ?: $loc,
                                        'slug' => $slug ?: null,
                                        'snippet' => null,
                                        'post_id' => null,
                                        'type' => 'sitemap_url',
                                    ];
                                }
                            }
                        }
                    }
                }
            }
        }

        return $discovered;
    }

    /**
     * Store discovered pages with domain restriction check.
     *
     * @param  list<array{url: string, title: string, slug: ?string, snippet: ?string, post_id: ?int, type: string}>  $discovered
     */
    private function saveDiscoveredPages(Brand $brand, array $discovered, string $allowedHost, string $source): int
    {
        $indexedCount = 0;

        DB::transaction(function () use ($brand, $discovered, $allowedHost, $source, &$indexedCount) {
            foreach ($discovered as $item) {
                $url = trim($item['url']);
                $parsed = parse_url($url);

                if (! isset($parsed['host'])) {
                    continue;
                }

                // Strict domain check: only accept URLs from verified WordPress host
                if (strtolower($parsed['host']) !== strtolower($allowedHost)) {
                    continue;
                }

                $urlHash = hash('sha256', $url);

                BrandIndexedPage::query()->updateOrCreate(
                    [
                        'brand_id' => $brand->getKey(),
                        'url_hash' => $urlHash,
                    ],
                    [
                        'workspace_id' => $brand->workspace_id,
                        'wordpress_post_id' => $item['post_id'] ?? null,
                        'url' => $url,
                        'title' => Str::limit($item['title'], 500, ''),
                        'slug' => ! empty($item['slug']) ? Str::limit($item['slug'], 255, '') : null,
                        'page_type' => $item['type'] ?? 'post',
                        'snippet' => ! empty($item['snippet']) ? Str::limit($item['snippet'], 1000, '') : null,
                        'source' => $source,
                        'last_indexed_at' => now(),
                    ]
                );

                $indexedCount++;
            }
        });

        return $indexedCount;
    }

    /**
     * @param  array{normalized_url: string, host: string, port: int, pinned_ip: ?string}  $resolved
     */
    private function newClient(array $resolved): PendingRequest
    {
        $options = [];

        if (! empty($resolved['pinned_ip'])) {
            $options['curl'] = [
                CURLOPT_RESOLVE => [
                    "{$resolved['host']}:{$resolved['port']}:{$resolved['pinned_ip']}",
                ],
            ];
        }

        $timeout = (int) config('wordpress.indexing.crawl_timeout_seconds', 20);

        return Http::timeout($timeout)
            ->connectTimeout(config('wordpress.connect_timeout_seconds', 5))
            ->withUserAgent(config('wordpress.user_agent', 'BlackTechEngine/1.0'))
            ->withoutRedirecting()
            ->withOptions($options);
    }

    /**
     * @throws ValidationException
     */
    private function executeSafeRequest(callable $request): Response
    {
        try {
            return $request();
        } catch (Throwable $e) {
            throw ValidationException::withMessages([
                'crawl' => "Crawl request failed: {$e->getMessage()}",
            ]);
        }
    }
}
