<?php

namespace App\Domain\Jyotish\Rules;

use App\Domain\Jyotish\Support\Angle;

/**
 * Planetary Combustion (Asta) Engine (T-JY04-03).
 *
 * Implements classical combustion orbs (direct vs retrograde) per Surya Siddhanta & Brihat Samhita.
 * Pure domain model — zero HTTP or framework dependencies (ADR-JY-01, doc 01 §7).
 */
final readonly class CombustionCalculator
{
    /**
     * Classical combustion orbs in degrees [direct, retrograde].
     */
    public const COMBUSTION_ORBS = [
        'moon' => ['direct' => 12.0, 'retrograde' => 12.0],
        'mars' => ['direct' => 17.0, 'retrograde' => 17.0],
        'mercury' => ['direct' => 14.0, 'retrograde' => 12.0],
        'jupiter' => ['direct' => 11.0, 'retrograde' => 11.0],
        'venus' => ['direct' => 10.0, 'retrograde' => 8.0],
        'saturn' => ['direct' => 15.0, 'retrograde' => 15.0],
    ];

    /**
     * Deep combustion limit in degrees (Param Asta).
     */
    public const DEEP_COMBUSTION_DEGREES = 1.0;

    /**
     * Evaluate combustion status of a planet relative to the Sun.
     *
     * @param string $planet Planetary body ('mars', 'mercury', etc.)
     * @param float $planetLongitude Planetary longitude in degrees
     * @param float $sunLongitude Solar longitude in degrees
     * @param bool $isRetrograde Whether planet is currently in retrograde motion
     * @return array{
     *     planet: string,
     *     is_combust: bool,
     *     is_deeply_combust: bool,
     *     angular_separation: float,
     *     allowed_orb: float,
     *     combustion_ratio: float,
     *     status: string
     * }
     */
    public static function evaluate(
        string $planet,
        float $planetLongitude,
        float $sunLongitude,
        bool $isRetrograde = false
    ): array {
        $p = strtolower(trim($planet));

        // Sun, Rahu and Ketu are immune to combustion
        if (in_array($p, ['sun', 'rahu', 'ketu'], true) || ! isset(self::COMBUSTION_ORBS[$p])) {
            return [
                'planet' => $p,
                'is_combust' => false,
                'is_deeply_combust' => false,
                'angular_separation' => 0.0,
                'allowed_orb' => 0.0,
                'combustion_ratio' => 0.0,
                'status' => 'immune',
            ];
        }

        $sep = abs(Angle::normalize($planetLongitude) - Angle::normalize($sunLongitude));
        if ($sep > 180.0) {
            $sep = 360.0 - $sep;
        }

        $orbRules = self::COMBUSTION_ORBS[$p];
        $orb = $isRetrograde ? $orbRules['retrograde'] : $orbRules['direct'];

        $isCombust = ($sep <= $orb);
        $isDeep = ($sep <= self::DEEP_COMBUSTION_DEGREES);

        $ratio = $isCombust ? max(0.0, 1.0 - ($sep / $orb)) : 0.0;

        $status = match (true) {
            $isDeep => 'deeply_combust',
            $isCombust => 'combust',
            default => 'normal',
        };

        return [
            'planet' => $p,
            'is_combust' => $isCombust,
            'is_deeply_combust' => $isDeep,
            'angular_separation' => round($sep, 4),
            'allowed_orb' => $orb,
            'combustion_ratio' => round($ratio, 4),
            'status' => $status,
        ];
    }
}

