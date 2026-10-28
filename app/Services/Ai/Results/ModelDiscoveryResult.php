<?php

namespace App\Services\Ai\Results;

use App\Enums\AiProviderErrorCategory;

final readonly class ModelDiscoveryResult
{
    /**
     * @param  list<DiscoveredAiModel>  $models
     */
    public function __construct(
        public array $models,
        public bool $complete,
        public int $pagesFetched,
        public ?string $incompleteReason = null,
        public ?AiProviderErrorCategory $errorCategory = null,
    ) {}
}
