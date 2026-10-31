<?php

namespace App\Enums;

enum AiContentDraftStatus: string
{
    case Draft = 'draft';
    case NeedsReview = 'needs_review';
}
