<?php

namespace App\Domain\Jyotish\Rules;

/**
 * 9x12 Planetary and Sign Aspect Matrix Calculator (T-JY04-04).
 * Pure domain model — zero HTTP or framework dependencies (ADR-JY-01, doc 01 §7).
 */
final readonly class AspectMatrixCalculator
{
    public const GRAHAS = ['sun', 'moon', 'mars', 'mercury', 'jupiter', 'venus', 'saturn', 'rahu', 'ketu'];

    /**
     * Build complete 9x12 aspect matrix from planetary rashis to all 12 signs/houses.
     *
     * @param array<string, int> $planetaryRashis Map of planet => rashi index (0..11)
     * @param int $lagnaRashi Rashi index of Lagna (0..11)
     * @return array{
     *     graha_to_bhavas: array<string, array<int, float>>,
     *     graha_to_grahas: array<string, array<string, float>>
     * }
     */
    public static function build(array $planetaryRashis, int $lagnaRashi = 0): array
    {
        $grahaToBhavas = [];
        $grahaToGrahas = [];

        // 1. Graha to 12 Bhavas (relative to Lagna)
        foreach (self::GRAHAS as $graha) {
            $grahaToBhavas[$graha] = [];

            if (! isset($planetaryRashis[$graha])) {
                for ($bhava = 1; $bhava <= 12; $bhava++) {
                    $grahaToBhavas[$graha][$bhava] = 0.0;
                }
                continue;
            }

            $sourceRashi = $planetaryRashis[$graha];

            for ($bhava = 1; $bhava <= 12; $bhava++) {
                // Target rashi for $bhava
                $targetRashi = ($lagnaRashi + ($bhava - 1)) % 12;
                $houseDist = (($targetRashi - $sourceRashi + 12) % 12) + 1;

                $strength = GrahaDrishtiEvaluator::getAspectStrength($graha, $houseDist);
                $grahaToBhavas[$graha][$bhava] = $strength;
            }
        }

        // 2. Graha to Graha mutual aspect strengths
        foreach (self::GRAHAS as $gSource) {
            $grahaToGrahas[$gSource] = [];

            if (! isset($planetaryRashis[$gSource])) {
                continue;
            }
            $sourceRashi = $planetaryRashis[$gSource];

            foreach (self::GRAHAS as $gTarget) {
                if ($gSource === $gTarget || ! isset($planetaryRashis[$gTarget])) {
                    $grahaToGrahas[$gSource][$gTarget] = 0.0;
                    continue;
                }

                $targetRashi = $planetaryRashis[$gTarget];
                $houseDist = (($targetRashi - $sourceRashi + 12) % 12) + 1;

                $strength = GrahaDrishtiEvaluator::getAspectStrength($gSource, $houseDist);
                $grahaToGrahas[$gSource][$gTarget] = $strength;
            }
        }

        return [
            'graha_to_bhavas' => $grahaToBhavas,
            'graha_to_grahas' => $grahaToGrahas,
        ];
    }
}

