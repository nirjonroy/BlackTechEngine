<?php

namespace App\Enums;

enum ReconciliationStatus: string
{
    case Unreconciled = 'unreconciled';
    case Matched = 'matched';
    case Mismatch = 'mismatch';
}
