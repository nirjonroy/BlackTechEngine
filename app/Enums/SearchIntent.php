<?php

namespace App\Enums;

enum SearchIntent: string
{
    case Informational = 'informational';
    case Commercial = 'commercial';
    case Transactional = 'transactional';
    case Navigational = 'navigational';

    public function label(): string
    {
        return match ($this) {
            self::Informational => 'Informational',
            self::Commercial => 'Commercial',
            self::Transactional => 'Transactional',
            self::Navigational => 'Navigational',
        };
    }

    public function badgeClasses(): string
    {
        return match ($this) {
            self::Informational => 'bg-blue-100 text-blue-700 dark:bg-blue-950 dark:text-blue-300',
            self::Commercial => 'bg-amber-100 text-amber-700 dark:bg-amber-950 dark:text-amber-300',
            self::Transactional => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300',
            self::Navigational => 'bg-purple-100 text-purple-700 dark:bg-purple-950 dark:text-purple-300',
        };
    }

    public function badgeClass(): string
    {
        return $this->badgeClasses();
    }
}
