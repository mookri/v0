<?php

declare(strict_types=1);

namespace App\Modules\Support\Domain\Enums;

/**
 * Full SupportCase lifecycle (TS-03, §9.2, §13.1):
 * Longest string is REFERRED_EXTERNALLY / RESOLUTION_PROPOSED (19) or
 * WAITING_FOR_CUSTOMER (20); column varchar(40) enforces margin >= 8.
 */
enum CaseStatus: string
{
    case NEW = 'NEW';
    case ACKNOWLEDGED = 'ACKNOWLEDGED';
    case CLASSIFIED = 'CLASSIFIED';
    case TRIAGED = 'TRIAGED';
    case UNDER_INVESTIGATION = 'UNDER_INVESTIGATION';
    case WAITING_FOR_CUSTOMER = 'WAITING_FOR_CUSTOMER';
    case PENDING_THIRD_PARTY = 'PENDING_THIRD_PARTY';
    case RESOLUTION_PROPOSED = 'RESOLUTION_PROPOSED';
    case REFUND_PENDING = 'REFUND_PENDING';
    case REFUND_COMPLETED = 'REFUND_COMPLETED';
    case RESOLVED = 'RESOLVED';
    case CLOSED = 'CLOSED';
    case REOPENED = 'REOPENED';
    case WITHDRAWN = 'WITHDRAWN';
    case REFERRED_EXTERNALLY = 'REFERRED_EXTERNALLY';
}

