<?php

namespace App\Domain\Calendar\Panchang;

use App\Domain\Calendar\Astronomy\SolarLongitude;

/**
 * Vedic Nitya Yoga calculation engine (pure math).
 *
 * Calculated from the SUM of sidereal Sun and Moon longitudes:
 * Yoga = (λ_sun_sidereal + λ_moon_sidereal) % 360° / (13°20')
 *
 * Distinct from Tithi (which is Moon MINUS Sun). Closes P-07.
 */
final class YogaCalculator
{
    public const YOGAS = [
        1 => 'VISHKUMBHA',
        2 => 'PRITI',
        3 => 'AYUSHMAN',
        4 => 'SAUBHAGYA',
        5 => 'SHOBHANA',
        6 => 'ATIGANDA',
        7 => 'SUKARMA',
        8 => 'DHRITI',
        9 => 'SHULA',
        10 => 'GANDA',
        11 => 'VRIDDHI',
        12 => 'DHRUVA',
        13 => 'VYAGHATA',
        14 => 'HARSHANA',
        15 => 'VAJRA',
        16 => 'SIDDHI',
        17 => 'VYATIPATA',
        18 => 'VARIYAN',
        19 => 'PARIGHA',
        20 => 'SHIVA',
        21 => 'SIDDHA',
        22 => 'SADHYA',
        23 => 'SHUBHA',
        24 => 'SHUKLA',
        25 => 'BRAHMA',
        26 => 'INDRA',
        27 => 'VAIDHRITI',
    ];

    /**
     * @param float $siderealSunDegrees Sidereal Sun longitude [0, 360)
     * @param float $siderealMoonDegrees Sidereal Moon longitude [0, 360)
     * @return array{number: int, name: string, progress: float}
     */
    public static function calculate(float $siderealSunDegrees, float $siderealMoonDegrees): array
    {
        $sum = SolarLongitude::normalizeDegrees($siderealSunDegrees + $siderealMoonDegrees);
        $span = 360.0 / 27.0; // 13.333333333333334° (13°20')

        $index = (int) floor($sum / $span) + 1;
        if ($index > 27) {
            $index = 27;
        }

        $degIntoYoga = fmod($sum, $span);
        $progress = $degIntoYoga / $span;

        return [
            'number' => $index,
            'name' => self::YOGAS[$index],
            'progress' => $progress,
        ];
    }
}

