<?php

namespace App\Domain\Verification\Issuers;

enum IssuerKind: string
{
    case PATHASHALA = 'pathashala';
    case AKHARA = 'akhara';
    case UNIVERSITY = 'university';
    case BOARD = 'board';
    case INDIVIDUAL_GURU = 'individual_guru';
    case OTHER = 'other';
}

