<?php

namespace App\Domain\Jyotish\Rules;

use App\Domain\Jyotish\Contracts\ZodiacConstants;
use App\Domain\Jyotish\Support\Angle;

/**
 * Classical Planetary Avastha Evaluator (BPHS Chapter 45, T-JY04-01).
 *
 * Implements:
 * 1. Baladi Avasthas (Age States: Bala, Kumara, Yuva, Vriddha, Mrita)
 * 2. Jagradadi Avasthas (Alertness States: Jagrita, Swapna, Sushupti)
 * 3. Deeptadi Avasthas (Luminosity States: Deepta, Swastha, Mudita, Shanta, Heena, Duhkhita, Khala, Deena, Vikala)
 *
 * Pure domain model — zero HTTP or framework dependencies (ADR-JY-01, doc 01 §7).
 */
final readonly class AvasthaEvaluator implements ZodiacConstants
{
    /**
     * Evaluate all classical avasthas for a planetary placement.
     *
     * @param string $planet Planetary name ('sun', 'moon', etc.)
     * @param float $siderealDegrees Longitude in degrees [0, 360)
     * @param string $dignity Dignity code from DignityEngine ('exalted', 'own_sign', etc.)
     * @param bool $isCombust Whether the planet is combust by Sun
     * @return array{
     *     baladi: array{code: string, name: string, potency: float, description: string},
     *     jagradadi: array{code: string, name: string, potency: float, description: string},
     *     deeptadi: array{code: string, name: string, potency: float, description: string}
     * }
     */
    public static function evaluate(
        string $planet,
        float $siderealDegrees,
        string $dignity = 'neutral',
        bool $isCombust = false
    ): array {
        $normalizedDeg = Angle::normalize($siderealDegrees);
        $rashiIndex = (int) floor($normalizedDeg / self::RASHI_SPAN_DEGREES);
        if ($rashiIndex >= 12) {
            $rashiIndex = 11;
        }
        $degInRashi = $normalizedDeg - ($rashiIndex * self::RASHI_SPAN_DEGREES);

        // 1. Baladi Avastha (Odd vs Even sign)
        // Odd signs: 0 (Mesha), 2 (Mithuna), 4 (Simha), 6 (Tula), 8 (Dhanu), 10 (Kumbha)
        $isOddSign = ($rashiIndex % 2 === 0);
        $baladi = self::evaluateBaladi($degInRashi, $isOddSign);

        // 2. Jagradadi Avastha (Alertness)
        $jagradadi = self::evaluateJagradadi($dignity);

        // 3. Deeptadi Avastha (Luminosity / 9-fold states)
        $deeptadi = self::evaluateDeeptadi($dignity, $isCombust);

        return [
            'baladi' => $baladi,
            'jagradadi' => $jagradadi,
            'deeptadi' => $deeptadi,
        ];
    }

    /**
     * Compute Baladi (Infancy to Death) Avastha based on 6° slices.
     *
     * @return array{code: string, name: string, potency: float, description: string}
     */
    private static function evaluateBaladi(float $degInRashi, bool $isOddSign): array
    {
        $slice = (int) floor($degInRashi / 6.0);
        if ($slice >= 5) {
            $slice = 4;
        }

        // Odd signs order: Bala (0), Kumara (1), Yuva (2), Vriddha (3), Mrita (4)
        // Even signs order: Mrita (0), Vriddha (1), Yuva (2), Kumara (3), Bala (4)
        $stateIndex = $isOddSign ? $slice : (4 - $slice);

        return match ($stateIndex) {
            0 => [
                'code' => 'bala',
                'name' => 'Bala (Infant)',
                'potency' => 0.25,
                'description' => 'Infant state with 25% fruit-giving capability.',
            ],
            1 => [
                'code' => 'kumara',
                'name' => 'Kumara (Youth)',
                'potency' => 0.50,
                'description' => 'Youthful state with 50% fruit-giving capability.',
            ],
            2 => [
                'code' => 'yuva',
                'name' => 'Yuva (Prime / Adolescent)',
                'potency' => 1.00,
                'description' => 'Peak prime maturity with 100% full fruit-giving capability.',
            ],
            3 => [
                'code' => 'vriddha',
                'name' => 'Vriddha (Old)',
                'potency' => 0.125,
                'description' => 'Aged state with minimal ~12.5% fruit-giving capability.',
            ],
            4 => [
                'code' => 'mrita',
                'name' => 'Mrita (Dead / Dormant)',
                'potency' => 0.00,
                'description' => 'Dormant state with nil (0%) fruit-giving capability.',
            ],
            default => [
                'code' => 'yuva',
                'name' => 'Yuva',
                'potency' => 1.0,
                'description' => 'Prime state.',
            ],
        };
    }

    /**
     * Compute Jagradadi (Awake/Dreaming/Asleep) Avastha.
     *
     * @return array{code: string, name: string, potency: float, description: string}
     */
    private static function evaluateJagradadi(string $dignity): array
    {
        return match ($dignity) {
            'exalted', 'moolatrikona', 'own_sign' => [
                'code' => 'jagrita',
                'name' => 'Jagrita (Awake)',
                'potency' => 1.0,
                'description' => 'Fully alert and conscious fruit manifestation.',
            ],
            'great_friend', 'friend', 'neutral' => [
                'code' => 'swapna',
                'name' => 'Swapna (Dreaming)',
                'potency' => 0.5,
                'description' => 'Semi-conscious dreaming state with medium potency.',
            ],
            'enemy', 'bitter_enemy', 'debilitated' => [
                'code' => 'sushupti',
                'name' => 'Sushupti (Deep Sleep)',
                'potency' => 0.1,
                'description' => 'Deep sleep state producing negligible or delayed results.',
            ],
            default => [
                'code' => 'swapna',
                'name' => 'Swapna',
                'potency' => 0.5,
                'description' => 'Medium state.',
            ],
        };
    }

    /**
     * Compute Deeptadi (9-fold luminosity) Avastha.
     *
     * @return array{code: string, name: string, potency: float, description: string}
     */
    private static function evaluateDeeptadi(string $dignity, bool $isCombust): array
    {
        if ($isCombust) {
            return [
                'code' => 'vikala',
                'name' => 'Vikala (Combust / Troubled)',
                'potency' => 0.1,
                'description' => 'Burned by close solar proximity; weakened expression.',
            ];
        }

        return match ($dignity) {
            'exalted' => [
                'code' => 'deepta',
                'name' => 'Deepta (Bright / Radiant)',
                'potency' => 1.0,
                'description' => 'Exalted radiance bestowing outstanding auspicious results.',
            ],
            'own_sign', 'moolatrikona' => [
                'code' => 'swastha',
                'name' => 'Swastha (Comfortable / Content)',
                'potency' => 0.9,
                'description' => 'At ease in own domain; steady stability and success.',
            ],
            'great_friend' => [
                'code' => 'mudita',
                'name' => 'Mudita (Delighted / Joyful)',
                'potency' => 0.8,
                'description' => 'Warm reception by an intimate friend.',
            ],
            'friend' => [
                'code' => 'shanta',
                'name' => 'Shanta (Peaceful)',
                'potency' => 0.7,
                'description' => 'Calm and amicable surroundings.',
            ],
            'neutral' => [
                'code' => 'heena',
                'name' => 'Heena (Deficient / Ordinary)',
                'potency' => 0.5,
                'description' => 'Indifferent environment requiring exertion.',
            ],
            'enemy' => [
                'code' => 'duhkhita',
                'name' => 'Duhkhita (Distressed)',
                'potency' => 0.3,
                'description' => 'Unwelcome in hostile territory; friction and obstacles.',
            ],
            'bitter_enemy' => [
                'code' => 'khala',
                'name' => 'Khala (Malicious / Base)',
                'potency' => 0.15,
                'description' => 'Trapped in bitter enmity with sharp adversity.',
            ],
            'debilitated' => [
                'code' => 'deena',
                'name' => 'Deena (Miserable / Humiliated)',
                'potency' => 0.05,
                'description' => 'Debilitated state lacking strength or resources.',
            ],
            default => [
                'code' => 'heena',
                'name' => 'Heena',
                'potency' => 0.5,
                'description' => 'Ordinary state.',
            ],
        };
    }
}

