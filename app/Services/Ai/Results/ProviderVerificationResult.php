<?php

namespace App\Services\Ai\Results;

use App\Enums\AiProviderErrorCategory;

final readonly class ProviderVerificationResult
{
    private function __construct(
        public bool $successful,
        public ?AiProviderErrorCategory $errorCategory,
    ) {}

    public static function success(): self
    {
        return new self(true, null);
    }

    public static function failure(AiProviderErrorCategory $errorCategory): self
    {
        return new self(false, $errorCategory);
    }
}
