<?php

namespace App\Services;

use App\Models\SiteSetting;
use Illuminate\Support\Facades\Route;

class FrontendNavigation
{
    /** @var array<string, string> */
    public const DESTINATION_OPTIONS = [
        'home' => 'Home',
        'features' => 'Features',
        'about' => 'About GEO',
        'pricing' => 'Pricing',
        'blog.index' => 'Blog',
        'affiliate' => 'Affiliate program',
        'login' => 'Client sign in',
        'register' => 'Create account',
        'dashboard' => 'User dashboard',
    ];

    /** @var array<int, array{label: string, route: string, order: int, is_visible: bool}> */
    public const DEFAULT_HEADER_LINKS = [
        ['label' => 'Home', 'route' => 'home', 'order' => 10, 'is_visible' => true],
        ['label' => 'Features', 'route' => 'features', 'order' => 20, 'is_visible' => true],
        ['label' => 'About GEO', 'route' => 'about', 'order' => 30, 'is_visible' => true],
        ['label' => 'Pricing', 'route' => 'pricing', 'order' => 40, 'is_visible' => true],
        ['label' => 'Blog', 'route' => 'blog.index', 'order' => 50, 'is_visible' => true],
        ['label' => 'Affiliates', 'route' => 'affiliate', 'order' => 60, 'is_visible' => true],
    ];

    /** @var array<int, array{title: string, order: int, is_visible: bool, links: array<int, array{label: string, route: string, order: int, is_visible: bool}>}> */
    public const DEFAULT_FOOTER_COLUMNS = [
        [
            'title' => 'Product',
            'order' => 10,
            'is_visible' => true,
            'links' => [
                ['label' => 'All Features', 'route' => 'features', 'order' => 10, 'is_visible' => true],
                ['label' => 'Pricing & Plans', 'route' => 'pricing', 'order' => 20, 'is_visible' => true],
            ],
        ],
        [
            'title' => 'Resources',
            'order' => 20,
            'is_visible' => true,
            'links' => [
                ['label' => 'About GEO Architecture', 'route' => 'about', 'order' => 10, 'is_visible' => true],
                ['label' => 'Research Blog', 'route' => 'blog.index', 'order' => 20, 'is_visible' => true],
                ['label' => 'Affiliate Program Status', 'route' => 'affiliate', 'order' => 30, 'is_visible' => true],
            ],
        ],
        [
            'title' => 'Portals',
            'order' => 30,
            'is_visible' => true,
            'links' => [
                ['label' => 'User Dashboard Panel', 'route' => 'dashboard', 'order' => 10, 'is_visible' => true],
                ['label' => 'Start Free Trial', 'route' => 'register', 'order' => 20, 'is_visible' => true],
                ['label' => 'Client Sign In', 'route' => 'login', 'order' => 30, 'is_visible' => true],
            ],
        ],
    ];

    public const DEFAULT_DESCRIPTION = 'The next-generation autonomous SEO and GEO platform, built for high-growth tech brands, agencies, and creators.';

    public const DEFAULT_COPYRIGHT = 'All rights reserved. Built with precision for autonomous SEO.';

    /**
     * @return array{
     *     headerLinks: array<int, array{label: string, route: string, url: string, is_active: bool}>,
     *     footerColumns: array<int, array{title: string, links: array<int, array{label: string, route: string, url: string}>}>,
     *     footerDescription: string,
     *     footerCopyright: string,
     *     socialLinks: array<int, array{label: string, icon: string, url: string}>
     * }
     */
    public function resolve(?SiteSetting $setting, ?string $currentRouteName): array
    {
        $headerLinks = collect($this->links($setting?->header_navigation, self::DEFAULT_HEADER_LINKS))
            ->filter(fn (array $link): bool => $link['is_visible'])
            ->map(fn (array $link): array => [
                'label' => $link['label'],
                'route' => $link['route'],
                'url' => route($link['route']),
                'is_active' => $this->routeIsActive($currentRouteName, $link['route']),
            ])
            ->values()
            ->all();

        $footerColumns = collect($this->columns($setting?->footer_columns, self::DEFAULT_FOOTER_COLUMNS))
            ->filter(fn (array $column): bool => $column['is_visible'])
            ->map(fn (array $column): array => [
                'title' => $column['title'],
                'links' => collect($column['links'])
                    ->filter(fn (array $link): bool => $link['is_visible'])
                    ->map(fn (array $link): array => [
                        'label' => $link['label'],
                        'route' => $link['route'],
                        'url' => route($link['route']),
                    ])
                    ->values()
                    ->all(),
            ])
            ->values()
            ->all();

        return [
            'headerLinks' => $headerLinks,
            'footerColumns' => $footerColumns,
            'footerDescription' => filled($setting?->footer_description) ? $setting->footer_description : self::DEFAULT_DESCRIPTION,
            'footerCopyright' => filled($setting?->footer_copyright) ? $setting->footer_copyright : self::DEFAULT_COPYRIGHT,
            'socialLinks' => $this->socialLinks($setting),
        ];
    }

    /**
     * @return array{
     *     header_navigation: array<int, array{label: string, route: string, order: int, is_visible: bool}>,
     *     footer_columns: array<int, array{title: string, order: int, is_visible: bool, links: array<int, array{label: string, route: string, order: int, is_visible: bool}>}>
     * }
     */
    public function formConfiguration(?SiteSetting $setting): array
    {
        return [
            'header_navigation' => $this->links($setting?->header_navigation, self::DEFAULT_HEADER_LINKS),
            'footer_columns' => $this->columns($setting?->footer_columns, self::DEFAULT_FOOTER_COLUMNS),
        ];
    }

    /**
     * @param  array<int, mixed>|null  $links
     * @param  array<int, array{label: string, route: string, order: int, is_visible: bool}>  $defaults
     * @return array<int, array{label: string, route: string, order: int, is_visible: bool}>
     */
    public function links(?array $links, array $defaults): array
    {
        if ($links === null) {
            return $defaults;
        }

        return collect($links)
            ->map(function (mixed $link, int $index): ?array {
                if (! is_array($link)) {
                    return null;
                }

                $label = $link['label'] ?? null;
                $routeName = $link['route'] ?? null;

                if (! is_string($label) || ! filled($label) || ! is_string($routeName) || ! $this->isAllowedRoute($routeName)) {
                    return null;
                }

                return [
                    'label' => $label,
                    'route' => $routeName,
                    'order' => is_numeric($link['order'] ?? null) ? (int) $link['order'] : $index,
                    'is_visible' => filter_var($link['is_visible'] ?? false, FILTER_VALIDATE_BOOL),
                ];
            })
            ->filter()
            ->sortBy('order')
            ->values()
            ->all();
    }

    /**
     * @param  array<int, mixed>|null  $columns
     * @param  array<int, array{title: string, order: int, is_visible: bool, links: array<int, array{label: string, route: string, order: int, is_visible: bool}>}>  $defaults
     * @return array<int, array{title: string, order: int, is_visible: bool, links: array<int, array{label: string, route: string, order: int, is_visible: bool}>}>
     */
    public function columns(?array $columns, array $defaults): array
    {
        if ($columns === null) {
            return $defaults;
        }

        return collect($columns)
            ->map(function (mixed $column, int $index): ?array {
                if (! is_array($column) || ! is_string($column['title'] ?? null) || ! filled($column['title'])) {
                    return null;
                }

                return [
                    'title' => $column['title'],
                    'order' => is_numeric($column['order'] ?? null) ? (int) $column['order'] : $index,
                    'is_visible' => filter_var($column['is_visible'] ?? false, FILTER_VALIDATE_BOOL),
                    'links' => $this->links(is_array($column['links'] ?? null) ? $column['links'] : [], []),
                ];
            })
            ->filter()
            ->sortBy('order')
            ->values()
            ->all();
    }

    private function isAllowedRoute(string $routeName): bool
    {
        return array_key_exists($routeName, self::DESTINATION_OPTIONS) && Route::has($routeName);
    }

    private function routeIsActive(?string $currentRouteName, string $routeName): bool
    {
        if ($routeName === 'blog.index') {
            return $currentRouteName === 'blog.index' || $currentRouteName === 'blog.show';
        }

        return $currentRouteName === $routeName;
    }

    /**
     * @return array<int, array{label: string, icon: string, url: string}>
     */
    private function socialLinks(?SiteSetting $setting): array
    {
        $links = [
            ['label' => 'Facebook', 'icon' => 'facebook', 'url' => $setting?->facebook_url],
            ['label' => 'X', 'icon' => 'twitter', 'url' => $setting?->x_url],
            ['label' => 'LinkedIn', 'icon' => 'linkedin', 'url' => $setting?->linkedin_url],
            ['label' => 'GitHub', 'icon' => 'github', 'url' => $setting?->github_url],
            ['label' => 'YouTube', 'icon' => 'youtube', 'url' => $setting?->youtube_url],
        ];

        return collect($links)
            ->filter(fn (array $link): bool => $this->isSafeHttpUrl($link['url']))
            ->map(fn (array $link): array => [
                'label' => $link['label'],
                'icon' => $link['icon'],
                'url' => $link['url'],
            ])
            ->values()
            ->all();
    }

    private function isSafeHttpUrl(mixed $url): bool
    {
        return is_string($url)
            && filter_var($url, FILTER_VALIDATE_URL) !== false
            && in_array(strtolower((string) parse_url($url, PHP_URL_SCHEME)), ['http', 'https'], true);
    }
}
