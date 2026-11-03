<?php

namespace App\Enums;

enum UsageEventStatus: string
{
    case Reserved = 'reserved';
    case Committed = 'committed';
    case Released = 'released';
}
