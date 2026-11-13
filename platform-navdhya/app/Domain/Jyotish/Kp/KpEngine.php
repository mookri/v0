<?php

declare(strict_types=1);

namespace App\Domain\Jyotish\Kp;

use App\Domain\Jyotish\Chart\HouseSystem\PlacidusSystem;
use App\Domain\Jyotish\Kp\SubLordDivider;

final readonly class KpEngine
{
    /**
     * Compute KP Cusps and Sub-lords for all 12 houses and all planetary bodies.
     *
     * @param float $ascendantLongitude
     * @param float $ramcDegrees
     * @param float $latitude
     * @param array<string, float> $planetLongitudes
     * @return array<string, mixed>
     */
    public function calculate(
        float $ascendantLongitude,
        float $ramcDegrees,
        float $latitude,
        array $planetLongitudes
    ): array {
        // 1. Placidus Cusps (classical KP standard)
        $placidus = new PlacidusSystem();
        $cusps = $placidus->calculateCusps($ascendantLongitude, $ramcDegrees, $latitude);

        $cuspDetails = [];
        foreach ($cusps as $house => $cuspLong) {
            $subInfo = SubLordDivider::findSubLord($cuspLong);
            $sign = (int) floor($cuspLong / 30.0) + 1;

            $cuspDetails[$house] = [
                'house' => $house,
                'longitude' => round($cuspLong, 4),
                'sign' => $sign,
                'nakshatra' => $subInfo['nakshatra'],
                'star_lord' => $subInfo['star_lord'],
                'sub_lord' => $subInfo['sub_lord'],
            ];
        }

        // 2. Planet Star & Sub-lords
        $planetDetails = [];
        foreach ($planetLongitudes as $graha => $long) {
            $subInfo = SubLordDivider::findSubLord($long);
            $sign = (int) floor($long / 30.0) + 1;

            $planetDetails[$graha] = [
                'graha' => $graha,
                'longitude' => round($long, 4),
                'sign' => $sign,
                'nakshatra' => $subInfo['nakshatra'],
                'star_lord' => $subInfo['star_lord'],
                'sub_lord' => $subInfo['sub_lord'],
            ];
        }

        return [
            'ayanamsa' => 'KRISHNAMURTI',
            'house_system' => 'PLACIDUS',
            'cusps' => $cuspDetails,
            'planets' => $planetDetails,
        ];
    }

    /**
     * Resolve KP Horary Number (1..249) into its exact starting sidereal boundary.
     * 249 sub-lord subdivisions span the entire 360° zodiac.
     *
     * @param int $horaryNumber 1..249
     * @return array{number: int, nakshatra: int, star_lord: string, sub_lord: string}
     */
    public static function resolveHoraryNumber(int $horaryNumber): array
    {
        if ($horaryNumber < 1 || $horaryNumber > 249) {
            throw new \InvalidArgumentException("KP Horary Number must be between 1 and 249, got {$horaryNumber}.");
        }

        // Each horary number corresponds to one discrete sub-lord slot across the 27 nakshatras
        // 27 nakshatras * ~9.22 sub-portions = 249 total boundaries
        $nakshatraIndex = (int) floor(($horaryNumber - 1) / 9.22);
        if ($nakshatraIndex > 26) $nakshatraIndex = 26;

        $subDivs = SubLordDivider::getSubDivisions($nakshatraIndex);
        $subIdx = ($horaryNumber - 1) % 9;
        $div = $subDivs[$subIdx] ?? $subDivs[0];

        return [
            'number' => $horaryNumber,
            'nakshatra' => $nakshatraIndex + 1,
            'star_lord' => SubLordDivider::LORDS_ORDER[$nakshatraIndex % 9],
            'sub_lord' => $div['sub_lord'],
        ];
    }
}

