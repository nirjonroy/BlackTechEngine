<?php

namespace App\Enums;

enum SocialPostStatus: string
{
    case Draft = 'draft';
    case NeedsReview = 'needs_review';
    case ChangesRequested = 'changes_requested';
    case Approved = 'approved';
    case Planned = 'planned';
    case ManuallyPublished = 'manually_published';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return str($this->value)->replace('_', ' ')->title()->toString();
    }
}
