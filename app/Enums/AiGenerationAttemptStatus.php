<?php

namespace App\Enums;

enum AiGenerationAttemptStatus: string
{
    case Pending = 'pending';
    case Completed = 'completed';
    case Failed = 'failed';
}
