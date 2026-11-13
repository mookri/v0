<?php

namespace App\Domain\Calendar\Panchang;

use Carbon\CarbonImmutable;

/**
 * Vedic Vara (Weekday) calculator with dual anchoring (Civil midnight vs Vedic sunrise).
 * Closes P-12.
 */
final class VaraCalculator
{
    public const VARAS = [
        0 => 'RAVIVARA',     // Sunday (Surya)
        1 => 'SOMAVARA',     // Monday (Chandra)
        2 => 'MANGALAVARA',  // Tuesday (Mangala)
        3 => 'BUDHAVARA',    // Wednesday (Budha)
        4 => 'GURUVARA',     // Thursday (Brihaspati)
        5 => 'SHUKRAVARA',   // Friday (Shukra)
        6 => 'SHANIVARA',    // Saturday (Shani)
    ];

    /**
     * Compute civil and sunrise-anchored Vedic vara.
     *
     * @param CarbonImmutable $instant Local instant with timezone
     * @param CarbonImmutable $sunriseLocal Local sunrise instant for that civil day
     * @return array{
     *     civil_number: int,
     *     civil_name: string,
     *     vedic_number: int,
     *     vedic_name: string,
     *     is_pre_sunrise: bool
     * }
     */
    public static function calculate(CarbonImmutable $instant, CarbonImmutable $sunriseLocal): array
    {
        $civilDayOfWeek = $instant->dayOfWeek; // 0 (Sunday) to 6 (Saturday)
        $civilName = self::VARAS[$civilDayOfWeek];

        $isPreSunrise = $instant->lessThan($sunriseLocal);

        if ($isPreSunrise) {
            // Vedic day belongs to previous solar cycle
            $vedicDayOfWeek = ($civilDayOfWeek + 6) % 7;
        } else {
            $vedicDayOfWeek = $civilDayOfWeek;
        }

        $vedicName = self::VARAS[$vedicDayOfWeek];

        return [
            'civil_number' => $civilDayOfWeek,
            'civil_name' => $civilName,
            'vedic_number' => $vedicDayOfWeek,
            'vedic_name' => $vedicName,
            'is_pre_sunrise' => $isPreSunrise,
        ];
    }
}

