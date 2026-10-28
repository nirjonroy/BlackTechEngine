<?php

namespace App\Services\Ai;

use App\Enums\AiProviderErrorCategory;
use App\Services\Ai\Exceptions\AiProviderException;

class AiProviderCredentialResolver
{
    public function hasGeminiCredentials(): bool
    {
        $credential = config('services.gemini.api_key');

        return is_string($credential) && trim($credential) !== '';
    }

    public function geminiApiKey(): string
    {
        $credential = config('services.gemini.api_key');

        if (! is_string($credential) || trim($credential) === '') {
            throw new AiProviderException(
                AiProviderErrorCategory::MissingCredentials,
                'Gemini credentials are not configured.',
            );
        }

        return trim($credential);
    }
}
