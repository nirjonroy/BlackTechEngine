<?php

namespace App\Enums;

enum AiContentDraftStatus: string
{
    case Draft = 'draft';
    case NeedsReview = 'needs_review';
    case Approved = 'approved';
    case ExportReady = 'export_ready';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::NeedsReview => 'Needs review',
            self::Approved => 'Approved',
            self::ExportReady => 'Export ready',
        };
    }
}
