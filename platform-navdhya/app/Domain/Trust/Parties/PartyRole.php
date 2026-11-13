<?php

declare(strict_types=1);

namespace App\Domain\Trust\Parties;

enum PartyRole: string
{
    case DEVOTEE = 'devotee';
    case PROVIDER = 'provider';
    case INSTITUTION = 'institution';
    case PLATFORM = 'platform';
    case REPRESENTATIVE = 'representative';
}

