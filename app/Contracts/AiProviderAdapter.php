<?php

namespace App\Contracts;

use App\Services\Ai\Results\ModelDiscoveryResult;
use App\Services\Ai\Results\ProviderVerificationResult;

interface AiProviderAdapter
{
    public function identifier(): string;

    public function hasCredentials(): bool;

    public function verifyConnection(): ProviderVerificationResult;

    public function discoverModels(): ModelDiscoveryResult;
}
