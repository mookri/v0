<?php

namespace App\Modules\Customers\Domain\Panchang;

use App\Modules\Customers\Domain\Panchang\Contracts\ComputesPanchang;
use App\Modules\Customers\Domain\Panchang\Exceptions\OutOfCorpusException;
use Carbon\CarbonImmutable;

/**
 * AnchorOccurrenceResolver (E2):
 * Deterministically resolves the next calendar occurrence for any ritual anchor rule
 * (Shraddha, Lunar Birthday, Solar Birthday, Custom Tithi) based on Udaya Tithi.
 */
final class AnchorOccurrenceResolver implements ComputesPanchang
{
    /** Maximum lookahead window in days (~13 lunar months). */
    private const MAX_LOOKAHEAD_DAYS = 400;

    /**
     * @param array<string, mixed> $anchorRule
     */
    public function occurrenceForAnchor(
        array $anchorRule,
        CarbonImmutable $after,
        string $timezone = 'Asia/Kolkata',
        string $tradition = 'PURNIMANTA'
    ): PanchangOccurrence {
        $tradition = strtoupper($anchorRule['tradition'] ?? $tradition);
        $current = $after->setTimezone($timezone)->startOfDay();

        // Check if calendar anniversary
        if (($anchorRule['type'] ?? '') === 'SOLAR_BIRTHDAY' || ($anchorRule['type'] ?? '') === 'WEDDING_ANNIVERSARY') {
            if (isset($anchorRule['month'], $anchorRule['day'])) {
                $targetYear = $current->year;
                $candidate = CarbonImmutable::create($targetYear, (int) $anchorRule['month'], (int) $anchorRule['day'], 0, 0, 0, $timezone);
                if ($candidate <= $current) {
                    $candidate = $candidate->addYear();
                }

                return PanchangCalculator::calculate(
                    $candidate->year,
                    $candidate->month,
                    $candidate->day,
                    23.1765,
                    75.7885,
                    $tradition
                );
            }
        }

        // Tithi / Masa based search (Shraddha, Lunar Birthday, Custom Tithi)
        $targetTithi = isset($anchorRule['tithi']) ? (int) $anchorRule['tithi'] : null;
        $targetPaksha = isset($anchorRule['paksha']) ? strtoupper((string) $anchorRule['paksha']) : null;
        $targetMasa = isset($anchorRule['masa']) ? strtoupper((string) $anchorRule['masa']) : null;
        $targetIndexInPaksha = isset($anchorRule['index_in_paksha']) ? (int) $anchorRule['index_in_paksha'] : null;

        for ($dayOffset = 1; $dayOffset <= self::MAX_LOOKAHEAD_DAYS; $dayOffset++) {
            $candidateDate = $current->addDays($dayOffset);

            // Bounded corpus check: verified through 2028
            if ($candidateDate->year > 2028) {
                throw new OutOfCorpusException("Occurrence calculation for year {$candidateDate->year} falls outside verified ephemeris corpus.");
            }

            $panchang = PanchangCalculator::calculate(
                $candidateDate->year,
                $candidateDate->month,
                $candidateDate->day,
                23.1765,
                75.7885,
                $tradition
            );

            if ($this->matchesAnchor($panchang, $targetTithi, $targetPaksha, $targetMasa, $targetIndexInPaksha, $tradition)) {
                return $panchang;
            }
        }

        throw new OutOfCorpusException('Unable to resolve anchor occurrence within 400 days.');
    }

    private function matchesAnchor(
        PanchangOccurrence $panchang,
        ?int $targetTithi,
        ?string $targetPaksha,
        ?string $targetMasa,
        ?int $targetIndexInPaksha,
        string $tradition
    ): bool {
        // 1. Tithi matching
        if ($targetTithi !== null && $panchang->tithi['number'] !== $targetTithi) {
            return false;
        }

        // 2. Index in paksha (e.g. 11 for Ekadashi in either paksha)
        if ($targetIndexInPaksha !== null && $panchang->tithi['index_in_paksha'] !== $targetIndexInPaksha) {
            return false;
        }

        // 3. Paksha matching
        if ($targetPaksha !== null && $panchang->tithi['paksha'] !== $targetPaksha) {
            return false;
        }

        // 4. Masa matching
        if ($targetMasa !== null) {
            $resolvedMasa = $panchang->resolvedMasa();
            // Handle optional ADHIKA prefix or direct match
            if ($resolvedMasa !== $targetMasa && ! str_ends_with($resolvedMasa, "_{$targetMasa}")) {
                return false;
            }
        }

        return true;
    }
}
