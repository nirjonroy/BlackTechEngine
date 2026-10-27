<?php

namespace App\Services;

use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpKernel\Exception\HttpException;

class AiMetadataWriteGate
{
    public const UNAVAILABLE_MESSAGE = 'AI metadata changes are unavailable until the concurrency migration is deployed and the write gate is enabled.';

    public function isAvailable(): bool
    {
        return (bool) config('ai.metadata_writes_enabled', false)
            && Schema::hasColumn('ai_providers', 'lock_version')
            && Schema::hasColumn('ai_models', 'lock_version');
    }

    public function ensureAvailable(): void
    {
        if (! $this->isAvailable()) {
            throw new HttpException(503, self::UNAVAILABLE_MESSAGE);
        }
    }
}
