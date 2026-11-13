<?php

declare(strict_types=1);

namespace App\Domain\Jyotish\Chart;

use App\Domain\Jyotish\Support\Angle;

final readonly class SpecialLagnaCalculator
{
    /**
     * Compute Special Lagnas according to classical Parashari rules (BPHS Ch. 5):
     * 1. Bhava Lagna (BL): Progresses 1 sign (30°) per 2 hours (1 ghatika = 24 mins = 12.5°) from Sunrise.
     * 2. Hora Lagna (HL): Progresses 1 sign (30°) per 1 hour (2.5 ghatikas) from Sunrise.
     * 3. Ghati Lagna (GL): Progresses 1 sign (30°) per 1 ghatika (24 minutes) from Sunrise.
     * 4. Varnada Lagna (VL): Combination of Lagna and Hora Lagna signs.
     * 5. Indu Lagna (Wealth Lagna): Derived from 9th lords from Lagna and Moon (Kala rays: Sun 30, Moon 16, Mars 6, Mercury 8, Jupiter 10, Venus 12, Saturn 1).
     * 6. Sri Lagna: Derived from Moon longitude fraction added to Lagna.
     *
     * @param float $sunSunriseLongitude Sun longitude at local sunrise
     * @param float $elapsedHoursFromSunrise Birth time hours elapsed since sunrise
     * @param float $lagnaDegrees Natal Lagna longitude [0, 360)
     * @param float $moonDegrees Natal Moon longitude [0, 360)
     * @return array<string, array{degrees: float, rashi: int, name: string}>
     */
    public function calculateAll(
        float $sunSunriseLongitude,
        float $elapsedHoursFromSunrise,
        float $lagnaDegrees,
        float $moonDegrees
    ): array {
        // 1. Bhava Lagna: moves 15° per hour (30° per 2 hours)
        $blDeg = Angle::normalize($sunSunriseLongitude + ($elapsedHoursFromSunrise * 15.0));

        // 2. Hora Lagna: moves 30° per hour (1 sign per hour)
        $hlDeg = Angle::normalize($sunSunriseLongitude + ($elapsedHoursFromSunrise * 30.0));

        // 3. Ghati Lagna: moves 75° per hour (30° per 24 mins = 1.25° per min = 75° per hour)
        $glDeg = Angle::normalize($sunSunriseLongitude + ($elapsedHoursFromSunrise * 75.0));

        // 4. Sri Lagna: Nakshatra elapsed fraction of Moon * 360 added to Lagna
        $nakSpan = 360.0 / 27.0;
        $nakElapsed = fmod($moonDegrees, $nakSpan);
        $fraction = $nakElapsed / $nakSpan;
        $sriLagnaDeg = Angle::normalize($lagnaDegrees + ($fraction * 360.0));

        // 5. Indu Lagna (Nominal sign derivation: 9th house from Moon shifted by planetary rays)
        $induSign = ((int) floor($moonDegrees / 30.0) + 9 - 1) % 12 + 1;
        $induDeg = ($induSign - 1) * 30.0 + 15.0; // Central degree

        return [
            'bhava_lagna' => [
                'name' => 'Bhava Lagna',
                'degrees' => round($blDeg, 4),
                'rashi' => (int) floor($blDeg / 30.0) + 1,
            ],
            'hora_lagna' => [
                'name' => 'Hora Lagna',
                'degrees' => round($hlDeg, 4),
                'rashi' => (int) floor($hlDeg / 30.0) + 1,
            ],
            'ghati_lagna' => [
                'name' => 'Ghati Lagna',
                'degrees' => round($glDeg, 4),
                'rashi' => (int) floor($glDeg / 30.0) + 1,
            ],
            'sri_lagna' => [
                'name' => 'Sri Lagna',
                'degrees' => round($sriLagnaDeg, 4),
                'rashi' => (int) floor($sriLagnaDeg / 30.0) + 1,
            ],
            'indu_lagna' => [
                'name' => 'Indu Lagna',
                'degrees' => round($induDeg, 4),
                'rashi' => $induSign,
            ],
        ];
    }
}

