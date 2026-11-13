<?php

namespace App\Domain\Verification\Issuers;

enum OutreachStage: string
{
    case REQUEST_SENT = 'REQUEST_SENT';
    case ACKNOWLEDGED = 'ACKNOWLEDGED';
    case RESPONSE_RECEIVED = 'RESPONSE_RECEIVED';
    case NO_RESPONSE = 'NO_RESPONSE';
    case DENIED = 'DENIED';
}

