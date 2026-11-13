<?php

declare(strict_types=1);

namespace App\Domain\Jyotish\Muhurat\Rules\Occasions;

use App\Domain\Jyotish\Muhurat\Rules\OccasionRuleInterface;

final readonly class BusinessOpeningRule implements OccasionRuleInterface
{
    public function getOccasionCode(): string
    {
        return 'BUSINESS_OPENING';
    }

    public function getOccasionName(): string
    {
        return 'Commercial & Business Launch';
    }

    public function getSourceReference(): string
    {
        return 'Muhurta Chintamani, Vyavahara Prakarana, Slokas 5-18';
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

        // Exclusions: Rikta Tithis, Amavasya
        if (in_array($tithiNum, [4, 9, 14, 19, 24, 29, 30], true)) {
            $isDisqualified = true;
            $exclusions[] = "Rikta/Amavasya Tithi is prohibited for commerce expansion.";
        } else {
            $scoreDelta += 25.0;
            $reasons[] = "Tithi ({$tithiNum}) provides growth potential.";
        }

        // Nakshatras promoting trade/finance: Ashwini (1), Rohini (4), Pushya (8), Uttara Phalguni (12), Hasta (13), Chitra (14), Anuradha (17), Revati (27)
        if (in_array($nakshatraNum, [1, 4, 8, 12, 13, 14, 17, 27], true)) {
            $scoreDelta += 35.0;
            $reasons[] = "Kshipra (Swift) or Chara (Movable) Nakshatra facilitates commercial turnover.";
        }

        // Beneficial days for trade: Wednesday (Budha - commerce), Thursday (Guru - wealth), Friday (Shukra - luxury/clientele)
        if (in_array($varaNum, [3, 4, 5], true)) {
            $scoreDelta += 25.0;
            $reasons[] = "Mercury/Jupiter/Venus lorded weekday ideal for commercial ventures.";
        }

        if (!empty($context['in_rahu_kaal'])) {
            $isDisqualified = true;
            $exclusions[] = "Operation within Rahu Kaal leads to loss in trade.";
        }

        return [
            'score_delta' => max(0.0, $scoreDelta),
            'is_disqualified' => $isDisqualified,
            'reasons' => $reasons,
            'exclusions' => $exclusions,
        ];
    }
}

