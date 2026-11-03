<?php

namespace App\Enums;

enum SubscriptionStatus: string
{
    case PendingActivation = 'pending_activation';
    case Trialing = 'trialing';
    case Active = 'active';
    case PastDue = 'past_due';
    case Cancelled = 'cancelled';
    case Expired = 'expired';

    public function isEntitled(): bool
    {
        return in_array($this, [self::Trialing, self::Active], true);
    }

    public function label(): string
    {
        return str($this->value)->replace('_', ' ')->title()->toString();
    }
}
