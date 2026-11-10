<?php

namespace App\Services\WordPress;

use Illuminate\Validation\ValidationException;

class WordPressUrlValidator
{
    /**
     * Validate the given URL for safe outbound requests and resolve host IP.
     *
     * @return array{
     *     normalized_url: string,
     *     host: string,
     *     port: int,
     *     pinned_ip: ?string
     * }
     *
     * @throws ValidationException
     */
    public function validateAndResolve(string $url): array
    {
        $trimmed = trim($url);

        if (! filter_var($trimmed, FILTER_VALIDATE_URL)) {
            throw ValidationException::withMessages([
                'site_url' => 'The WordPress site URL is not a valid URL format.',
            ]);
        }

        $parts = parse_url($trimmed);

        if ($parts === false || ! isset($parts['scheme'], $parts['host'])) {
            throw ValidationException::withMessages([
                'site_url' => 'The WordPress site URL must contain a valid scheme and host.',
            ]);
        }

        $scheme = strtolower($parts['scheme']);
        $host = strtolower($parts['host']);

        // Prevent credential exposure in authority
        if (isset($parts['user']) || isset($parts['pass'])) {
            throw ValidationException::withMessages([
                'site_url' => 'URL authority credentials are not permitted in the WordPress URL.',
            ]);
        }

        $allowLocal = (bool) config('wordpress.allow_local_urls', false);

        // Require HTTPS unless explicitly permitted for local dev / testing
        if (! $allowLocal && $scheme !== 'https') {
            throw ValidationException::withMessages([
                'site_url' => 'WordPress site URLs must use secure HTTPS.',
            ]);
        }

        if (! in_array($scheme, ['http', 'https'], true)) {
            throw ValidationException::withMessages([
                'site_url' => 'Only HTTP and HTTPS protocols are supported.',
            ]);
        }

        $port = isset($parts['port']) ? (int) $parts['port'] : ($scheme === 'https' ? 443 : 80);

        if (! $allowLocal && ! in_array($port, [80, 443], true)) {
            throw ValidationException::withMessages([
                'site_url' => 'Non-standard port connections are disabled for security.',
            ]);
        }

        $pinnedIp = null;

        if (! $allowLocal) {
            $pinnedIp = $this->resolveAndAssertSafeHost($host);
        }

        $portSuffix = isset($parts['port']) ? ':'.$parts['port'] : '';
        $path = isset($parts['path']) ? rtrim($parts['path'], '/') : '';
        $normalizedUrl = "{$scheme}://{$host}{$portSuffix}{$path}";

        return [
            'normalized_url' => $normalizedUrl,
            'host' => $host,
            'port' => $port,
            'pinned_ip' => $pinnedIp,
        ];
    }

    /**
     * Backward-compatible helper returning normalized string.
     *
     * @throws ValidationException
     */
    public function validateAndNormalize(string $url): string
    {
        return $this->validateAndResolve($url)['normalized_url'];
    }

    /**
     * Resolve host and verify that all IP targets are public and safe.
     * Returns the verified IP for cURL pinning.
     *
     * @throws ValidationException
     */
    private function resolveAndAssertSafeHost(string $host): string
    {
        if (in_array($host, ['localhost', 'localhost.localdomain', 'ip6-localhost', 'ip6-loopback'], true)
            || str_ends_with($host, '.localhost')
            || str_ends_with($host, '.internal')
            || str_ends_with($host, '.local')
            || str_ends_with($host, '.test')) {
            throw ValidationException::withMessages([
                'site_url' => 'Connections to loopback or local domains are prohibited.',
            ]);
        }

        if (filter_var($host, FILTER_VALIDATE_IP)) {
            $this->assertSafeIp($host);

            return $host;
        }

        $ips = gethostbynamel($host);

        if ($ips === false || $ips === []) {
            throw ValidationException::withMessages([
                'site_url' => 'Could not resolve hostname for the provided WordPress URL. Failing closed for security.',
            ]);
        }

        foreach ($ips as $ip) {
            $this->assertSafeIp($ip);
        }

        return $ips[0];
    }

    /**
     * Assert an IP is public and safe.
     *
     * @throws ValidationException
     */
    private function assertSafeIp(string $ip): void
    {
        $flags = FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE;

        if (! filter_var($ip, FILTER_VALIDATE_IP, $flags)) {
            throw ValidationException::withMessages([
                'site_url' => 'Connections to private, loopback, or reserved IP addresses are strictly prohibited.',
            ]);
        }

        $lowered = strtolower($ip);
        if ($lowered === '::1' || str_starts_with($lowered, 'fc') || str_starts_with($lowered, 'fd') || str_starts_with($lowered, 'fe80')) {
            throw ValidationException::withMessages([
                'site_url' => 'Connections to private IPv6 addresses are strictly prohibited.',
            ]);
        }
    }
}
