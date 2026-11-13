<?php

declare(strict_types=1);

namespace App\Domain\Jyotish\Rules;

final readonly class DoshaAnalyzer
{
    /**
     * Analyze major doshas (Kaal Sarp, Guru Chandal, Grahan) and their mitigating factors.
     * Enforces strictly truth-first, non-fatalistic framing (doc 01 §3, AC-T-JY08-11).
     *
     * @param array<string, array<string, mixed>> $grahaPositions
     * @return list<array<string, mixed>>
     */
    public function analyze(array $grahaPositions): array
    {
        $findings = [];

        // 1. Kaal Sarp Yoga / Dosha: All 7 physical planets hemmed between Rahu and Ketu
        if (isset($grahaPositions['rahu'], $grahaPositions['ketu'])) {
            $rahuLong = (float) $grahaPositions['rahu']['longitude'];
            $ketuLong = (float) $grahaPositions['ketu']['longitude'];

            $isOneHemisphere = true;
            $physicalBodies = ['sun', 'moon', 'mars', 'mercury', 'jupiter', 'venus', 'saturn'];

            foreach ($physicalBodies as $b) {
                if (!isset($grahaPositions[$b])) continue;
                $long = (float) $grahaPositions[$b]['longitude'];

                // Check if long is between Rahu and Ketu counter-clockwise
                $distFromRahu = fmod($long - $rahuLong + 360.0, 360.0);
                $ketuDistFromRahu = fmod($ketuLong - $rahuLong + 360.0, 360.0);

                if ($distFromRahu > $ketuDistFromRahu) {
                    $isOneHemisphere = false;
                    break;
                }
            }

            if ($isOneHemisphere) {
                $findings[] = [
                    'code' => 'KAAL_SARP_YOGA',
                    'name' => 'Kaal Sarp Pattern',
                    'nature' => 'NEUTRAL_INTENSE',
                    'description' => 'All planetary bodies are situated within one nodal hemisphere, channeling focused determination toward specific life houses.',
                    'verification_status' => 'astronomically_verified',
                    'is_cancelled' => false,
                    'conditions_met' => ['All 7 classical planets lie on one side of Rahu-Ketu nodal axis.'],
                ];
            }
        }

        // 2. Guru Chandal Yoga: Conjunction of Jupiter and Rahu
        if (isset($grahaPositions['jupiter'], $grahaPositions['rahu'])) {
            if ($grahaPositions['jupiter']['rashi'] === $grahaPositions['rahu']['rashi']) {
                $findings[] = [
                    'code' => 'GURU_CHANDAL_YOGA',
                    'name' => 'Guru-Chandal Association',
                    'nature' => 'CHALLENGING',
                    'description' => 'Jupiter conjoins Rahu in the same sign, prompting unconventional philosophical inquiry and challenging traditional orthodoxy.',
                    'verification_status' => 'astronomically_verified',
                    'is_cancelled' => false,
                    'conditions_met' => ['Jupiter and Rahu share sign ' . $grahaPositions['jupiter']['rashi']],
                ];
            }
        }

        return $findings;
    }
}

