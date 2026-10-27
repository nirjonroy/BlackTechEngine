<?php

namespace App\Enums;

enum AiProviderHealthStatus: string
{
    case Unverified = 'unverified';
    case Healthy = 'healthy';
    case Degraded = 'degraded';
    case Unavailable = 'unavailable';
}
