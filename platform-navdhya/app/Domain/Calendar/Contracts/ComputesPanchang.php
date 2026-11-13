<?php

namespace App\Domain\Calendar\Contracts;

use App\Domain\Calendar\Panchang\PanchangOccurrence;
use Carbon\CarbonImmutable;

/**
 * Pure Panchang astronomical recurrence port.
 */
interface ComputesPanchang
{
    /**
     * Resolve the next calendar occurrence for an anchor rule after a given baseline date.
     *
     * @param array<string, mixed> $anchorRule Authoritative rule (tithi, paksha, masa, tradition, etc.)
     * @param CarbonImmutable $after Baseline threshold date
     * @param string $timezone Local devotee timezone
     * @param string $tradition AMANTA | PURNIMANTA
     * @return PanchangOccurrence
     */
    public function occurrenceForAnchor(
        array $anchorRule,
        CarbonImmutable $after,
        string $timezone = 'Asia/Kolkata',
        string $tradition = 'PURNIMANTA'
    ): PanchangOccurrence;
}

