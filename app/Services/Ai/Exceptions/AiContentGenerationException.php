<?php

namespace App\Services\Ai\Exceptions;

use RuntimeException;

final class AiContentGenerationException extends RuntimeException
{
    public function __construct(
        public readonly string $category,
        string $message,
    ) {
        parent::__construct($message);
    }
}
