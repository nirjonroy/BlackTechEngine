<?php

namespace App\Services\Subscriptions;

final readonly class EntitlementDecision
{
    public function __construct(
        public bool $allowed,
        public bool $enabled,
        public ?int $limit,
        public int $used,
        public ?int $remaining,
        public string $reason,
    ) {}
}
