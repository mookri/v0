<?php

declare(strict_types=1);

namespace App\Domain\Jyotish\Strength\Components;

final readonly class DigBala
{
    /**
     * Compute Directional Strength (Dig Bala) in Virupas (0..60).
     * Jupiter & Mercury gain maximum strength in 1st house (East).
     * Sun & Mars gain maximum strength in 10th house (South).
     * Saturn gains maximum strength in 7th house (West).
     * Moon & Venus gain maximum strength in 4th house (North).
     *
     * @param string $graha
     * @param float $planetLongitude
     * @param float $ascendantLongitude
     * @return array{dig_bala: float, powerful_house: int}
     */
    public static function calculate(string $graha, float $planetLongitude, float $ascendantLongitude): array
    {
        $powerfulHouse = match (strtolower($graha)) {
            'jupiter', 'mercury' => 1,
            'sun', 'mars' => 10,
            'saturn' => 7,
            'moon', 'venus' => 4,
            default => 1,
        };

        // Longitude of the directional peak point (cusp of powerful house)
        $peakLongitude = fmod($ascendantLongitude + (($powerfulHouse - 1) * 30.0), 360.0);
        $diff = abs($planetLongitude - $peakLongitude);
        if ($diff > 180.0) {
            $diff = 360.0 - $diff;
        }

        // Dig Bala formula: (180 - diff) / 3. Max is 60 Virupas at peak; 0 Virupas at opposite cusp
        $digBala = (180.0 - $diff) / 3.0;

        return [
            'dig_bala' => round(max(0.0, min(60.0, $digBala)), 2),
            'powerful_house' => $powerfulHouse,
        ];
    }
}

