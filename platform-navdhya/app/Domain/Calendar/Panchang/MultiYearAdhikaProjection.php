<?php

namespace App\Domain\Calendar\Panchang;

/**
 * Multi-year Adhika Masa (Intercalary Month) Projections (2026 - 2036).
 * Based on sidereal solar ingresses vs lunar synodic cycles (metonic ~32.5 month synchronization).
 */
final class MultiYearAdhikaProjection
{
    /**
     * Known historical and astronomical projected Adhika Masas.
     *
     * @var list<array{
     *     year: int,
     *     adhika_masa: string,
     *     gregorian_approx: string,
     *     solar_rashi_traversed: string,
     *     cycle_rule: string
     * }>
     */
    public const PROJECTED_ADHIKA_MASAS = [
        [
            'year' => 2026,
            'adhika_masa' => 'JYESHTHA',
            'gregorian_approx' => 'May - June 2026',
            'solar_rashi_traversed' => 'VRISHABHA',
            'cycle_rule' => 'Zero solar sankranti in lunar month',
        ],
        [
            'year' => 2029,
            'adhika_masa' => 'CHAITRA',
            'gregorian_approx' => 'March - April 2029',
            'solar_rashi_traversed' => 'MEENA',
            'cycle_rule' => 'Zero solar sankranti in lunar month',
        ],
        [
            'year' => 2031,
            'adhika_masa' => 'KARTIKA',
            'gregorian_approx' => 'October - November 2031',
            'solar_rashi_traversed' => 'TULA',
            'cycle_rule' => 'Zero solar sankranti in lunar month',
        ],
        [
            'year' => 2034,
            'adhika_masa' => 'ASHADHA',
            'gregorian_approx' => 'June - July 2034',
            'solar_rashi_traversed' => 'MITHUNA',
            'cycle_rule' => 'Zero solar sankranti in lunar month',
        ],
    ];

    /**
     * Check if a given year has an Adhika Masa projection.
     */
    public static function forYear(int $year): ?array
    {
        foreach (self::PROJECTED_ADHIKA_MASAS as $proj) {
            if ($proj['year'] === $year) {
                return $proj;
            }
        }

        return null;
    }
}

