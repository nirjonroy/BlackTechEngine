<?php

namespace App\Services\Ai\Gemini;

use App\Enums\AiProviderErrorCategory;
use App\Services\Ai\AiProviderCredentialResolver;
use App\Services\Ai\Exceptions\AiProviderException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use JsonException;

class GeminiHttpTransport
{
    public const API_VERSION = 'v1beta';

    public const BASE_URL = 'https://generativelanguage.googleapis.com';

    public const MODELS_PATH = '/'.self::API_VERSION.'/models';

    public const MAX_PAGE_SIZE = 100;

    private const CONNECT_TIMEOUT_SECONDS = 5;

    private const MAX_RESPONSE_BYTES = 2_000_000;

    private const REQUEST_TIMEOUT_SECONDS = 30;

    private const MAX_OUTPUT_TOKENS = 8192;

    private const MAX_PROMPT_BYTES = 50000;

    public function __construct(
        private readonly AiProviderCredentialResolver $credentialResolver,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function listModels(int $pageSize, ?string $pageToken = null): array
    {
        if ($pageSize < 1 || $pageSize > self::MAX_PAGE_SIZE) {
            throw new AiProviderException(
                AiProviderErrorCategory::InvalidRequest,
                'The Gemini model page size is outside the permitted range.',
            );
        }

        if ($pageToken !== null && ($pageToken === '' || mb_strlen($pageToken) > 4096)) {
            throw new AiProviderException(
                AiProviderErrorCategory::InvalidRequest,
                'The Gemini model page token is invalid.',
            );
        }

        $query = ['pageSize' => $pageSize];

        if ($pageToken !== null) {
            $query['pageToken'] = $pageToken;
        }

        try {
            $response = Http::baseUrl(self::BASE_URL)
                ->acceptJson()
                ->withHeaders([
                    'x-goog-api-key' => $this->credentialResolver->geminiApiKey(),
                ])
                ->connectTimeout(self::CONNECT_TIMEOUT_SECONDS)
                ->timeout(self::REQUEST_TIMEOUT_SECONDS)
                ->withoutRedirecting()
                ->get(self::MODELS_PATH, $query);
        } catch (ConnectionException $exception) {
            $category = preg_match('/cURL error 28|timed? out|timeout/i', $exception->getMessage()) === 1
                ? AiProviderErrorCategory::Timeout
                : AiProviderErrorCategory::NetworkFailure;

            throw new AiProviderException($category, $this->safeMessage($category));
        }

        if (! $response->successful()) {
            $this->throwResponseException($response);
        }

        $body = $response->body();

        if (strlen($body) > self::MAX_RESPONSE_BYTES) {
            throw new AiProviderException(
                AiProviderErrorCategory::MalformedResponse,
                'The Gemini response exceeded the permitted in-memory size.',
            );
        }

        try {
            $payload = json_decode($body, true, 64, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            throw new AiProviderException(
                AiProviderErrorCategory::MalformedResponse,
                'Gemini returned malformed JSON.',
            );
        }

        if (! is_array($payload) || array_is_list($payload)) {
            throw new AiProviderException(
                AiProviderErrorCategory::MalformedResponse,
                'Gemini returned an unexpected response shape.',
            );
        }

        return $payload;
    }

    /** @return array<string, mixed> */
    public function countTokens(string $modelId, string $prompt, int $maximumOutputTokens): int
    {
        $this->validateModelAndPrompt($modelId, $prompt);
        $this->validateOutputTokenLimit($maximumOutputTokens);

        try {
            $response = Http::baseUrl(self::BASE_URL)
                ->acceptJson()
                ->asJson()
                ->withHeaders(['x-goog-api-key' => $this->credentialResolver->geminiApiKey()])
                ->connectTimeout(self::CONNECT_TIMEOUT_SECONDS)
                ->timeout(self::REQUEST_TIMEOUT_SECONDS)
                ->withoutRedirecting()
                ->post('/'.self::API_VERSION.'/models/'.$modelId.':countTokens', [
                    'generateContentRequest' => [
                        'model' => 'models/'.$modelId,
                        ...$this->generationRequestPayload($prompt, $maximumOutputTokens),
                    ],
                ]);
        } catch (ConnectionException $exception) {
            $this->throwConnectionException($exception);
        }

        if (! $response->successful()) {
            $this->throwResponseException($response);
        }

        $payload = $this->decodedPayload($response->body());
        $totalTokens = $payload['totalTokens'] ?? null;

        if (! is_int($totalTokens) || $totalTokens < 0) {
            throw new AiProviderException(
                AiProviderErrorCategory::MalformedResponse,
                'Gemini returned an invalid token count response.',
            );
        }

        return $totalTokens;
    }

    /** @return array<string, mixed> */
    public function generateContent(string $modelId, string $prompt, int $maximumOutputTokens): array
    {
        $this->validateModelAndPrompt($modelId, $prompt);
        $this->validateOutputTokenLimit($maximumOutputTokens);

        try {
            $response = Http::baseUrl(self::BASE_URL)
                ->acceptJson()
                ->asJson()
                ->withHeaders(['x-goog-api-key' => $this->credentialResolver->geminiApiKey()])
                ->connectTimeout(self::CONNECT_TIMEOUT_SECONDS)
                ->timeout(self::REQUEST_TIMEOUT_SECONDS)
                ->withoutRedirecting()
                ->post(
                    '/'.self::API_VERSION.'/models/'.$modelId.':generateContent',
                    $this->generationRequestPayload($prompt, $maximumOutputTokens),
                );
        } catch (ConnectionException $exception) {
            $this->throwConnectionException($exception);
        }

        if (! $response->successful()) {
            $this->throwResponseException($response);
        }

        return $this->decodedPayload($response->body());
    }

    /** @return array<string, mixed> */
    private function contentResponseSchema(): array
    {
        return [
            'type' => 'object',
            'required' => ['seo_title', 'meta_description', 'suggested_slug', 'outline', 'content', 'faq_entries', 'internal_link_opportunities', 'external_research_topics', 'verification_notes'],
            'properties' => [
                'seo_title' => ['type' => 'string'],
                'meta_description' => ['type' => 'string'],
                'suggested_slug' => ['type' => 'string'],
                'outline' => ['type' => 'array', 'items' => ['type' => 'string']],
                'content' => ['type' => 'string'],
                'faq_entries' => ['type' => 'array', 'items' => [
                    'type' => 'object',
                    'required' => ['question', 'answer'],
                    'properties' => ['question' => ['type' => 'string'], 'answer' => ['type' => 'string']],
                ]],
                'internal_link_opportunities' => ['type' => 'array', 'items' => ['type' => 'string']],
                'external_research_topics' => ['type' => 'array', 'items' => ['type' => 'string']],
                'verification_notes' => ['type' => 'array', 'items' => ['type' => 'string']],
            ],
        ];
    }

    /** @return list<array{role: string, parts: list<array{text: string}>}> */
    private function promptContents(string $prompt): array
    {
        return [[
            'role' => 'user',
            'parts' => [['text' => $prompt]],
        ]];
    }

    /** @return array{contents: list<array{role: string, parts: list<array{text: string}>}>, generationConfig: array<string, mixed>} */
    private function generationRequestPayload(string $prompt, int $maximumOutputTokens): array
    {
        return [
            'contents' => $this->promptContents($prompt),
            'generationConfig' => [
                'temperature' => 0.3,
                'maxOutputTokens' => $maximumOutputTokens,
                'responseMimeType' => 'application/json',
                'responseJsonSchema' => $this->contentResponseSchema(),
            ],
        ];
    }

    private function validateModelAndPrompt(string $modelId, string $prompt): void
    {
        if (preg_match('/\A[A-Za-z0-9][A-Za-z0-9._-]{0,254}\z/D', $modelId) !== 1
            || $prompt === ''
            || strlen($prompt) > self::MAX_PROMPT_BYTES) {
            throw new AiProviderException(
                AiProviderErrorCategory::InvalidRequest,
                'The Gemini request is outside the permitted bounds.',
            );
        }
    }

    private function validateOutputTokenLimit(int $maximumOutputTokens): void
    {
        if ($maximumOutputTokens < 1 || $maximumOutputTokens > self::MAX_OUTPUT_TOKENS) {
            throw new AiProviderException(
                AiProviderErrorCategory::InvalidRequest,
                'The Gemini content generation request is outside the permitted bounds.',
            );
        }
    }

    private function throwConnectionException(ConnectionException $exception): never
    {
        $category = preg_match('/cURL error 28|timed? out|timeout/i', $exception->getMessage()) === 1
            ? AiProviderErrorCategory::Timeout
            : AiProviderErrorCategory::NetworkFailure;

        throw new AiProviderException($category, $this->safeMessage($category));
    }

    /** @return array<string, mixed> */
    private function decodedPayload(string $body): array
    {
        if (strlen($body) > self::MAX_RESPONSE_BYTES) {
            throw new AiProviderException(AiProviderErrorCategory::MalformedResponse, 'The Gemini response exceeded the permitted in-memory size.');
        }

        try {
            $payload = json_decode($body, true, 64, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            throw new AiProviderException(AiProviderErrorCategory::MalformedResponse, 'Gemini returned malformed JSON.');
        }

        if (! is_array($payload) || array_is_list($payload)) {
            throw new AiProviderException(AiProviderErrorCategory::MalformedResponse, 'Gemini returned an unexpected response shape.');
        }

        return $payload;
    }

    private function throwResponseException(Response $response): never
    {
        $providerCode = $this->validatedProviderCode($response);
        $category = $this->classifyFailure($response->status(), $providerCode);

        throw new AiProviderException(
            $category,
            $this->safeMessage($category),
            $providerCode,
        );
    }

    private function validatedProviderCode(Response $response): ?string
    {
        $payload = $response->json();

        if (! is_array($payload) || ! is_array($payload['error'] ?? null)) {
            return null;
        }

        $candidate = $payload['error']['code'] ?? null;

        if (! is_string($candidate)) {
            $candidate = $payload['error']['status'] ?? null;
        }

        if (! is_string($candidate) || preg_match('/\A[A-Za-z][A-Za-z0-9_-]{0,63}\z/D', $candidate) !== 1) {
            return null;
        }

        return mb_strtolower($candidate);
    }

    private function classifyFailure(int $status, ?string $providerCode): AiProviderErrorCategory
    {
        return match (true) {
            in_array($providerCode, ['authentication', 'unauthenticated'], true), $status === 401 => AiProviderErrorCategory::AuthenticationFailed,
            $providerCode === 'payment_required', $status === 402 => AiProviderErrorCategory::BillingRequired,
            $providerCode === 'permission_denied', $status === 403 => AiProviderErrorCategory::PermissionDenied,
            $providerCode === 'model_not_found', $status === 404 => AiProviderErrorCategory::ModelNotFound,
            $providerCode === 'quota_exceeded' => AiProviderErrorCategory::QuotaExceeded,
            in_array($providerCode, ['rate_limit_exceeded', 'too_many_requests'], true), $status === 429 => AiProviderErrorCategory::RateLimited,
            $providerCode === 'deadline_exceeded', $status === 504 => AiProviderErrorCategory::Timeout,
            in_array($providerCode, ['api_error', 'service_unavailable'], true), in_array($status, [500, 502, 503], true) => AiProviderErrorCategory::ProviderUnavailable,
            in_array($status, [400, 404], true) => AiProviderErrorCategory::InvalidRequest,
            default => AiProviderErrorCategory::ProviderUnavailable,
        };
    }

    private function safeMessage(AiProviderErrorCategory $category): string
    {
        return match ($category) {
            AiProviderErrorCategory::MissingCredentials => 'Gemini credentials are not configured.',
            AiProviderErrorCategory::AuthenticationFailed => 'Gemini rejected the configured credentials.',
            AiProviderErrorCategory::PermissionDenied => 'Gemini denied access to the requested resource.',
            AiProviderErrorCategory::BillingRequired => 'Gemini requires billing configuration.',
            AiProviderErrorCategory::InvalidRequest => 'Gemini rejected the model catalog request.',
            AiProviderErrorCategory::ModelNotFound => 'The requested Gemini model was not found.',
            AiProviderErrorCategory::RateLimited => 'Gemini rate-limited the request.',
            AiProviderErrorCategory::QuotaExceeded => 'Gemini quota is exhausted.',
            AiProviderErrorCategory::ProviderUnavailable => 'Gemini is temporarily unavailable.',
            AiProviderErrorCategory::Timeout => 'The Gemini request timed out.',
            AiProviderErrorCategory::NetworkFailure => 'The Gemini request could not reach the provider.',
            AiProviderErrorCategory::MalformedResponse => 'Gemini returned an invalid response.',
        };
    }
}
