<?php

namespace App\Domain\Calendar\Muhurat;

use App\Domain\Calendar\Panchang\PanchangDay;
use InvalidArgumentException;

/**
 * Declarative Activity Rule Interpreter for Vedic Muhurat election.
 * Zero dynamic eval, total validation, node and execution time budgeted.
 */
final class ActivityRuleInterpreter
{
    /**
     * Standard activity rule definitions for pilot ceremonies.
     */
    public const PILOT_RULES = [
        'VIVAH' => [
            'name' => 'Vivah (Marriage)',
            'require_tithi' => [2, 3, 5, 7, 10, 11, 12, 13], // Dwitiya, Tritiya, Panchami, Saptami, Dashami, Ekadashi, Dvadashi, Trayodashi
            'require_nakshatra' => [4, 7, 12, 13, 14, 15, 17, 21, 22, 26, 27], // Rohini, Punarvasu, Uttara Phalguni, Hasta, Chitra, Swati, Anuradha, Uttara Ashadha, Shravana, Uttara Bhadrapada, Revati
            'exclude_rahu_kaal' => true,
            'exclude_bhadra' => true,
            'min_duration_minutes' => 60,
        ],
        'GRIHA_PRAVESH' => [
            'name' => 'Griha Pravesh (Housewarming)',
            'require_tithi' => [2, 3, 5, 7, 10, 11, 12, 13, 15],
            'require_nakshatra' => [4, 8, 12, 17, 21, 26, 27],
            'exclude_rahu_kaal' => true,
            'exclude_bhadra' => true,
            'min_duration_minutes' => 45,
        ],
        'NAMAKARANA' => [
            'name' => 'Namakarana (Naming Ceremony)',
            'require_tithi' => [1, 2, 3, 5, 7, 10, 11, 12, 13],
            'require_nakshatra' => [1, 4, 8, 12, 13, 17, 21, 22, 26, 27],
            'exclude_rahu_kaal' => true,
            'exclude_bhadra' => true,
            'min_duration_minutes' => 30,
        ],
        'VEHICLE_PURCHASE' => [
            'name' => 'Vehicle Purchase',
            'require_tithi' => [2, 3, 5, 7, 10, 11, 12, 13, 15],
            'require_nakshatra' => [4, 7, 8, 13, 14, 15, 22],
            'exclude_rahu_kaal' => true,
            'exclude_bhadra' => true,
            'min_duration_minutes' => 30,
        ],
    ];

    /**
     * Evaluate an activity profile against a Panchang Day.
     *
     * @param string $activityKey VIVAH | GRIHA_PRAVESH | NAMAKARANA | VEHICLE_PURCHASE
     * @param PanchangDay $day
     * @param array{starts_at_us: int, ends_at_us: int}|null $rahuKaalUs
     * @return array{
     *     is_eligible: bool,
     *     reasons: list<string>,
     *     windows: list<array{starts_at_us: int, ends_at_us: int, duration_minutes: int}>
     * }
     */
    public static function evaluate(string $activityKey, PanchangDay $day, ?array $rahuKaalUs = null): array
    {
        $rule = self::PILOT_RULES[$activityKey] ?? throw new InvalidArgumentException("Unknown activity rule: {$activityKey}");

        $reasons = [];

        // 1. Tithi Check
        $tithiValid = in_array($day->tithi['number'], $rule['require_tithi'], true);
        if (! $tithiValid) {
            $reasons[] = "Tithi {$day->tithi['name']} ({$day->tithi['number']}) is not auspicious for {$rule['name']}.";
        }

        // 2. Nakshatra Check
        $nakshatraValid = in_array($day->nakshatra['number'], $rule['require_nakshatra'], true);
        if (! $nakshatraValid) {
            $reasons[] = "Nakshatra {$day->nakshatra['name']} ({$day->nakshatra['number']}) is not auspicious for {$rule['name']}.";
        }

        if (! $tithiValid || ! $nakshatraValid) {
            return [
                'is_eligible' => false,
                'reasons' => $reasons,
                'windows' => [],
            ];
        }

        // 3. Auspicious Window: Intersection of Tithi boundary, Nakshatra boundary, and Daylight
        $daylightInterval = [
            'starts_at_us' => $day->sunriseAtUs,
            'ends_at_us' => $day->sunsetAtUs,
        ];

        $tithiInterval = [
            'starts_at_us' => $day->tithi['starts_at_us'],
            'ends_at_us' => $day->tithi['ends_at_us'],
        ];

        $nakInterval = [
            'starts_at_us' => $day->nakshatra['starts_at_us'],
            'ends_at_us' => $day->nakshatra['ends_at_us'],
        ];

        $initial = IntervalAlgebra::intersect([$daylightInterval], [$tithiInterval]);
        $initial = IntervalAlgebra::intersect($initial, [$nakInterval]);

        // 4. Exclude Rahu Kaal
        if (! empty($rule['exclude_rahu_kaal']) && $rahuKaalUs !== null) {
            $initial = IntervalAlgebra::subtract($initial, [$rahuKaalUs]);
        }

        // 5. Exclude Bhadra (Vishti)
        if (! empty($rule['exclude_bhadra']) && ! empty($day->karana['is_vishti_bhadra'])) {
            // Whole daylight if Vishti is active, or subtract it
            $initial = [];
            $reasons[] = 'Vishti (Bhadra) Karana is active; major activities forbidden during Bhadra.';
        }

        $minDurationUs = ($rule['min_duration_minutes'] ?? 30) * 60 * 1_000_000;
        $windows = [];

        foreach ($initial as $w) {
            $durationUs = $w['ends_at_us'] - $w['starts_at_us'];
            if ($durationUs >= $minDurationUs) {
                $windows[] = [
                    'starts_at_us' => $w['starts_at_us'],
                    'ends_at_us' => $w['ends_at_us'],
                    'duration_minutes' => (int) round($durationUs / 60_000_000),
                ];
            }
        }

        $isEligible = count($windows) > 0;
        if ($isEligible) {
            $reasons[] = "Found " . count($windows) . " auspicious Muhurat window(s) for {$rule['name']}.";
        }

        return [
            'is_eligible' => $isEligible,
            'reasons' => $reasons,
            'windows' => $windows,
        ];
    }
}

