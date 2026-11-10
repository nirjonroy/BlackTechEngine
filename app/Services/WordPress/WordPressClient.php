<?php

namespace App\Services\WordPress;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;
use Throwable;

class WordPressClient
{
    public function __construct(
        private readonly WordPressUrlValidator $urlValidator,
    ) {}

    /**
     * Verify WordPress site accessibility, REST API availability, and user publishing permissions.
     *
     * @return array{
     *     site_name: ?string,
     *     wordpress_version: ?string,
     *     user_id: int,
     *     user_name: string,
     *     can_publish: bool
     * }
     *
     * @throws ValidationException
     */
    public function verifyConnection(string $siteUrl, string $username, string $applicationPassword): array
    {
        $resolved = $this->urlValidator->validateAndResolve($siteUrl);
        $cleanPassword = str_replace(' ', '', $applicationPassword);

        // 1. Discovery probe: Verify WordPress REST API namespace
        $discoveryUrl = rtrim($resolved['normalized_url'], '/').'/wp-json/';
        $discoveryResponse = $this->executeRequest(function () use ($discoveryUrl, $resolved) {
            return $this->newClient($resolved)
                ->acceptJson()
                ->get($discoveryUrl);
        }, 'WordPress discovery probe failed');

        if (! $discoveryResponse->successful()) {
            throw ValidationException::withMessages([
                'site_url' => 'Could not detect WordPress REST API at the specified URL (HTTP '.$discoveryResponse->status().').',
            ]);
        }

        $discoveryData = $discoveryResponse->json();
        $namespaces = is_array($discoveryData) && isset($discoveryData['namespaces']) && is_array($discoveryData['namespaces'])
            ? $discoveryData['namespaces']
            : [];

        if (! in_array('wp/v2', $namespaces, true)) {
            throw ValidationException::withMessages([
                'site_url' => 'The target site does not expose the standard WordPress REST API namespace (wp/v2).',
            ]);
        }

        $siteName = is_array($discoveryData) && isset($discoveryData['name']) && is_string($discoveryData['name'])
            ? $discoveryData['name']
            : null;

        // 2. Authentication and capability probe
        $userMeUrl = rtrim($resolved['normalized_url'], '/').'/wp-json/wp/v2/users/me?context=edit';
        $userResponse = $this->executeRequest(function () use ($userMeUrl, $username, $cleanPassword, $resolved) {
            return $this->newClient($resolved)
                ->withBasicAuth($username, $cleanPassword)
                ->acceptJson()
                ->get($userMeUrl);
        }, 'WordPress authentication probe failed');

        if ($userResponse->status() === 401 || $userResponse->status() === 403) {
            throw ValidationException::withMessages([
                'application_password' => 'Invalid WordPress username or Application Password credentials.',
            ]);
        }

        if (! $userResponse->successful()) {
            throw ValidationException::withMessages([
                'site_url' => 'WordPress user verification returned unexpected HTTP '.$userResponse->status().'.',
            ]);
        }

        $userData = $userResponse->json();
        if (! is_array($userData) || ! isset($userData['id'])) {
            throw ValidationException::withMessages([
                'application_password' => 'WordPress returned an invalid user profile response structure.',
            ]);
        }

        // Check publishing capabilities or roles
        $capabilities = isset($userData['capabilities']) && is_array($userData['capabilities'])
            ? $userData['capabilities']
            : [];
        $roles = isset($userData['roles']) && is_array($userData['roles'])
            ? $userData['roles']
            : [];

        $canPublish = ! empty($capabilities['publish_posts'])
            || ! empty($capabilities['edit_posts'])
            || in_array('administrator', $roles, true)
            || in_array('editor', $roles, true)
            || in_array('author', $roles, true);

        if (! $canPublish) {
            throw ValidationException::withMessages([
                'username' => 'The connected WordPress user does not have permission to publish or edit posts.',
            ]);
        }

        return [
            'site_name' => $siteName,
            'wordpress_version' => null,
            'user_id' => (int) $userData['id'],
            'user_name' => (string) ($userData['name'] ?? $username),
            'can_publish' => true,
        ];
    }

    /**
     * Create or update a post on WordPress via REST API with DNS pinning and SSRF defense.
     *
     * @param  array<string, mixed>  $payload
     * @return array{id: int, link: string, status: string}
     *
     * @throws ValidationException
     */
    public function publishPost(string $siteUrl, string $username, string $applicationPassword, array $payload, ?int $wpPostId = null): array
    {
        $resolved = $this->urlValidator->validateAndResolve($siteUrl);
        $cleanPassword = str_replace(' ', '', $applicationPassword);

        $endpoint = $wpPostId !== null
            ? rtrim($resolved['normalized_url'], '/').'/wp-json/wp/v2/posts/'.$wpPostId
            : rtrim($resolved['normalized_url'], '/').'/wp-json/wp/v2/posts';

        $response = $this->executeRequest(function () use ($endpoint, $username, $cleanPassword, $payload, $resolved) {
            return $this->newClient($resolved)
                ->withBasicAuth($username, $cleanPassword)
                ->acceptJson()
                ->post($endpoint, $payload);
        }, 'WordPress post creation failed');

        if (! $response->successful()) {
            $errorBody = $response->json();
            $wpMessage = is_array($errorBody) && isset($errorBody['message']) && is_string($errorBody['message'])
                ? strip_tags($errorBody['message'])
                : 'WordPress API returned HTTP '.$response->status().'.';

            throw ValidationException::withMessages([
                'wordpress' => "Failed to publish article to WordPress: {$wpMessage}",
            ]);
        }

        $data = $response->json();

        if (! is_array($data) || ! isset($data['id'])) {
            throw ValidationException::withMessages([
                'wordpress' => 'WordPress returned an invalid response structure when creating post.',
            ]);
        }

        return [
            'id' => (int) $data['id'],
            'link' => (string) ($data['link'] ?? ''),
            'status' => (string) ($data['status'] ?? 'draft'),
        ];
    }

    /**
     * Build client with pinned cURL resolution to prevent DNS rebinding.
     *
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

        return Http::timeout(config('wordpress.request_timeout_seconds', 20))
            ->connectTimeout(config('wordpress.connect_timeout_seconds', 5))
            ->withUserAgent(config('wordpress.user_agent', 'BlackTechEngine/1.0'))
            ->withoutRedirecting()
            ->withOptions($options);
    }

    /**
     * Execute an HTTP callback and wrap connection / network exceptions safely.
     *
     * @throws ValidationException
     */
    private function executeRequest(callable $request, string $context): Response
    {
        try {
            return $request();
        } catch (ConnectionException $e) {
            throw ValidationException::withMessages([
                'site_url' => "{$context}: Connection timed out or server unreachable.",
            ]);
        } catch (ValidationException $e) {
            throw $e;
        } catch (Throwable $e) {
            throw ValidationException::withMessages([
                'site_url' => "{$context}: ".($e->getMessage() ?: 'Network communication error.'),
            ]);
        }
    }
}
