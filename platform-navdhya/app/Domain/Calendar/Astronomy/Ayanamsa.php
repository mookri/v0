<?php

namespace App\Domain\Calendar\Astronomy;

/**
 * Ayanamsa registry & sidereal calculation engine.
 *
 * Implements the Chitrapaksha / Lahiri formulation:
 * A(t) = 23.858072° + 0.0139697° · T + 0.0003086° · T²
 * where T is Julian centuries from J2000.0.
 */
final class Ayanamsa
{
    public const MODEL_LAHIRI = 'CHITRAPAKSHA_LAHIRI';
    public const MODEL_RAMAN = 'RAMAN';
    public const MODEL_KP = 'KRISHNAMURTI';

    public static function forCenturies(float $t, string $model = self::MODEL_LAHIRI): float
    {
        return match ($model) {
            self::MODEL_RAMAN => 21.014 + (1.398 * $t),
            self::MODEL_KP => 23.858072 + (0.0139697 * $t) + 0.025,
            default => 23.858072 + (0.0139697 * $t) + (0.0003086 * $t * $t),
        };
    }

    public static function toSidereal(float $tropicalLongitude, float $t, string $model = self::MODEL_LAHIRI): float
    {
        $ayanamsa = self::forCenturies($t, $model);
        $sidereal = $tropicalLongitude - $ayanamsa;

        return SolarLongitude::normalizeDegrees($sidereal);
    }
}

