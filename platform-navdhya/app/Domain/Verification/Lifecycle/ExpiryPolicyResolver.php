<?php

namespace App\Domain\Verification\Lifecycle;

use App\Domain\Verification\Enums\ClaimKind;
use Carbon\CarbonImmutable;
use DateTimeInterface;

/**
 * Mandatory expiry schedules per claim kind (T-TR07-04.1 / Closes T-18).
 */
final class ExpiryPolicyResolver
{
    public function resolveExpiryFor(string $claimKind, ?DateTimeInterface $from = null): CarbonImmutable
    {
        $base = $from !== null ? CarbonImmutable::instance($from)->utc() : CarbonImmutable::now('UTC');

        $months = match ($claimKind) {
            ClaimKind::TAX->value, ClaimKind::ANTECEDENT->value => 12,
            ClaimKind::INSTITUTIONAL_AUTHORITY->value, ClaimKind::COMPETENCY->value => 24,
            ClaimKind::QUALIFICATION->value, ClaimKind::FINANCIAL->value => 36,
            default => 60,
        };

        return $base->addMonths($months);
    }
}

