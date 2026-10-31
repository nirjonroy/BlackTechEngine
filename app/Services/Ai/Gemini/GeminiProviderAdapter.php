<?php

namespace App\Services\Ai\Gemini;

use App\Contracts\AiProviderAdapter;
use App\Enums\AiProviderErrorCategory;
use App\Services\Ai\AiProviderCredentialResolver;
use App\Services\Ai\Exceptions\AiProviderException;
use App\Services\Ai\Results\AiTextGenerationResult;
use App\Services\Ai\Results\DiscoveredAiModel;
use App\Services\Ai\Results\GeneratedAiContent;
use App\Services\Ai\Results\ModelDiscoveryResult;
use App\Services\Ai\Results\ProviderVerificationResult;
use JsonException;

class GeminiProviderAdapter implements AiProviderAdapter
{
    public const IDENTIFIER = 'gemini';

    private const DISCOVERY_PAGE_SIZE = 50;

    private const MAX_DISCOVERED_MODELS = 500;

    private const MAX_DISCOVERY_PAGES = 10;

    public function __construct(
        private readonly AiProviderCredentialResolver $credentialResolver,
        private readonly GeminiHttpTransport $transport,
    ) {}

    public function identifier(): string
    {
        return self::IDENTIFIER;
    }

    public function hasCredentials(): bool
    {
        return $this->credentialResolver->hasGeminiCredentials();
    }

    public function verifyConnection(): ProviderVerificationResult
    {
        if (! $this->hasCredentials()) {
            return ProviderVerificationResult::failure(AiProviderErrorCategory::MissingCredentials);
        }

        try {
            $this->parsePage($this->transport->listModels(1));
        } catch (AiProviderException $exception) {
            return ProviderVerificationResult::failure($exception->category);
        }

        return ProviderVerificationResult::success();
    }

    public function discoverModels(): ModelDiscoveryResult
    {
        $discoveredModels = [];
        $seenResourceNames = [];
        $seenPageTokens = [];
        $pageToken = null;
        $pagesFetched = 0;

        while ($pagesFetched < self::MAX_DISCOVERY_PAGES) {
            try {
                [$models, $nextPageToken] = $this->parsePage(
                    $this->transport->listModels(self::DISCOVERY_PAGE_SIZE, $pageToken),
                );
            } catch (AiProviderException $exception) {
                if ($pagesFetched === 0) {
                    throw $exception;
                }

                return new ModelDiscoveryResult(
                    $discoveredModels,
                    false,
                    $pagesFetched,
                    'provider_error',
                    $exception->category,
                );
            }

            $pagesFetched++;

            foreach ($models as $model) {
                if (isset($seenResourceNames[$model->resourceName])) {
                    return new ModelDiscoveryResult(
                        $discoveredModels,
                        false,
                        $pagesFetched,
                        'duplicate_model',
                        AiProviderErrorCategory::MalformedResponse,
                    );
                }

                if (count($discoveredModels) >= self::MAX_DISCOVERED_MODELS) {
                    return new ModelDiscoveryResult(
                        $discoveredModels,
                        false,
                        $pagesFetched,
                        'model_limit',
                    );
                }

                $seenResourceNames[$model->resourceName] = true;
                $discoveredModels[] = $model;
            }

            if ($nextPageToken === null) {
                return new ModelDiscoveryResult($discoveredModels, true, $pagesFetched);
            }

            if (isset($seenPageTokens[$nextPageToken]) || $nextPageToken === $pageToken) {
                return new ModelDiscoveryResult(
                    $discoveredModels,
                    false,
                    $pagesFetched,
                    'malformed_pagination',
                    AiProviderErrorCategory::MalformedResponse,
                );
            }

            $seenPageTokens[$nextPageToken] = true;
            $pageToken = $nextPageToken;
        }

        return new ModelDiscoveryResult(
            $discoveredModels,
            false,
            $pagesFetched,
            'page_limit',
        );
    }

    public function generateStructuredContent(string $modelId, string $prompt, int $maximumOutputTokens): AiTextGenerationResult
    {
        if (! $this->hasCredentials()) {
            throw new AiProviderException(AiProviderErrorCategory::MissingCredentials, 'Gemini credentials are not configured.');
        }

        $payload = $this->transport->generateContent($modelId, $prompt, $maximumOutputTokens);
        $candidate = $payload['candidates'][0] ?? null;
        $text = is_array($candidate) ? ($candidate['content']['parts'][0]['text'] ?? null) : null;
        $finishReason = is_array($candidate) ? ($candidate['finishReason'] ?? null) : null;

        if (! is_string($text) || $text === '' || ($finishReason !== null && $finishReason !== 'STOP')) {
            $this->throwMalformedContentResponse();
        }

        try {
            $structured = json_decode($text, true, 64, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            $this->throwMalformedContentResponse();
        }

        if (! is_array($structured) || array_is_list($structured)) {
            $this->throwMalformedContentResponse();
        }

        $usage = is_array($payload['usageMetadata'] ?? null) ? $payload['usageMetadata'] : [];

        return new AiTextGenerationResult(
            GeneratedAiContent::fromArray($structured),
            $this->optionalTokenCount($usage, 'promptTokenCount'),
            $this->optionalTokenCount($usage, 'candidatesTokenCount'),
        );
    }

    /** @param array<string, mixed> $usage */
    private function optionalTokenCount(array $usage, string $key): ?int
    {
        if (! array_key_exists($key, $usage)) {
            return null;
        }

        if (! is_int($usage[$key]) || $usage[$key] < 0) {
            $this->throwMalformedContentResponse();
        }

        return $usage[$key];
    }

    private function throwMalformedContentResponse(): never
    {
        throw new AiProviderException(
            AiProviderErrorCategory::MalformedResponse,
            'Gemini returned an invalid structured content response.',
        );
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array{0: list<DiscoveredAiModel>, 1: string|null}
     */
    private function parsePage(array $payload): array
    {
        $rawModels = $payload['models'] ?? [];

        if (! is_array($rawModels) || ! array_is_list($rawModels)) {
            $this->throwMalformedResponse();
        }

        $models = [];

        foreach ($rawModels as $rawModel) {
            if (! is_array($rawModel) || array_is_list($rawModel)) {
                $this->throwMalformedResponse();
            }

            $models[] = $this->normalizeModel($rawModel);
        }

        $nextPageToken = $payload['nextPageToken'] ?? null;

        if ($nextPageToken !== null) {
            if (! is_string($nextPageToken) || $nextPageToken === '' || mb_strlen($nextPageToken) > 4096) {
                $this->throwMalformedResponse();
            }
        }

        return [$models, $nextPageToken];
    }

    /**
     * @param  array<string, mixed>  $rawModel
     */
    private function normalizeModel(array $rawModel): DiscoveredAiModel
    {
        $resourceName = $this->requiredString($rawModel, 'name', 262);

        if (preg_match('/\Amodels\/[A-Za-z0-9][A-Za-z0-9._-]{0,254}\z/D', $resourceName) !== 1) {
            $this->throwMalformedResponse();
        }

        $generationMethodsKnown = array_key_exists('supportedGenerationMethods', $rawModel);
        $supportedGenerationMethods = $generationMethodsKnown
            ? $this->stringList($rawModel['supportedGenerationMethods'])
            : [];

        return new DiscoveredAiModel(
            resourceName: $resourceName,
            providerModelId: substr($resourceName, strlen('models/')),
            baseModelId: $this->requiredString($rawModel, 'baseModelId', 255),
            version: $this->requiredString($rawModel, 'version', 64),
            displayName: $this->optionalString($rawModel, 'displayName', 128),
            description: $this->optionalString($rawModel, 'description', 4000),
            inputTokenLimit: $this->optionalPositiveInteger($rawModel, 'inputTokenLimit'),
            outputTokenLimit: $this->optionalPositiveInteger($rawModel, 'outputTokenLimit'),
            supportedGenerationMethods: $supportedGenerationMethods,
            generationMethodsKnown: $generationMethodsKnown,
            supportsThinking: $this->optionalBoolean($rawModel, 'thinking'),
        );
    }

    /**
     * @param  array<string, mixed>  $values
     */
    private function requiredString(array $values, string $key, int $maximumLength): string
    {
        $value = $values[$key] ?? null;

        if (! is_string($value) || $value === '' || mb_strlen($value) > $maximumLength || preg_match('/[\x00-\x1F\x7F]/', $value) === 1) {
            $this->throwMalformedResponse();
        }

        return $value;
    }

    /**
     * @param  array<string, mixed>  $values
     */
    private function optionalString(array $values, string $key, int $maximumLength): ?string
    {
        if (! array_key_exists($key, $values)) {
            return null;
        }

        $value = $values[$key];

        if (! is_string($value) || mb_strlen($value) > $maximumLength || preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', $value) === 1) {
            $this->throwMalformedResponse();
        }

        return $value;
    }

    /**
     * @param  array<string, mixed>  $values
     */
    private function optionalPositiveInteger(array $values, string $key): ?int
    {
        if (! array_key_exists($key, $values)) {
            return null;
        }

        $value = $values[$key];

        if (! is_int($value) || $value < 1) {
            $this->throwMalformedResponse();
        }

        return $value;
    }

    /**
     * @param  array<string, mixed>  $values
     */
    private function optionalBoolean(array $values, string $key): ?bool
    {
        if (! array_key_exists($key, $values)) {
            return null;
        }

        if (! is_bool($values[$key])) {
            $this->throwMalformedResponse();
        }

        return $values[$key];
    }

    /**
     * @return list<string>
     */
    private function stringList(mixed $values): array
    {
        if (! is_array($values) || ! array_is_list($values) || count($values) > 50) {
            $this->throwMalformedResponse();
        }

        $validatedValues = [];

        foreach ($values as $value) {
            if (! is_string($value)
                || preg_match('/\A[A-Za-z][A-Za-z0-9]{0,63}\z/D', $value) !== 1
                || in_array($value, $validatedValues, true)) {
                $this->throwMalformedResponse();
            }

            $validatedValues[] = $value;
        }

        return $validatedValues;
    }

    private function throwMalformedResponse(): never
    {
        throw new AiProviderException(
            AiProviderErrorCategory::MalformedResponse,
            'Gemini returned an unexpected model catalog response.',
        );
    }
}
