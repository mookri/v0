<?php

namespace App\Domain\Calendar\Panchang;

use App\Domain\Calendar\Contracts\ComputesPanchang;
use App\Domain\Calendar\Exceptions\OutOfCorpusException;
use Carbon\CarbonImmutable;

/**
 * AnchorOccurrenceResolver:
 * Deterministically resolves the next calendar occurrence for any ritual anchor rule
 * (Shraddha, Lunar Birthday, Solar Birthday, Custom Tithi) based on Udaya Tithi.
 */
final class AnchorOccurrenceResolver implements ComputesPanchang
{
    /** Default fallback location: Ujjain, India (Avanti - classical prime meridian of Hindu astronomy). */
    public const DEFAULT_LATITUDE = 23.1765;
    public const DEFAULT_LONGITUDE = 75.7885;

    /**
     * @param array<string, mixed> $anchorRule
     */
    public function occurrenceForAnchor(
        array $anchorRule,
        CarbonImmutable $after,
        string $timezone = 'Asia/Kolkata',
        string $tradition = 'PURNIMANTA'
    ): PanchangOccurrence {
        $tradition = strtoupper((string) ($anchorRule['tradition'] ?? $tradition));
        $resolvedTz = (string) ($anchorRule['timezone'] ?? $timezone);
        $current = $after->setTimezone($resolvedTz)->startOfDay();

        $latitude = isset($anchorRule['latitude']) ? (float) $anchorRule['latitude'] : self::DEFAULT_LATITUDE;
        $longitude = isset($anchorRule['longitude']) ? (float) $anchorRule['longitude'] : self::DEFAULT_LONGITUDE;

        // Check if calendar anniversary
        if (($anchorRule['type'] ?? '') === 'SOLAR_BIRTHDAY' || ($anchorRule['type'] ?? '') === 'WEDDING_ANNIVERSARY') {
            if (isset($anchorRule['month'], $anchorRule['day'])) {
                $targetYear = $current->year;
                $candidate = CarbonImmutable::create($targetYear, (int) $anchorRule['month'], (int) $anchorRule['day'], 0, 0, 0, $resolvedTz);
                if ($candidate <= $current) {
                    $candidate = $candidate->addYear();
                }

                return PanchangCalculator::calculate(
                    $candidate->year,
                    $candidate->month,
                    $candidate->day,
                    $latitude,
                    $longitude,
                    $tradition
                );
            }
        }

        // Tithi / Masa based search (Shraddha, Lunar Birthday, Custom Tithi)
        $targetTithi = isset($anchorRule['tithi']) ? (int) $anchorRule['tithi'] : null;
        $targetPaksha = isset($anchorRule['paksha']) ? strtoupper((string) $anchorRule['paksha']) : null;
        $targetMasa = isset($anchorRule['masa']) ? strtoupper((string) $anchorRule['masa']) : null;
        $targetIndexInPaksha = isset($anchorRule['index_in_paksha']) ? (int) $anchorRule['index_in_paksha'] : null;

        $maxScanDays = isset($anchorRule['max_scan_days']) ? (int) $anchorRule['max_scan_days'] : 730;

        for ($dayOffset = 1; $dayOffset <= $maxScanDays; $dayOffset++) {
            $candidateDate = $current->addDays($dayOffset);

            // Computational astronomical horizon sanity check
            if ($candidateDate->year > 2100) {
                throw new OutOfCorpusException("Occurrence calculation for year {$candidateDate->year} exceeds maximum supported ephemeris horizon.");
            }

            $panchang = PanchangCalculator::calculate(
                $candidateDate->year,
                $candidateDate->month,
                $candidateDate->day,
                $latitude,
                $longitude,
                $tradition
            );

            if ($this->matchesAnchor($panchang, $targetTithi, $targetPaksha, $targetMasa, $targetIndexInPaksha, $tradition)) {
                return $panchang;
            }
        }

        throw new OutOfCorpusException("Unable to resolve anchor occurrence within {$maxScanDays} days.");
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

