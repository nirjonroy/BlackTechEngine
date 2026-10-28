<?php

namespace App\Services\Ai\Exceptions;

use App\Enums\AiProviderErrorCategory;
use RuntimeException;

final class AiProviderException extends RuntimeException
{
    public function __construct(
        public readonly AiProviderErrorCategory $category,
        string $message,
        public readonly ?string $providerCode = null,
    ) {
        parent::__construct($message);
    }
}
