<?php

namespace App\Domain\Calendar\Panchang;

use App\Domain\Calendar\Astronomy\Ayanamsa;
use App\Domain\Calendar\Astronomy\JulianDayCalculator;
use App\Domain\Calendar\Astronomy\RootSolver;
use App\Domain\Calendar\Astronomy\SolarLongitude;
use Carbon\CarbonImmutable;

/**
 * Solar Ingress (Sankranti) and Sidereal Rashi calculation engine.
 */
final class SankrantiCalculator
{
    public const RASHIS = [
        1 => 'MESHA',         // Aries
        2 => 'VRISHABHA',     // Taurus
        3 => 'MITHUNA',       // Gemini
        4 => 'KARKA',         // Cancer
        5 => 'SIMHA',         // Leo
        6 => 'KANYA',         // Virgo
        7 => 'TULA',          // Libra
        8 => 'VRISHCHIKA',    // Scorpio
        9 => 'DHANU',         // Sagittarius
        10 => 'MAKARA',       // Capricorn
        11 => 'KUMBHA',       // Aquarius
        12 => 'MEENA',        // Pisces
    ];

    /**
     * @param float $siderealSunDegrees Sidereal Sun longitude [0, 360)
     * @return array{number: int, name: string, degree_in_rashi: float, progress: float}
     */
    public static function calculateRashi(float $siderealSunDegrees): array
    {
        $normalized = SolarLongitude::normalizeDegrees($siderealSunDegrees);
        $rashi = (int) floor($normalized / 30.0) + 1;
        if ($rashi > 12) {
            $rashi = 12;
        }

        $degInRashi = fmod($normalized, 30.0);
        $progress = $degInRashi / 30.0;

        return [
            'number' => $rashi,
            'name' => self::RASHIS[$rashi],
            'degree_in_rashi' => $degInRashi,
            'progress' => $progress,
        ];
    }

    /**
     * Compute sidereal Sun longitude at an arbitrary epoch second.
     */
    public static function siderealSunAtTimestamp(float $epochSeconds): float
    {
        $dt = CarbonImmutable::createFromTimestampUTC((int) $epochSeconds);
        $jd = JulianDayCalculator::fromUtc($dt->year, $dt->month, $dt->day, $dt->hour, $dt->minute, $dt->second);
        $t = JulianDayCalculator::centuriesSinceJ2000($jd);
        $tropicalSun = SolarLongitude::calculate($t);

        return Ayanamsa::toSidereal($tropicalSun, $t);
    }

    /**
     * Find the exact UTC microsecond instant of the next Sankranti after $fromInstant.
     *
     * @return array{
     *     rashi_entered: int,
     *     rashi_name: string,
     *     instant_utc: CarbonImmutable,
     *     starts_at_us: int
     * }
     */
    public static function findNextSankranti(CarbonImmutable $fromInstant): array
    {
        $currentSidereal = self::siderealSunAtTimestamp((float) $fromInstant->getTimestamp());
        $currentRashi = (int) floor($currentSidereal / 30.0) + 1;
        $nextRashi = ($currentRashi % 12) + 1;
        $targetAngle = fmod(($nextRashi - 1) * 30.0, 360.0);

        // Sun moves ~1° per day. Search window is 1 to 35 days ahead.
        $a = (float) $fromInstant->getTimestamp();
        $b = $a + (35.0 * 86400.0);

        $rootTs = RootSolver::findAngleCrossing(
            fn (float $ts) => self::siderealSunAtTimestamp($ts),
            $a,
            $b,
            $targetAngle,
            tolerance: 1.0 // Within 1 second
        );

        $intSec = (int) floor($rootTs);
        $micro = (int) round(($rootTs - $intSec) * 1_000_000);
        $instant = CarbonImmutable::createFromTimestampUTC($intSec)->addMicroseconds($micro);
        $startsAtUs = (int) ($instant->getTimestamp() * 1_000_000 + $instant->micro);

        return [
            'rashi_entered' => $nextRashi,
            'rashi_name' => self::RASHIS[$nextRashi],
            'instant_utc' => $instant,
            'starts_at_us' => $startsAtUs,
        ];
    }
}

