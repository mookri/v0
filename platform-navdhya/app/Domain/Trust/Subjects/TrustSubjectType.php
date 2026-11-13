<?php

declare(strict_types=1);

namespace App\Domain\Trust\Subjects;

/**
 * Authoritative trust subject types aligned 1-to-1 with @navdhya/trust-contracts
 * and @navdhya/feedback SubjectRef.
 */
enum TrustSubjectType: string
{
    case PANDIT = 'pandit';
    case TEMPLE = 'temple';
    case VENDOR = 'vendor';
    case SERVICE_INSTANCE = 'service_instance';
}

