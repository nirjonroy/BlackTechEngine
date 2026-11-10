<?php

namespace App\Enums;

enum WordPressConnectionStatus: string
{
    case Connected = 'connected';
    case Error = 'error';
    case Disconnected = 'disconnected';

    public function label(): string
    {
        return match ($this) {
            self::Connected => 'Connected',
            self::Error => 'Error',
            self::Disconnected => 'Disconnected',
        };
    }
}
