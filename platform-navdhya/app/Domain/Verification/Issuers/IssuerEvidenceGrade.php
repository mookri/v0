<?php

namespace App\Domain\Verification\Issuers;

enum IssuerEvidenceGrade: string
{
    case UNCONFIRMED = 'unconfirmed';
    case CONTACT_FOUND = 'contact_found';
    case REGISTERED_ENTITY = 'registered_entity';
    case RESPONDING = 'responding';
    case AUTHORITATIVE = 'authoritative';
}

