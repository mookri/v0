<?php

namespace App\Modules\Customers\Domain\Panchang;

/**
 * Pure Panchang astronomical calculator.
 * Evaluates the 5 limbs of the Hindu calendar at sunrise (Udaya Tithi rule).
 */
final class PanchangCalculator
{
    /**
     * Calculate Panchang for a given civil calendar date and location coordinates.
     *
     * @param int $year Gregorian Year
     * @param int $month Gregorian Month [1, 12]
     * @param int $day Gregorian Day [1, 31]
     * @param float $latitude Default: Ujjain / Varanasi 23.1765° N
     * @param float $longitude Default: Ujjain / Varanasi 75.7885° E
     * @param string $tradition AMANTA | PURNIMANTA
     */
    public static function calculate(
        int $year,
        int $month,
        int $day,
        float $latitude = 23.1765,
        float $longitude = 75.7885,
        string $tradition = 'PURNIMANTA'
    ): PanchangOccurrence {
        // 1. Calculate civil sunrise in UTC
        $sunrise = SunriseUtcCalculator::calculate($year, $month, $day, $latitude, $longitude);
        $sunriseIso = sprintf(
            '%04d-%02d-%02dT%02d:%02d:%02dZ',
            $year,
            $month,
            $day,
            $sunrise['hour'],
            $sunrise['minute'],
            $sunrise['second']
        );

        // 2. Calculate Julian Day at Sunrise
        $jd = JulianDayCalculator::fromUtc(
            $year,
            $month,
            $day,
            $sunrise['hour'],
            $sunrise['minute'],
            $sunrise['second']
        );
        $t = JulianDayCalculator::centuriesSinceJ2000($jd);

        // 3. Calculate celestial longitudes
        $sunLon = SolarLongitude::calculate($t);
        $moonLon = LunarLongitude::calculate($t);

        // 4. Calculate angular elongation
        $elongation = Elongation::calculate($moonLon, $sunLon);

        // 5. Calculate Tithi and Nakshatra
        $tithi = TithiCalculator::calculate($elongation);
        $nakshatra = NakshatraCalculator::calculate($moonLon);

        // 6. Calculate Lunar Month (Amanta & Purnimanta)
        $masa = LunarMonthCalculator::calculate($sunLon, $tithi['number']);

        // 7. Calculate astronomical confidence
        $confidence = PanchangConfidence::forTithi($tithi['progress']);

        $dateStr = sprintf('%04d-%02d-%02d', $year, $month, $day);

        return new PanchangOccurrence(
            date: $dateStr,
            sunriseUtc: $sunriseIso,
            tithi: $tithi,
            nakshatra: $nakshatra,
            masa: $masa,
            tradition: strtoupper($tradition),
            confidence: $confidence
        );
    }
}
