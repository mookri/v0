<?php

namespace App\Domain\Calendar\Muhurat;

use Carbon\CarbonImmutable;

/**
 * Abhijit Muhurat calculator.
 * The 8th of the 15 daylight Muhurtas, centered dynamically on local solar noon.
 * Strictly zero static 11:45–12:30 hardcoding.
 */
final class AbhijitMuhurat
{
    /**
     * @param CarbonImmutable $sunrise Local sunrise
     * @param CarbonImmutable $sunset Local sunset
     * @param int $weekday Weekday (0 = Sunday, ..., 3 = Wednesday, ..., 6 = Saturday)
     * @return array{
     *     name: string,
     *     starts_at: CarbonImmutable,
     *     ends_at: CarbonImmutable,
     *     starts_at_us: int,
     *     ends_at_us: int,
     *     is_dosha_afflicted: bool,
     *     dosha_reason: ?string
     * }
     */
    public static function calculate(CarbonImmutable $sunrise, CarbonImmutable $sunset, int $weekday): array
    {
        $muhurtas = DayDivision::daylightMuhurtas($sunrise, $sunset);
        // Abhijit is the 8th Muhurta (index 7)
        $slot = $muhurtas[7];

        $isWednesday = ($weekday % 7) === 3;

        return [
            'name' => 'ABHIJIT_MUHURAT',
            'starts_at' => $slot['starts_at'],
            'ends_at' => $slot['ends_at'],
            'starts_at_us' => $slot['starts_at_us'],
            'ends_at_us' => $slot['ends_at_us'],
            'is_dosha_afflicted' => $isWednesday,
            'dosha_reason' => $isWednesday ? 'Abhijit on Wednesday (Budhavara) causes Rahu/Budha collision dosha' : null,
        ];
    }
}

