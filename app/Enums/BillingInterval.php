<?php

namespace App\Enums;

enum BillingInterval: string
{
    case Monthly = 'monthly';
    case Annual = 'annual';
    case Manual = 'manual';
}
