<?php

namespace App\Services;

use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpKernel\Exception\HttpException;

class AiProviderVerificationGate
{
    public const UNAVAILABLE_MESSAGE = 'AI provider binding and verification are currently disabled.';

    public function isAvailable(): bool
    {
        return (bool) config('ai.provider_verification_enabled', false)
            && Schema::hasColumns('ai_providers', ['adapter_type', 'health_status', 'last_verified_at', 'lock_version']);
    }

    public function ensureAvailable(): void
    {
        if (! $this->isAvailable()) {
            throw new HttpException(503, self::UNAVAILABLE_MESSAGE);
        }
    }
}
