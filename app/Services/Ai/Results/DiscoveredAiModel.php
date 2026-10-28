<?php

namespace App\Services\Ai\Results;

use App\Enums\AiCapabilitySupport;
use App\Enums\AiModelCapability;

final readonly class DiscoveredAiModel
{
    /**
     * @param  list<string>  $supportedGenerationMethods
     */
    public function __construct(
        public string $resourceName,
        public string $providerModelId,
        public string $baseModelId,
        public string $version,
        public ?string $displayName,
        public ?string $description,
        public ?int $inputTokenLimit,
        public ?int $outputTokenLimit,
        public array $supportedGenerationMethods,
        public bool $generationMethodsKnown,
        public ?bool $supportsThinking,
    ) {}

    public function capabilitySupport(AiModelCapability $capability): AiCapabilitySupport
    {
        if (! $this->generationMethodsKnown) {
            return AiCapabilitySupport::Unknown;
        }

        return match ($capability) {
            AiModelCapability::TextGeneration => $this->methodSupport('generateContent'),
            AiModelCapability::Embeddings => $this->methodSupport('embedContent'),
            AiModelCapability::StructuredOutput,
            AiModelCapability::ImageGeneration => AiCapabilitySupport::Unknown,
        };
    }

    private function methodSupport(string $method): AiCapabilitySupport
    {
        return in_array($method, $this->supportedGenerationMethods, true)
            ? AiCapabilitySupport::Supported
            : AiCapabilitySupport::Unsupported;
    }
}
