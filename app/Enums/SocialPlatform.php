<?php

namespace App\Enums;

enum SocialPlatform: string
{
    case Facebook = 'facebook';
    case Instagram = 'instagram';
    case LinkedIn = 'linkedin';
    case X = 'x';
    case GoogleBusinessProfile = 'google_business_profile';

    public function label(): string
    {
        return match ($this) {
            self::Facebook => 'Facebook',
            self::Instagram => 'Instagram',
            self::LinkedIn => 'LinkedIn',
            self::X => 'X',
            self::GoogleBusinessProfile => 'Google Business Profile',
        };
    }

    public function advisoryLimit(): int
    {
        return match ($this) {
            self::X => 280,
            self::Instagram => 2200,
            self::GoogleBusinessProfile => 1500,
            default => 3000,
        };
    }
}
