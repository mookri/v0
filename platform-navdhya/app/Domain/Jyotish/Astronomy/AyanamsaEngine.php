<?php

namespace App\Domain\Jyotish\Astronomy;

use InvalidArgumentException;

/**
 * Multi-Model High-Precision Ayanamsa Engine (T-JY02-03).
 *
 * Implements canonical Ayanamsa systems:
 *  - LAHIRI (Chitrapaksha / Indian Astronomical Ephemeris)
 *  - RAMAN (B.V. Raman)
 *  - KRISHNAMURTI (KP)
 *  - FAGAN_BRADLEY (Western Sidereal)
 *  - YUKTESHWAR (Sri Yukteshwar)
 *  - SURYA_SIDDHANTA
 *  - TRUE_CHITRA
 *
 * Pure domain model — zero HTTP or framework dependencies (ADR-JY-01, doc 01 §7).
 */
final readonly class AyanamsaEngine
{
    public const SYSTEM_LAHIRI = 'LAHIRI';
    public const SYSTEM_RAMAN = 'RAMAN';
    public const SYSTEM_KRISHNAMURTI = 'KRISHNAMURTI';
    public const SYSTEM_KP = 'KRISHNAMURTI';
    public const SYSTEM_FAGAN_BRADLEY = 'FAGAN_BRADLEY';
    public const SYSTEM_YUKTESHWAR = 'YUKTESHWAR';
    public const SYSTEM_SURYA_SIDDHANTA = 'SURYA_SIDDHANTA';
    public const SYSTEM_TRUE_CHITRA = 'TRUE_CHITRA';

    public const CANONICAL_SYSTEMS = [
        self::SYSTEM_LAHIRI,
        self::SYSTEM_RAMAN,
        self::SYSTEM_KRISHNAMURTI,
        self::SYSTEM_FAGAN_BRADLEY,
        self::SYSTEM_YUKTESHWAR,
        self::SYSTEM_SURYA_SIDDHANTA,
        self::SYSTEM_TRUE_CHITRA,
    ];

    /**
     * Compute Ayanamsa value and metadata at centuries T from J2000.0.
     *
     * @param float $t Julian centuries since J2000.0
     * @param string $system Canonical ayanamsa code
     * @param bool $isTrue If true, applies nutation in longitude (apparent ayanamsa)
     * @return array{
     *     system: string,
     *     ayanamsa_degrees: float,
     *     ayanamsa_dms: string,
     *     nutation_degrees: float,
     *     epoch_reference: string,
     *     rate_per_century: float
     * }
     */
    public static function calculate(float $t, string $system = self::SYSTEM_LAHIRI, bool $isTrue = false): array
    {
        $code = strtoupper(trim($system));
        if (! in_array($code, self::CANONICAL_SYSTEMS, true)) {
            throw new InvalidArgumentException("Unsupported ayanamsa system: {$system}");
        }

        $nutation = self::calculateNutationInLongitude($t);

        [$meanVal, $rate, $epoch] = match ($code) {
            self::SYSTEM_LAHIRI => [
                23.853056 + (1.396888 * $t) + (0.0003086 * $t * $t),
                1.396888,
                'J2000.0 (Chitrapaksha Lahiri, Indian Astronomical Ephemeris)'
            ],
            self::SYSTEM_RAMAN => [
                22.404167 + (1.396944 * $t),
                1.396944,
                '397 CE zero year (B.V. Raman)'
            ],
            self::SYSTEM_KRISHNAMURTI => [
                23.755833 + (1.396888 * $t),
                1.396888,
                'KP Krishnamurti Paddhati'
            ],
            self::SYSTEM_FAGAN_BRADLEY => [
                24.739444 + (1.396888 * $t),
                1.396888,
                'Spica at 29 Vir 06 (Western Sidereal)'
            ],
            self::SYSTEM_YUKTESHWAR => [
                22.092778 + (1.396888 * $t),
                1.396888,
                'Sri Yukteshwar (The Holy Science)'
            ],
            self::SYSTEM_SURYA_SIDDHANTA => [
                22.840000 + (1.396888 * $t),
                1.396888,
                'Surya Siddhanta traditional motion'
            ],
            self::SYSTEM_TRUE_CHITRA => [
                23.842700 + (1.396888 * $t),
                1.396888,
                'Alpha Virginis / Spica at exactly 180°'
            ],
        };

        $finalValue = $isTrue ? ($meanVal - $nutation) : $meanVal;

        return [
            'system' => $code,
            'ayanamsa_degrees' => $finalValue,
            'ayanamsa_dms' => self::formatDms($finalValue),
            'nutation_degrees' => $nutation,
            'epoch_reference' => $epoch,
            'rate_per_century' => $rate,
        ];
    }

    /**
     * Compute nutation in longitude (IAU 1980 / Meeus Chapter 22).
     */
    public static function calculateNutationInLongitude(float $t): float
    {
        $omega = deg2rad(125.04452 - (1934.136261 * $t));
        $l0 = deg2rad(280.4665 + (36000.7698 * $t));

        // Nutation in degrees
        return (-0.004778 * sin($omega)) - (0.000367 * sin(2.0 * $l0));
    }

    public static function formatDms(float $degrees): string
    {
        $sign = $degrees < 0 ? '-' : '';
        $abs = abs($degrees);
        $d = (int) floor($abs);
        $remainder = ($abs - $d) * 60.0;
        $m = (int) floor($remainder);
        $s = ($remainder - $m) * 60.0;

        return sprintf('%s%d° %02d\' %04.1f"', $sign, $d, $m, $s);
    }
}

