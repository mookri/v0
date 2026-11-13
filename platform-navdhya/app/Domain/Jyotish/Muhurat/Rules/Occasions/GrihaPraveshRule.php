<?php

declare(strict_types=1);

namespace App\Domain\Jyotish\Muhurat\Rules\Occasions;

use App\Domain\Jyotish\Muhurat\Rules\OccasionRuleInterface;

final readonly class GrihaPraveshRule implements OccasionRuleInterface
{
    public function getOccasionCode(): string
    {
        return 'GRIHA_PRAVESH';
    }

    public function getOccasionName(): string
    {
        return 'Griha Pravesh (Housewarming Ceremony)';
    }

    public function getSourceReference(): string
    {
        return 'Muhurta Martanda, Griha Pravesh Prakarana; Vastu Ratnavali Ch. 4';
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

        // Exclusions: Amavasya (30), Rikta (4, 9, 14)
        if (in_array($tithiNum, [4, 9, 14, 19, 24, 29, 30], true)) {
            $isDisqualified = true;
            $exclusions[] = "Rikta/Amavasya Tithi ({$tithiNum}) is prohibited for entering a new dwelling.";
        } else {
            $scoreDelta += 25.0;
            $reasons[] = "Favourable Tithi ({$tithiNum}) for entry.";
        }

        // Fixed/Auspicious Nakshatras: Rohini (4), Mrigashira (5), Uttara Phalguni (12), Chitra (14), Anuradha (17), Uttara Ashadha (21), Uttara Bhadrapada (26), Revati (27)
        if (in_array($nakshatraNum, [4, 5, 12, 14, 17, 21, 26, 27], true)) {
            $scoreDelta += 35.0;
            $reasons[] = "Dhruva (Fixed) or Mridu (Gentle) Nakshatra ({$nakshatraNum}) grants stability to the home.";
        }

        // Favourable days: Monday, Wednesday, Thursday, Friday
        if (in_array($varaNum, [1, 3, 4, 5], true)) {
            $scoreDelta += 20.0;
            $reasons[] = "Soumya Vara (benefic day) supports Griha Pravesh.";
        } elseif (in_array($varaNum, [2, 6], true)) { // Tuesday or Saturday
            $isDisqualified = true;
            $exclusions[] = "Tuesday/Saturday entry leads to unrest according to Vastu classical texts.";
        }

        if (!empty($context['in_rahu_kaal'])) {
            $isDisqualified = true;
            $exclusions[] = "Moment falls within Rahu Kaal window.";
        }

        return [
            'score_delta' => max(0.0, $scoreDelta),
            'is_disqualified' => $isDisqualified,
            'reasons' => $reasons,
            'exclusions' => $exclusions,
        ];
    }
}

