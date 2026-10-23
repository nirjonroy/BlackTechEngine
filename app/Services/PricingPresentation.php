<?php

namespace App\Services;

class PricingPresentation
{
    /** @var array<string, string> */
    public const CURRENCY_OPTIONS = [
        'USD' => 'USD — US Dollar',
        'EUR' => 'EUR — Euro',
        'GBP' => 'GBP — British Pound',
        'BDT' => 'BDT — Bangladeshi Taka',
        'CAD' => 'CAD — Canadian Dollar',
        'AUD' => 'AUD — Australian Dollar',
        'INR' => 'INR — Indian Rupee',
        'JPY' => 'JPY — Japanese Yen',
        'SGD' => 'SGD — Singapore Dollar',
        'AED' => 'AED — UAE Dirham',
    ];

    /** @var array<string, string> */
    private const CURRENCY_PREFIXES = [
        'USD' => '$',
        'EUR' => '€',
        'GBP' => '£',
        'BDT' => '৳',
        'CAD' => 'CA$',
        'AUD' => 'A$',
        'INR' => '₹',
        'JPY' => '¥',
        'SGD' => 'S$',
        'AED' => 'AED ',
    ];

    /** @var array<string, string> */
    public const DESTINATION_OPTIONS = [
        'register' => 'Create account',
        'login' => 'Client sign in',
        'pricing' => 'Pricing page',
        'home' => 'Homepage',
        'about' => 'About page',
        'features' => 'Features page',
        'affiliate' => 'Affiliate program',
        'blog.index' => 'Blog',
    ];

    /** @var array<string, string> */
    public const ICON_OPTIONS = [
        'tag' => 'Tag',
        'badge-dollar-sign' => 'Currency badge',
        'circle-dollar-sign' => 'Currency circle',
        'wallet-cards' => 'Wallet',
        'receipt' => 'Receipt',
        'chart-no-axes-combined' => 'Trend chart',
    ];

    public function currencyCode(?string $currencyCode): string
    {
        $currencyCode = strtoupper((string) $currencyCode);

        return array_key_exists($currencyCode, self::CURRENCY_OPTIONS) ? $currencyCode : 'USD';
    }

    public function format(int|float|string|null $amount, string $currencyCode): string
    {
        $currencyCode = $this->currencyCode($currencyCode);

        return self::CURRENCY_PREFIXES[$currencyCode].number_format((float) $amount, 2, '.', ',');
    }

    public function destinationUrl(?string $destination, ?string $fallback = null): ?string
    {
        $routeName = array_key_exists((string) $destination, self::DESTINATION_OPTIONS)
            ? $destination
            : $fallback;

        return $routeName !== null && array_key_exists($routeName, self::DESTINATION_OPTIONS)
            ? route($routeName)
            : null;
    }

    public function safeIcon(?string $icon): string
    {
        return is_string($icon) && array_key_exists($icon, self::ICON_OPTIONS)
            ? $icon
            : 'tag';
    }
}
