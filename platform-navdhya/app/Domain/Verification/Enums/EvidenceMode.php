<?php

namespace App\Domain\Verification\Enums;

enum EvidenceMode: string
{
    case NATIVE = 'native';
    case MANUAL = 'manual';
    case OFFICIAL = 'official';
    case FINANCIAL = 'financial';

    /**
     * Only authoritative official or financial proofs may be surfaced as Verified.
     * Native and manual checks must present as Reviewed / Manually Reviewed.
     */
    public function allowsOfficialVerifiedLabel(): bool
    {
        return match ($this) {
            self::OFFICIAL, self::FINANCIAL => true,
            self::NATIVE, self::MANUAL => false,
        };
    }

    public function badgeLabel(): string
    {
        return match ($this) {
            self::OFFICIAL => 'Verified (Official Registry)',
            self::FINANCIAL => 'Verified (Financial Instrument)',
            self::MANUAL => 'Manually Reviewed',
            self::NATIVE => 'Self-Declared',
        };
    }
}
