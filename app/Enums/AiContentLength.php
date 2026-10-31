<?php

namespace App\Enums;

enum AiContentLength: string
{
    case Short = 'short';
    case Standard = 'standard';
    case Long = 'long';

    public function label(): string
    {
        return match ($this) {
            self::Short => 'Short (600–900 words)',
            self::Standard => 'Standard (1,000–1,500 words)',
            self::Long => 'Long (1,800–2,500 words)',
        };
    }

    public function targetWords(): string
    {
        return match ($this) {
            self::Short => '600 to 900',
            self::Standard => '1000 to 1500',
            self::Long => '1800 to 2500',
        };
    }

    public function maximumOutputTokens(): int
    {
        return match ($this) {
            self::Short => 2500,
            self::Standard => 4500,
            self::Long => 7000,
        };
    }
}
