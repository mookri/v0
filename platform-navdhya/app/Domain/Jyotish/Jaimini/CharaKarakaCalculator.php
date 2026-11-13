<?php

declare(strict_types=1);

namespace App\Domain\Jyotish\Jaimini;

final readonly class CharaKarakaCalculator
{
    /**
     * 7-Karaka and 8-Karaka designations in classical descending degree order:
     * 1. Atmakaraka (AK) - Soul significator
     * 2. Amatyakaraka (AmK) - Career, intellect
     * 3. Bhratrukaraka (BK) - Siblings, courage
     * 4. Matrukaraka (MK) - Mother, happiness
     * 5. Pitrukaraka (PiK) - Father (in 8-karaka scheme)
     * 6. Putrakaraka (PK) - Children, creativity
     * 7. Gnatikaraka (GK) - Relatives, obstacles
     * 8. Darakaraka (DK) - Spouse, partners
     */
    public const KARAKAS_7 = ['AK', 'AmK', 'BK', 'MK', 'PK', 'GK', 'DK'];
    public const KARAKAS_8 = ['AK', 'AmK', 'BK', 'MK', 'PiK', 'PK', 'GK', 'DK'];

    /**
     * Calculate Chara Karakas from planet degrees within their respective signs.
     * In 8-Karaka mode, Rahu is included, with its degree reversed: (30° - deg) because of retrogression.
     *
     * @param array<string, float> $planetLongitudes [sun, moon, mars, mercury, jupiter, venus, saturn, rahu]
     * @param int $scheme 7 or 8
     * @return array<string, array{karaka: string, graha: string, degree_in_sign: float}>
     */
    public function calculate(array $planetLongitudes, int $scheme = 7): array
    {
        $bodies = ($scheme === 8)
            ? ['sun', 'moon', 'mars', 'mercury', 'jupiter', 'venus', 'saturn', 'rahu']
            : ['sun', 'moon', 'mars', 'mercury', 'jupiter', 'venus', 'saturn'];

        $signDegrees = [];
        foreach ($bodies as $b) {
            if (!isset($planetLongitudes[$b])) {
                continue;
            }

            $long = $planetLongitudes[$b];
            $degInSign = fmod($long, 30.0);

            // In Jaimini 8-karaka scheme, Rahu's traversed degree is measured in reverse
            if ($b === 'rahu' && $scheme === 8) {
                $degInSign = 30.0 - $degInSign;
            }

            $signDegrees[$b] = $degInSign;
        }

        // Sort descending by degrees in sign
        arsort($signDegrees);

        $karakaNames = ($scheme === 8) ? self::KARAKAS_8 : self::KARAKAS_7;
        $assigned = [];
        $idx = 0;

        foreach ($signDegrees as $graha => $deg) {
            if ($idx >= count($karakaNames)) {
                break;
            }
            $kName = $karakaNames[$idx];
            $assigned[$kName] = [
                'karaka' => $kName,
                'graha' => $graha,
                'degree_in_sign' => round($deg, 4),
            ];
            $idx++;
        }

        return $assigned;
    }
}

