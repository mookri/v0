<?php

namespace App\Domain\Verification\Enums;

enum ConfidenceLevel: string
{
    case UNASSESSED = 'unassessed';
    case LOW = 'low';
    case MEDIUM = 'medium';
    case HIGH = 'high';
}
