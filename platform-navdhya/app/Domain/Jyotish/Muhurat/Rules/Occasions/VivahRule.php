<?php

declare(strict_types=1);

namespace App\Domain\Jyotish\Muhurat\Rules\Occasions;

use App\Domain\Jyotish\Muhurat\Rules\OccasionRuleInterface;

final readonly class VivahRule implements OccasionRuleInterface
{
    public function getOccasionCode(): string
    {
        return 'VIVAH';
    }

    public function getOccasionName(): string
    {
        return 'Vivah (Wedding Ceremony)';
    }

    public function getSourceReference(): string
    {
        return 'Muhurta Chintamani, Vivah Prakarana, Slokas 12-45; Brihat Samhita Ch. 99';
    }

    public function evaluate(array $context): array
    {
        $scoreDelta = 0.0;
        $isDisqualified = false;
        $reasons = [];
        $exclusions = [];

        $tithiNum = (int) ($context['tithi_number'] ?? 1);
        $nakshatraNum = (int) ($context['nakshatra_number'] ?? 1);
        $varaNum = (int) ($context['vara_number'] ?? 1);

        // 1. Hard Exclusions (Rikta Tithis: 4, 9, 14, Amavasya: 30)
        if (in_array($tithiNum, [4, 9, 14, 19, 24, 29, 30], true)) {
            $isDisqualified = true;
            $exclusions[] = "Rikta/Amavasya Tithi ({$tithiNum}) is strictly prohibited for Vivah.";
        } else {
            $reasons[] = "Tithi ({$tithiNum}) is auspicious for Vivah.";
            $scoreDelta += 20.0;
        }

        // 2. Auspicious Nakshatras for Marriage: Rohini, Mrigashira, Magha, Uttara Phalguni, Hasta, Swati, Anuradha, Mula, Uttara Ashadha, Uttara Bhadrapada, Revati
        $vivahNakshatras = [4, 5, 10, 12, 13, 15, 17, 19, 21, 26, 27];
        if (in_array($nakshatraNum, $vivahNakshatras, true)) {
            $scoreDelta += 35.0;
            $reasons[] = "Nakshatra ({$nakshatraNum}) is highly auspicious for Vivah.";
        } else {
            $scoreDelta -= 10.0;
            $reasons[] = "Nakshatra ({$nakshatraNum}) is neutral or ordinary.";
        }

        // 3. Auspicious Weekdays: Monday, Wednesday, Thursday, Friday
        if (in_array($varaNum, [1, 3, 4, 5], true)) {
            $scoreDelta += 20.0;
            $reasons[] = "Benefic weekday suitable for ceremonial vows.";
        } elseif ($varaNum === 2) { // Tuesday
            $isDisqualified = true;
            $exclusions[] = "Kuja-vara (Tuesday) is scripturally prohibited for Vivah solemnisation.";
        }

        // 4. Inauspicious daytime divisions check (Rahu Kaal)
        if (!empty($context['in_rahu_kaal'])) {
            $isDisqualified = true;
            $exclusions[] = "Moment falls within Rahu Kaal.";
        }

        return [
            'score_delta' => max(0.0, $scoreDelta),
            'is_disqualified' => $isDisqualified,
            'reasons' => $reasons,
            'exclusions' => $exclusions,
        ];
    }
}

