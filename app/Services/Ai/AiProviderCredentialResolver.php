<?php

namespace App\Services\Ai;

use App\Enums\AiProviderErrorCategory;
use App\Models\IntegrationSetting;
use App\Services\Ai\Exceptions\AiProviderException;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Schema;

class AiProviderCredentialResolver
{
    public function hasGeminiCredentials(): bool
    {
        $credential = $this->configuredCredential();

        return is_string($credential) && trim($credential) !== '';
    }

    public function geminiApiKey(): string
    {
        $credential = $this->configuredCredential();

        if (! is_string($credential) || trim($credential) === '') {
            throw new AiProviderException(
                AiProviderErrorCategory::MissingCredentials,
                'Gemini credentials are not configured.',
            );
        }

        return trim($credential);
    }

    private function configuredCredential(): mixed
    {
        if (Schema::hasTable('integration_settings')) {
            $setting = IntegrationSetting::query()->find(1);

            if ($setting instanceof IntegrationSetting && $setting->gemini_managed) {
                try {
                    return $setting->gemini_enabled ? $setting->gemini_api_key : null;
                } catch (DecryptException) {
                    return null;
                }
            }
        }

        return config('services.gemini.api_key');
    }
}
