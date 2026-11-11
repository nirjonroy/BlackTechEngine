<?php

namespace App\Enums;

enum ArticleScheduleTargetStatus: string
{
    case Draft = 'draft';
    case NeedsReview = 'needs_review';
    case WordPressPublish = 'wordpress_publish';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Save as Draft',
            self::NeedsReview => 'Submit for Review',
            self::WordPressPublish => 'Auto-publish to WordPress',
        };
    }
}

