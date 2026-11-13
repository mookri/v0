<?php

namespace App\Domain\Verification\Enums;

enum ClaimStatus: string
{
    case PENDING = 'pending';
    case GRANTED = 'granted';
    case REJECTED = 'rejected';
    case EXPIRED = 'expired';
    case REVOKED = 'revoked';
    case SUPERSEDED = 'superseded';

    public function isTerminal(): bool
    {
        return match ($this) {
            self::REJECTED, self::EXPIRED, self::REVOKED, self::SUPERSEDED => true,
            self::PENDING, self::GRANTED => false,
        };
    }
}
