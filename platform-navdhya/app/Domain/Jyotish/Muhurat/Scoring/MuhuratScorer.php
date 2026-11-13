<?php

declare(strict_types=1);

namespace App\Domain\Jyotish\Muhurat\Scoring;

use App\Domain\Jyotish\Muhurat\Rules\OccasionRuleInterface;

final readonly class MuhuratScorer
{
    /**
     * @param OccasionRuleInterface $rule
     * @param array<string, mixed> $context
     * @return array{score: float, is_disqualified: bool, reasons: list<string>, exclusions: list<string>}
     */
    public function scoreCandidate(OccasionRuleInterface $rule, array $context): array
    {
        $evaluation = $rule->evaluate($context);

        // Base planetary or shubh muhurat bonus
        $baseScore = 20.0;
        if (!empty($context['is_abhijit'])) {
            $baseScore += 15.0;
            $evaluation['reasons'][] = 'Moment coincides with Abhijit Muhurat (universal benefic).';
        }

        if (!empty($context['choghadiya_auspicious'])) {
            $baseScore += 10.0;
            $evaluation['reasons'][] = 'Occurs in an auspicious Choghadiya period (Amrit/Shubh/Labh).';
        }

        $finalScore = min(100.0, $baseScore + $evaluation['score_delta']);

        // Hard exclusion forces score to zero
        if ($evaluation['is_disqualified']) {
            $finalScore = 0.0;
        }

        return [
            'score' => round($finalScore, 2),
            'is_disqualified' => $evaluation['is_disqualified'],
            'reasons' => $evaluation['reasons'],
            'exclusions' => $evaluation['exclusions'],
        ];
    }
}

