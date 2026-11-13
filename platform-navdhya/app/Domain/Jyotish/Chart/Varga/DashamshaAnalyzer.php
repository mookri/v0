<?php

namespace App\Domain\Jyotish\Chart\Varga;

use App\Domain\Jyotish\Contracts\ZodiacConstants;
use App\Domain\Jyotish\Rules\Datasets\ParashariDignities;
use App\Domain\Jyotish\Support\Angle;

/**
 * Deep D10 Dashamsha Career Analyzer (T-JY04-08).
 *
 * Implements:
 * - D10 Lagna, 10th house, and career prestige lordships
 * - Artha trikona (2nd, 6th, 10th) planetary strength and distribution
 *
 * Pure domain model — zero HTTP or framework dependencies (ADR-JY-01, doc 01 §7).
 */
final readonly class DashamshaAnalyzer implements ZodiacConstants
{
    /**
     * Analyze D10 Dashamsha career chart.
     *
     * @param float $lagnaDegrees Sidereal Ascendant
     * @param array<string, float> $planetaryDegrees Map of planet => sidereal degree
     * @return array{
     *     dashamsha_lagna: array{rashi_index: int, rashi_name: string, rashi_lord: string},
     *     tenth_house: array{rashi_index: int, rashi_name: string, lord: string, occupants: list<string>},
     *     artha_trikona_planets: array{house_2: list<string>, house_6: list<string>, house_10: list<string>},
     *     placements: array<string, array{planet: string, d10_rashi: int, d10_bhava: int}>
     * }
     */
    public static function analyze(float $lagnaDegrees, array $planetaryDegrees): array
    {
        $d10Lagna = VargaHarmonicEngine::calculate('D10', $lagnaDegrees);
        $d10LagnaRashi = $d10Lagna['rashi_index'];

        $tenthHouseRashi = ($d10LagnaRashi + 9) % 12;
        $tenthHouseLord = ParashariDignities::RASHI_LORDS[$tenthHouseRashi];

        $occupants10 = [];
        $house2 = [];
        $house6 = [];
        $house10 = [];
        $placements = [];

        foreach ($planetaryDegrees as $planet => $deg) {
            $d10 = VargaHarmonicEngine::calculate('D10', Angle::normalize($deg));
            $rashi = $d10['rashi_index'];
            $bhava = (($rashi - $d10LagnaRashi + 12) % 12) + 1;

            if ($bhava === 10) {
                $occupants10[] = $planet;
                $house10[] = $planet;
            } elseif ($bhava === 2) {
                $house2[] = $planet;
            } elseif ($bhava === 6) {
                $house6[] = $planet;
            }

            $placements[$planet] = [
                'planet' => $planet,
                'd10_rashi' => $rashi,
                'd10_bhava' => $bhava,
            ];
        }

        return [
            'dashamsha_lagna' => [
                'rashi_index' => $d10LagnaRashi,
                'rashi_name' => $d10Lagna['rashi_name'],
                'rashi_lord' => $d10Lagna['rashi_lord'],
            ],
            'tenth_house' => [
                'rashi_index' => $tenthHouseRashi,
                'rashi_name' => self::RASHIS[$tenthHouseRashi]['name'],
                'lord' => $tenthHouseLord,
                'occupants' => $occupants10,
            ],
            'artha_trikona_planets' => [
                'house_2' => $house2,
                'house_6' => $house6,
                'house_10' => $house10,
            ],
            'placements' => $placements,
        ];
    }
}

