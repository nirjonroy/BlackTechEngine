<?php

namespace App\Services\Ai;

use App\Contracts\AiProviderAdapter;
use App\Services\Ai\Gemini\GeminiProviderAdapter;
use InvalidArgumentException;

class AiProviderAdapterRegistry
{
    /** @var array<string, AiProviderAdapter> */
    private array $adapters;

    public function __construct(GeminiProviderAdapter $geminiProviderAdapter)
    {
        $this->adapters = [
            $geminiProviderAdapter->identifier() => $geminiProviderAdapter,
        ];
    }

    public function resolve(string $identifier): AiProviderAdapter
    {
        if (! array_key_exists($identifier, $this->adapters)) {
            throw new InvalidArgumentException('The requested AI provider adapter is not registered.');
        }

        return $this->adapters[$identifier];
    }
}
