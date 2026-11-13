<?php

namespace App\Domain\Jyotish\Astronomy;

/**
 * High-Precision Planetary Motion Classifier (T-JY04-02).
 *
 * Classifies:
 * - Direct (Marga): positive angular rate
 * - Retrograde (Vakri): negative angular rate
 * - Stationary (Sthira): velocity near zero (turning point)
 *
 * Pure domain model — zero HTTP or framework dependencies (ADR-JY-01, doc 01 §7).
 */
final readonly class PlanetaryMotionClassifier
{
    /**
     * Daily motion threshold in degrees/day under which a planet is stationary.
     */
    public const STATIONARY_THRESHOLD_DEG_PER_DAY = 0.008;

    /**
     * Motion classification constants.
     */
    public const MOTION_DIRECT = 'direct';
    public const MOTION_RETROGRADE = 'retrograde';
    public const MOTION_STATIONARY_RETROGRADE = 'stationary_retrograde'; // Station turning retrograde
    public const MOTION_STATIONARY_DIRECT = 'stationary_direct';         // Station turning direct

    /**
     * Classify planetary motion based on instantaneous velocity and acceleration.
     *
     * @param string $planet Planetary body name ('mars', 'mercury', etc.)
     * @param float $speedLongitude Daily rate of change of ecliptic longitude in degrees/day
     * @param float|null $acceleration Daily rate of change of velocity (d²λ/dt²)
     * @return array{
     *     planet: string,
     *     motion: string,
     *     speed_deg_per_day: float,
     *     is_retrograde: bool,
     *     is_stationary: bool,
     *     sanskrit_name: string
     * }
     */
    public static function classify(string $planet, float $speedLongitude, ?float $acceleration = null): array
    {
        $p = strtolower(trim($planet));

        // Sun and Moon never turn retrograde in geocentric astronomy
        if (in_array($p, ['sun', 'moon'], true)) {
            return [
                'planet' => $p,
                'motion' => self::MOTION_DIRECT,
                'speed_deg_per_day' => $speedLongitude,
                'is_retrograde' => false,
                'is_stationary' => false,
                'sanskrit_name' => 'Marga',
            ];
        }

        $absSpeed = abs($speedLongitude);
        $isStationary = $absSpeed <= self::STATIONARY_THRESHOLD_DEG_PER_DAY;

        if ($isStationary) {
            $motion = ($acceleration !== null && $acceleration < 0.0)
                ? self::MOTION_STATIONARY_RETROGRADE
                : self::MOTION_STATIONARY_DIRECT;
            $sanskrit = 'Sthira (Stationary)';
            $isVakri = ($speedLongitude < 0.0);
        } elseif ($speedLongitude < 0.0) {
            $motion = self::MOTION_RETROGRADE;
            $sanskrit = 'Vakri (Retrograde)';
            $isVakri = true;
        } else {
            $motion = self::MOTION_DIRECT;
            $sanskrit = 'Marga (Direct)';
            $isVakri = false;
        }

        return [
            'planet' => $p,
            'motion' => $motion,
            'speed_deg_per_day' => $speedLongitude,
            'is_retrograde' => $isVakri,
            'is_stationary' => $isStationary,
            'sanskrit_name' => $sanskrit,
        ];
    }
}

