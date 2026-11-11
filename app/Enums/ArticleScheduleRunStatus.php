<?php

namespace App\Enums;

enum ArticleScheduleRunStatus: string
{
    case Pending = 'pending';
    case Generating = 'generating';
    case Completed = 'completed';
    case Failed = 'failed';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::Generating => 'Generating Content',
            self::Completed => 'Completed',
            self::Failed => 'Failed',
        };
    }
}

