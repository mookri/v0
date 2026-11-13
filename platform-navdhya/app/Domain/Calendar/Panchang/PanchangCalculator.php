<?php

namespace App\Domain\Calendar\Panchang;

use App\Domain\Calendar\Astronomy\Ayanamsa;
use App\Domain\Calendar\Astronomy\Elongation;
use App\Domain\Calendar\Astronomy\JulianDayCalculator;
use App\Domain\Calendar\Astronomy\LunarLongitude;
use App\Domain\Calendar\Astronomy\SolarLongitude;
use App\Domain\Calendar\Astronomy\SunriseUtcCalculator;
use App\Domain\Calendar\Exceptions\UnsupportedHorizonException;
use Carbon\CarbonImmutable;

/**
 * Pure Panchang astronomical calculator.
 * Evaluates the limbs of the Hindu calendar at sunrise (Udaya Tithi rule).
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
     * @throws UnsupportedHorizonException If horizon is polar night, midnight sun, or latitude is unsupported (P-05)
     */
    public static function calculate(
        int $year,
        int $month,
        int $day,
        float $latitude = 23.1765,
        float $longitude = 75.7885,
        string $tradition = 'PURNIMANTA'
    ): PanchangOccurrence {
        $dateStr = sprintf('%04d-%02d-%02d', $year, $month, $day);

        // 1. Calculate civil sunrise in UTC with polar refusal (P-05)
        $sunrise = SunriseUtcCalculator::calculate($year, $month, $day, $latitude, $longitude);
        if (! $sunrise->isSupported() || $sunrise->instant_utc === null) {
            throw new UnsupportedHorizonException(
                status: $sunrise->status,
                latitude: $latitude,
                longitude: $longitude,
                date: $dateStr
            );
        }

        $sunriseIso = $sunrise->instant_utc->toIso8601ZuluString();

        // 2. Calculate Julian Day at Sunrise using exact instant
        $jd = JulianDayCalculator::fromUtc(
            (int) $sunrise->instant_utc->format('Y'),
            (int) $sunrise->instant_utc->format('m'),
            (int) $sunrise->instant_utc->format('d'),
            (int) $sunrise->hour,
            (int) $sunrise->minute,
            (int) $sunrise->second
        );
        $t = JulianDayCalculator::centuriesSinceJ2000($jd);

        // 3. Calculate celestial longitudes (tropical)
        $sunLonTropical = SolarLongitude::calculate($t);
        $moonLonTropical = LunarLongitude::calculate($t);

        // 4. Calculate angular elongation (Ayanamsa-invariant, P-04)
        $elongation = Elongation::calculate($moonLonTropical, $sunLonTropical);

        // 5. Convert to sidereal longitudes via Chitrapaksha/Lahiri Ayanamsa (P-01, P-02)
        $moonLonSidereal = Ayanamsa::toSidereal($moonLonTropical, $t);
        $sunLonSidereal = Ayanamsa::toSidereal($sunLonTropical, $t);

        // 6. Calculate all five limbs
        $tithi = TithiCalculator::calculate($elongation);
        $nakshatra = NakshatraCalculator::calculate($moonLonSidereal);
        $yoga = YogaCalculator::calculate($sunLonSidereal, $moonLonSidereal);
        $karana = KaranaCalculator::calculate($elongation);
        $sunriseDt = CarbonImmutable::parse($sunriseIso);
        $vara = VaraCalculator::calculate($sunriseDt, $sunriseDt);

        // 7. Calculate Lunar Month (Amanta & Purnimanta) from sidereal Sun
        $masa = LunarMonthCalculator::calculate($sunLonSidereal, $tithi['number']);

        // 8. Calculate astronomical confidence
        $confidence = PanchangConfidence::forTithi($tithi['progress']);

        return new PanchangOccurrence(
            date: $dateStr,
            sunriseUtc: $sunriseIso,
            tithi: $tithi,
            nakshatra: $nakshatra,
            masa: $masa,
            yoga: $yoga,
            karana: $karana,
            vara: $vara,
            tradition: strtoupper($tradition),
            confidence: $confidence
        );
    }
}

