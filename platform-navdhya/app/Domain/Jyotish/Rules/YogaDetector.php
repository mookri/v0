<?php

declare(strict_types=1);

namespace App\Domain\Jyotish\Rules;

final readonly class YogaDetector
{
    /**
     * Detect classical Parashari Yogas from planetary and house positions.
     *
     * @param array<string, array<string, mixed>> $grahaPositions [graha => [rashi, house, is_retrograde, etc.]]
     * @param int $lagnaRashi
     * @return list<array<string, mixed>>
     */
    public function detect(array $grahaPositions, int $lagnaRashi): array
    {
        $detected = [];

        // 1. Gajakesari Yoga: Jupiter in a Kendra (1, 4, 7, 10) from Moon
        if (isset($grahaPositions['jupiter'], $grahaPositions['moon'])) {
            $jupHouse = (int) $grahaPositions['jupiter']['house'];
            $moonHouse = (int) $grahaPositions['moon']['house'];
            $relHouse = (($jupHouse - $moonHouse + 12) % 12) + 1;

            if (in_array($relHouse, [1, 4, 7, 10], true)) {
                $detected[] = [
                    'code' => 'GAJAKESARI_YOGA',
                    'name' => 'Gajakesari Yoga',
                    'nature' => 'BENEFIC',
                    'description' => 'Jupiter occupies a Kendra house from the Moon, conferring wisdom, repute, and lasting virtue.',
                    'source_reference' => 'Brihat Parashara Hora Shastra, Ch. 35, Sloka 14',
                    'conditions_met' => ["Jupiter in {$relHouse}th house from Moon (Kendra)"],
                ];
            }
        }

        // 2. Budhaditya Yoga: Sun and Mercury conjoined in the same Rashi
        if (isset($grahaPositions['sun'], $grahaPositions['mercury'])) {
            if ($grahaPositions['sun']['rashi'] === $grahaPositions['mercury']['rashi']) {
                $detected[] = [
                    'code' => 'BUDHADITYA_YOGA',
                    'name' => 'Budhaditya Yoga',
                    'nature' => 'BENEFIC',
                    'description' => 'Conjunction of Sun and Mercury enhances intellectual sharpness, administrative acumen, and eloquence.',
                    'source_reference' => 'Saravali, Ch. 15, Sloka 12',
                    'conditions_met' => ['Sun and Mercury share sign ' . $grahaPositions['sun']['rashi']],
                ];
            }
        }

        // 3. Pancha Mahapurusha Yogas (Mars: Ruchaka, Mercury: Bhadra, Jupiter: Hamsa, Venus: Malavya, Saturn: Sasa)
        // Condition: Planet in own sign or exalted AND in a Kendra house (1, 4, 7, 10) from Lagna.
        $mahapurushaMap = [
            'mars' => ['own' => [1, 8], 'exalted' => 10, 'name' => 'Ruchaka Yoga', 'code' => 'RUCHAKA_YOGA'],
            'mercury' => ['own' => [3, 6], 'exalted' => 6, 'name' => 'Bhadra Yoga', 'code' => 'BHADRA_YOGA'],
            'jupiter' => ['own' => [9, 12], 'exalted' => 4, 'name' => 'Hamsa Yoga', 'code' => 'HAMSA_YOGA'],
            'venus' => ['own' => [2, 7], 'exalted' => 12, 'name' => 'Malavya Yoga', 'code' => 'MALAVYA_YOGA'],
            'saturn' => ['own' => [10, 11], 'exalted' => 7, 'name' => 'Sasa Yoga', 'code' => 'SASA_YOGA'],
        ];

        foreach ($mahapurushaMap as $graha => $spec) {
            if (isset($grahaPositions[$graha])) {
                $h = (int) $grahaPositions[$graha]['house'];
                $r = (int) $grahaPositions[$graha]['rashi'];
                $isKendra = in_array($h, [1, 4, 7, 10], true);
                $isOwnOrExalted = in_array($r, $spec['own'], true) || ($r === $spec['exalted']);

                if ($isKendra && $isOwnOrExalted) {
                    $detected[] = [
                        'code' => $spec['code'],
                        'name' => $spec['name'],
                        'nature' => 'BENEFIC',
                        'description' => "{$spec['name']} (Pancha Mahapurusha) formed by {$graha} placed in Kendra in own/exalted dignity.",
                        'source_reference' => 'Brihat Parashara Hora Shastra, Ch. 75, Slokas 1-12',
                        'conditions_met' => ["{$graha} in house {$h} in dignity sign {$r}"],
                    ];
                }
            }
        }

        return $detected;
    }
}

