<?php

namespace App\Domain\Jyotish\Support;

/**
 * Pure domain Angle value object and geometric helper (T-JY02-04).
 * Pure domain model — zero HTTP or framework dependencies (ADR-JY-01, doc 01 §7).
 */
final readonly class Angle
{
    public float $degrees;

    public function __construct(float $degrees)
    {
        $this->degrees = self::normalize($degrees);
    }

    public static function fromDegrees(float $degrees): self
    {
        return new self($degrees);
    }

    public static function fromRadians(float $radians): self
    {
        return new self(rad2deg($radians));
    }

    public function toRadians(): float
    {
        return deg2rad($this->degrees);
    }

    /**
     * Normalize degrees into [0.0, 360.0).
     */
    public static function normalize(float $deg): float
    {
        $normalized = fmod($deg, 360.0);
        if ($normalized < 0.0) {
            $normalized += 360.0;
        }

        return $normalized;
    }

    /**
     * Compute signed shortest angular difference between two angles in range [-180.0, 180.0].
     */
    public static function difference(float $a, float $b): float
    {
        $diff = fmod($a - $b + 180.0, 360.0);
        if ($diff < 0.0) {
            $diff += 360.0;
        }

        return $diff - 180.0;
    }

    /**
     * Format angle as degrees, arcminutes, arcseconds: D° MM' SS.S".
     */
    public function toDms(int $precision = 1): string
    {
        $d = (int) floor($this->degrees);
        $remainder = ($this->degrees - $d) * 60.0;
        $m = (int) floor($remainder);
        $s = ($remainder - $m) * 60.0;

        return sprintf("%d° %02d' %0" . ($precision > 0 ? (3 + $precision) : 2) . ".{$precision}f\"", $d, $m, $s);
    }
}

