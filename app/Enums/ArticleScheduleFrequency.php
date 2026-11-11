<?php

namespace App\Enums;

enum ArticleScheduleFrequency: string
{
    case Once = 'once';
    case Daily = 'daily';
    case Weekly = 'weekly';

    public function label(): string
    {
        return match ($this) {
            self::Once => 'One-time',
            self::Daily => 'Daily recurring',
            self::Weekly => 'Weekly recurring',
        };
    }
}

