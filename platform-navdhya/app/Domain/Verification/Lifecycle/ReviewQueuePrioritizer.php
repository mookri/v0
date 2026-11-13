<?php

namespace App\Domain\Verification\Lifecycle;

/**
 * Config-driven workload-aware & skill-aware reviewer queue prioritizer (§12.1 / T-TR08-02).
 * Never alters application outcomes; strictly computes logged queue priority and skill routing.
 */
final class ReviewQueuePrioritizer
{
    /**
     * @return array{
     *   priority_score: float,
     *   component_scores: array{risk: float, age: float, urgency: float, complexity: float},
     *   recommended_skill: string,
     *   routing_rationale: string
     * }
     */
    public function score(
        string $track,
        float $riskLevel,
        float $ageHours,
        float $urgencyLevel,
        float $complexityLevel,
    ): array {
        $weights = config('verification_queue.weights', [
            'risk' => 0.35,
            'age' => 0.25,
            'urgency' => 0.25,
            'complexity' => 0.15,
        ]);

        $r = min(1.0, max(0.0, $riskLevel));
        $a = min(1.0, max(0.0, $ageHours / 168.0)); // Normalized against 7-day SLA
        $u = min(1.0, max(0.0, $urgencyLevel));
        $c = min(1.0, max(0.0, $complexityLevel));

        $score = round(
            ((float) $weights['risk'] * $r)
            + ((float) $weights['age'] * $a)
            + ((float) $weights['urgency'] * $u)
            + ((float) $weights['complexity'] * $c),
            4,
        );

        $skills = config('verification_queue.track_skills', []);
        $skill = $skills[$track] ?? ($r >= 0.75 ? 'compliance' : 'qualification');
        if ($r >= 0.80) {
            $skill = 'compliance';
        }

        $rationale = sprintf(
            'Routed to [%s] (score=%.4f; risk=%.2f, age=%.2f, urgency=%.2f, complexity=%.2f).',
            $skill,
            $score,
            $r,
            $a,
            $u,
            $c,
        );

        return [
            'priority_score' => $score,
            'component_scores' => [
                'risk' => round($r, 4),
                'age' => round($a, 4),
                'urgency' => round($u, 4),
                'complexity' => round($c, 4),
            ],
            'recommended_skill' => $skill,
            'routing_rationale' => $rationale,
        ];
    }
}

