<?php

namespace App\Enums;

enum AiCapabilitySupport: string
{
    case Supported = 'supported';
    case Unsupported = 'unsupported';
    case Unknown = 'unknown';
}
