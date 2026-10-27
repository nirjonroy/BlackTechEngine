<?php

namespace App\Enums;

enum AiModelAvailabilityStatus: string
{
    case Unverified = 'unverified';
    case Available = 'available';
    case Unavailable = 'unavailable';
    case Retired = 'retired';
}
