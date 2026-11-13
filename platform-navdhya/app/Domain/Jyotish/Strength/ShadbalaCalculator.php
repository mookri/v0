<?php

declare(strict_types=1);

namespace App\Domain\Jyotish\Strength;

use App\Domain\Jyotish\Strength\Components\CheshtaBala;
use App\Domain\Jyotish\Strength\Components\DigBala;
use App\Domain\Jyotish\Strength\Components\DrikBala;
use App\Domain\Jyotish\Strength\Components\KalaBala;
use App\Domain\Jyotish\Strength\Components\NaisargikaBala;
use App\Domain\Jyotish\Strength\Components\SthanaBala;

final readonly class ShadbalaCalculator
{
    /**
     * Minimum required Shadbala in Rupas (1 Rupa = 60 Virupas):
     * Sun: 6.5 Rupas (390 Virupas)
     * Moon: 6.0 Rupas (360 Virupas)
     * Mars: 5.0 Rupas (300 Virupas)
     * Mercury: 7.0 Rupas (420 Virupas)
     * Jupiter: 6.5 Rupas (390 Virupas)
     * Venus: 5.5 Rupas (330 Virupas)
     * Saturn: 5.0 Rupas (300 Virupas)
     */
    public const MINIMUM_RUPAS = [
        'sun' => 6.5,
        'moon' => 6.0,
        'mars' => 5.0,
        'mercury' => 7.0,
        'jupiter' => 6.5,
        'venus' => 5.5,
        'saturn' => 5.0,
    ];

    /**
     * Compute full 6-fold Shadbala for all 7 classical planets.
     *
     * @param array<string, array<string, mixed>> $grahaData
     * @param float $ascendantLongitude
     * @param bool $isDayBirth
     * @param float $moonPhaseFraction
     * @return array<string, array<string, mixed>>
     */
    public function calculateAll(
        array $grahaData,
        float $ascendantLongitude,
        bool $isDayBirth = true,
        float $moonPhaseFraction = 0.5
    ): array {
        $results = [];

        foreach ($grahaData as $graha => $data) {
            $g = strtolower($graha);
            if ($g === 'rahu' || $g === 'ketu') {
                continue; // Classical Shadbala applies to 7 physical bodies (BPHS Ch. 27)
            }

            $longitude = (float) ($data['longitude'] ?? 0.0);
            $rashi = (int) ($data['rashi'] ?? (floor($longitude / 30.0) + 1));
            $houseNumber = (int) ($data['house_number'] ?? 1);
            $isRetrograde = (bool) ($data['is_retrograde'] ?? false);
            $exaltationLong = (float) ($data['exaltation_longitude'] ?? 0.0);
            $gender = (string) ($data['gender'] ?? 'MALE');

            // 1. Sthana Bala
            $sthana = SthanaBala::calculate($longitude, $exaltationLong, $houseNumber, $rashi, $gender);

            // 2. Dig Bala
            $dig = DigBala::calculate($g, $longitude, $ascendantLongitude);

            // 3. Kala Bala
            $kala = KalaBala::calculate($g, $isDayBirth, $moonPhaseFraction);

            // 4. Cheshta Bala
            $cheshta = CheshtaBala::calculate($g, $isRetrograde, false);

            // 5. Naisargika Bala
            $naisargika = NaisargikaBala::get($g);

            // 6. Drik Bala
            $drik = DrikBala::calculate(15.0, 5.0); // Nominal aspect balance

            // Exact Summation (Virupas)
            $totalVirupas = $sthana['total_sthana_bala']
                + $dig['dig_bala']
                + $kala['total_kala_bala']
                + $cheshta
                + $naisargika
                + $drik;

            $totalRupas = $totalVirupas / 60.0;
            $minRequired = self::MINIMUM_RUPAS[$g] ?? 5.0;
            $strengthRatio = $totalRupas / $minRequired;

            $results[$g] = [
                'graha' => $g,
                'components_virupas' => [
                    'sthana_bala' => $sthana['total_sthana_bala'],
                    'dig_bala' => $dig['dig_bala'],
                    'kala_bala' => $kala['total_kala_bala'],
                    'cheshta_bala' => $cheshta,
                    'naisargika_bala' => $naisargika,
                    'drik_bala' => $drik,
                ],
                'sub_factors' => [
                    'sthana_details' => $sthana,
                    'dig_details' => $dig,
                    'kala_details' => $kala,
                ],
                'total_virupas' => round($totalVirupas, 2),
                'total_rupas' => round($totalRupas, 2),
                'minimum_required_rupas' => $minRequired,
                'strength_ratio' => round($strengthRatio, 2),
                'is_sufficient_strength' => ($strengthRatio >= 1.0),
            ];
        }

        return $results;
    }
}

