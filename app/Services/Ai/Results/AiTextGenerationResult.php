<?php

namespace App\Services\Ai\Results;

final readonly class AiTextGenerationResult
{
    public function __construct(
        public GeneratedAiContent $content,
        public ?int $inputTokens,
        public ?int $outputTokens,
    ) {}
}
