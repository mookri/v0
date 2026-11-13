<?php

declare(strict_types=1);

namespace App\Domain\Jyotish\Kp;

final readonly class SubLordDivider
{
    /**
     * Total span of one Nakshatra = 13° 20' = 800 arcminutes.
     * Vimshottari total cycle = 120 years.
     * Each sub-lord's width in arcminutes = 800' * (Lord_Years / 120.0).
     * BPHS / Krishnamurti Paddhati exact formula.
     */
    public const NAKSHATRA_SPAN_MINUTES = 800.0;
    public const TOTAL_YEARS = 120.0;

    public const LORDS_ORDER = ['ketu', 'venus', 'sun', 'moon', 'mars', 'rahu', 'jupiter', 'saturn', 'mercury'];
    public const LORD_YEARS = [
        'ketu' => 7.0,
        'venus' => 20.0,
        'sun' => 6.0,
        'moon' => 10.0,
        'mars' => 7.0,
        'rahu' => 18.0,
        'jupiter' => 16.0,
        'saturn' => 19.0,
        'mercury' => 17.0,
    ];

    /**
     * Compute 9 sub-lord divisions within a given Nakshatra.
     * Starts with the nakshatra lord and continues in standard Vimshottari order.
     *
     * @param int $nakshatraIndex 0..26
     * @return list<array{sub_lord: string, start_minutes: float, end_minutes: float, width_minutes: float}>
     */
    public static function getSubDivisions(int $nakshatraIndex): array
    {
        $starLordIndex = $nakshatraIndex % 9;
        $divisions = [];
        $currentMinute = 0.0;

        for ($sub = 0; $sub < 9; $sub++) {
            $lordIdx = ($starLordIndex + $sub) % 9;
            $lord = self::LORDS_ORDER[$lordIdx];
            $years = self::LORD_YEARS[$lord];

            // Width in arcminutes: 800 * (years / 120)
            $width = self::NAKSHATRA_SPAN_MINUTES * ($years / self::TOTAL_YEARS);
            $endMinute = $currentMinute + $width;

            $divisions[] = [
                'sub_lord' => $lord,
                'start_minutes' => round($currentMinute, 6),
                'end_minutes' => round($endMinute, 6),
                'width_minutes' => round($width, 6),
            ];

            $currentMinute = $endMinute;
        }

        return $divisions;
    }

    /**
     * Identify the Sub-Lord for any specific sidereal longitude.
     *
     * @param float $longitude [0, 360)
     * @return array{nakshatra: int, star_lord: string, sub_lord: string, degree_in_nakshatra: float}
     */
    public static function findSubLord(float $longitude): array
    {
        $normLong = fmod(fmod($longitude, 360.0) + 360.0, 360.0);
        $nakshatraSpanDeg = 360.0 / 27.0; // 13.333333333333334°
        $nakshatraIndex = (int) floor($normLong / $nakshatraSpanDeg);
        if ($nakshatraIndex > 26) $nakshatraIndex = 26;

        $starLord = self::LORDS_ORDER[$nakshatraIndex % 9];
        $degInNakshatra = $normLong - ($nakshatraIndex * $nakshatraSpanDeg);
        $minutesInNakshatra = $degInNakshatra * 60.0;

        $divisions = self::getSubDivisions($nakshatraIndex);
        $subLord = $divisions[8]['sub_lord']; // fallback to last

        foreach ($divisions as $div) {
            if ($minutesInNakshatra >= $div['start_minutes'] && $minutesInNakshatra < $div['end_minutes']) {
                $subLord = $div['sub_lord'];
                break;
            }
        }

        return [
            'nakshatra' => $nakshatraIndex + 1,
            'star_lord' => $starLord,
            'sub_lord' => $subLord,
            'degree_in_nakshatra' => round($degInNakshatra, 4),
        ];
    }
}

