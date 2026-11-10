<?php

namespace App\Enums;

enum WordPressPostStatus: string
{
    case Draft = 'draft';
    case Publish = 'publish';
    case Failed = 'failed';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Publish => 'Published',
            self::Failed => 'Failed',
        };
    }
}
