<?php

namespace App\Enums;

enum PaymentType: string
{
    case Charge = 'charge';
    case Refund = 'refund';
}
