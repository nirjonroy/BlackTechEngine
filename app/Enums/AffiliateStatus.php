<?php

namespace App\Enums;

enum AffiliateStatus: string
{
    case Pending = 'pending';
    case Active = 'active';
    case Suspended = 'suspended';
    case Closed = 'closed';
}
