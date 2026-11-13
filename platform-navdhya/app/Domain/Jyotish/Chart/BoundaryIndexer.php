<?php

namespace App\Domain\Jyotish\Chart;

use App\Domain\Jyotish\Contracts\ZodiacConstants;
use App\Domain\Jyotish\Support\Angle;

/**
 * High-Precision Boundary Indexing Service (T-JY02-05).
 *
 * Maps continuous sidereal ecliptic longitudes into 12 Rashis (30°),
 * 27 Nakshatras (13° 20'), and 108 Padas (3° 20').
 * Pure domain model — zero HTTP or framework dependencies (ADR-JY-01, doc 01 §7).
 */
final readonly class BoundaryIndexer implements ZodiacConstants
{
    /**
     * Map a sidereal ecliptic longitude into Rashi, Nakshatra, and Pada boundaries.
     *
     * @param float $degrees Sidereal longitude in degrees
     * @return array{
     *     total_degrees: float,
     *     rashi: array{index: int, name: string, english: string, lord: string, degrees_in_rashi: float, dms_in_rashi: string},
     *     nakshatra: array{index: int, name: string, lord: string, degrees_in_nakshatra: float, elapsed_fraction: float, pada: int, absolute_pada: int, syllable: string}
     * }
     */
    public static function index(float $degrees): array
    {
        $normalized = Angle::normalize($degrees);

        // Use small epsilon for numerical stability at exact boundary transitions
        $eps = 1e-11;

        // 1. Rashi Indexing (30° per rashi)
        $rashiIndex = (int) floor(($normalized + $eps) / self::RASHI_SPAN_DEGREES);
        if ($rashiIndex >= 12) {
            $rashiIndex = 11;
        }
        $degreesInRashi = $normalized - ($rashiIndex * self::RASHI_SPAN_DEGREES);
        if ($degreesInRashi < 0.0) {
            $degreesInRashi = 0.0;
        }
        $rashiMeta = self::RASHIS[$rashiIndex];

        // 2. Pada Indexing (108 equal padas of 3° 20' across 360°)
        // 360° / 108 = 10/3° per pada.
        $absolutePada = (int) floor((($normalized + $eps) * 108.0) / 360.0);
        if ($absolutePada >= 108) {
            $absolutePada = 107;
        }

        // 3. Nakshatra Indexing (27 nakshatras of 4 padas each)
        $nakshatraIndex = intdiv($absolutePada, 4);
        if ($nakshatraIndex >= 27) {
            $nakshatraIndex = 26;
        }

        $padaInNakshatra = ($absolutePada % 4) + 1;
        $nakshatraSpan = 360.0 / 27.0;
        $degInNakshatra = $normalized - ($nakshatraIndex * $nakshatraSpan);
        if ($degInNakshatra < 0.0) {
            $degInNakshatra = 0.0;
        }
        $elapsedFraction = $degInNakshatra / $nakshatraSpan;
        $nakshatraMeta = self::NAKSHATRAS[$nakshatraIndex];

        $syllable = $nakshatraMeta['syllables'][$padaInNakshatra - 1] ?? '';

        return [
            'total_degrees' => $normalized,
            'rashi' => [
                'index' => $rashiIndex,
                'name' => $rashiMeta['name'],
                'english' => $rashiMeta['english'],
                'lord' => $rashiMeta['lord'],
                'degrees_in_rashi' => $degreesInRashi,
                'dms_in_rashi' => (new Angle($degreesInRashi))->toDms(),
            ],
            'nakshatra' => [
                'index' => $nakshatraIndex,
                'name' => $nakshatraMeta['name'],
                'lord' => $nakshatraMeta['ruling_graha'],
                'degrees_in_nakshatra' => $degInNakshatra,
                'elapsed_fraction' => $elapsedFraction,
                'pada' => $padaInNakshatra,
                'absolute_pada' => $absolutePada,
                'syllable' => $syllable,
            ],
        ];
    }
}

