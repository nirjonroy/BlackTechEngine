<?php

namespace App\Services;

use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpKernel\Exception\HttpException;

class AiWorkloadDraftWriteGate
{
    public const UNAVAILABLE_MESSAGE = 'AI workload draft changes are unavailable until the concurrency migration is deployed and the draft write gate is enabled.';

    public function isAvailable(): bool
    {
        return (bool) config('ai.workload_draft_writes_enabled', false)
            && Schema::hasColumn('ai_workload_routes', 'lock_version');
    }

    public function ensureAvailable(): void
    {
        if (! $this->isAvailable()) {
            throw new HttpException(503, self::UNAVAILABLE_MESSAGE);
        }
    }
}
