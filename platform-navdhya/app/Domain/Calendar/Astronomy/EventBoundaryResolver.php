<?php

namespace App\Domain\Calendar\Astronomy;

use App\Domain\Calendar\Panchang\NakshatraCalculator;
use App\Domain\Calendar\Panchang\TithiCalculator;
use Carbon\CarbonImmutable;

/**
 * Resolves continuous microsecond boundaries for astronomical events (Tithi, Nakshatra, Sankranti).
 * Closes P-10 (missing continuous boundaries) and P-11 (approximate discrete steps).
 */
final class EventBoundaryResolver
{
    /**
     * Compute angular elongation at an arbitrary UNIX epoch second.
     */
    public static function elongationAtTimestamp(float $epochSeconds): float
    {
        $dt = CarbonImmutable::createFromTimestampUTC((int) $epochSeconds);
        $microFrac = $epochSeconds - floor($epochSeconds);
        $jd = JulianDayCalculator::fromUtc(
            $dt->year,
            $dt->month,
            $dt->day,
            $dt->hour,
            $dt->minute,
            $dt->second
        ) + ($microFrac / 86400.0);

        $t = JulianDayCalculator::centuriesSinceJ2000($jd);
        $sun = SolarLongitude::calculate($t);
        $moon = LunarLongitude::calculate($t);

        return Elongation::calculate($moon, $sun);
    }

    /**
     * Compute sidereal moon longitude at an arbitrary UNIX epoch second.
     */
    public static function siderealMoonAtTimestamp(float $epochSeconds): float
    {
        $dt = CarbonImmutable::createFromTimestampUTC((int) $epochSeconds);
        $jd = JulianDayCalculator::fromUtc(
            $dt->year,
            $dt->month,
            $dt->day,
            $dt->hour,
            $dt->minute,
            $dt->second
        );
        $t = JulianDayCalculator::centuriesSinceJ2000($jd);
        $moonTropical = LunarLongitude::calculate($t);

        return Ayanamsa::toSidereal($moonTropical, $t);
    }

    /**
     * Resolve the precise UTC instant when Tithi number $tithi (1-30) ends.
     * Egress angle = ($tithi * 12.0) % 360.
     */
    public static function resolveTithiEnd(CarbonImmutable $nearInstant, int $tithi): CarbonImmutable
    {
        $targetAngle = fmod($tithi * 12.0, 360.0);
        $centerTs = (float) $nearInstant->getTimestamp();

        // Search window: +/- 36 hours (~54 degrees of lunar motion)
        $a = $centerTs - (36.0 * 3600.0);
        $b = $centerTs + (36.0 * 3600.0);

        $rootTs = RootSolver::findAngleCrossing(
            fn (float $ts) => self::elongationAtTimestamp($ts),
            $a,
            $b,
            $targetAngle,
            tolerance: 0.5 // Within 0.5 seconds
        );

        $intSec = (int) floor($rootTs);
        $micro = (int) round(($rootTs - $intSec) * 1_000_000);

        return CarbonImmutable::createFromTimestampUTC($intSec)->addMicroseconds($micro);
    }

    /**
     * Resolve the full continuous microsecond interval [starts_at_us, ends_at_us] for the active Tithi at a given instant.
     *
     * @return array{tithi: int, name: string, starts_at_us: int, ends_at_us: int}
     */
    public static function resolveTithiInterval(CarbonImmutable $instant): array
    {
        $elongation = self::elongationAtTimestamp((float) $instant->getTimestamp());
        $tithiInfo = TithiCalculator::calculate($elongation);
        $currentTithi = $tithiInfo['number'];

        // Start boundary: when ($currentTithi - 1) * 12° was crossed
        $prevTithi = $currentTithi === 1 ? 30 : $currentTithi - 1;
        $startInstant = self::resolveTithiEnd($instant->subHours(12), $prevTithi);

        // End boundary: when currentTithi * 12° is crossed
        $endInstant = self::resolveTithiEnd($instant->addHours(12), $currentTithi);

        $startsAtUs = (int) ($startInstant->getTimestamp() * 1_000_000 + $startInstant->micro);
        $endsAtUs = (int) ($endInstant->getTimestamp() * 1_000_000 + $endInstant->micro);

        return [
            'tithi' => $currentTithi,
            'name' => $tithiInfo['name'],
            'starts_at_us' => $startsAtUs,
            'ends_at_us' => $endsAtUs,
        ];
    }

    /**
     * Resolve the precise UTC instant when Nakshatra number $nakshatra (1-27) ends.
     */
    public static function resolveNakshatraEnd(CarbonImmutable $nearInstant, int $nakshatra): CarbonImmutable
    {
        $span = 360.0 / 27.0;
        $targetAngle = fmod($nakshatra * $span, 360.0);
        $centerTs = (float) $nearInstant->getTimestamp();

        $a = $centerTs - (36.0 * 3600.0);
        $b = $centerTs + (36.0 * 3600.0);

        $rootTs = RootSolver::findAngleCrossing(
            fn (float $ts) => self::siderealMoonAtTimestamp($ts),
            $a,
            $b,
            $targetAngle,
            tolerance: 0.5
        );

        $intSec = (int) floor($rootTs);
        $micro = (int) round(($rootTs - $intSec) * 1_000_000);

        return CarbonImmutable::createFromTimestampUTC($intSec)->addMicroseconds($micro);
    }

    /**
     * Resolve the full continuous microsecond interval for the active Nakshatra.
     *
     * @return array{nakshatra: int, name: string, starts_at_us: int, ends_at_us: int}
     */
    public static function resolveNakshatraInterval(CarbonImmutable $instant): array
    {
        $moonSidereal = self::siderealMoonAtTimestamp((float) $instant->getTimestamp());
        $nakInfo = NakshatraCalculator::calculate($moonSidereal);
        $currentNak = $nakInfo['number'];

        $prevNak = $currentNak === 1 ? 27 : $currentNak - 1;
        $startInstant = self::resolveNakshatraEnd($instant->subHours(12), $prevNak);
        $endInstant = self::resolveNakshatraEnd($instant->addHours(12), $currentNak);

        $startsAtUs = (int) ($startInstant->getTimestamp() * 1_000_000 + $startInstant->micro);
        $endsAtUs = (int) ($endInstant->getTimestamp() * 1_000_000 + $endInstant->micro);

        return [
            'nakshatra' => $currentNak,
            'name' => $nakInfo['name'],
            'pada' => $nakInfo['pada'],
            'starts_at_us' => $startsAtUs,
            'ends_at_us' => $endsAtUs,
        ];
    }
}

