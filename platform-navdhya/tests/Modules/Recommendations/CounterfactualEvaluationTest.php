<?php

namespace Tests\Modules\Recommendations;

use App\Modules\Recommendations\Services\IpsEvaluator;
use Tests\TestCase;

class CounterfactualEvaluationTest extends TestCase
{
    public function test_propensity_decreases_with_lower_display_positions(): void
    {
        $evaluator = new IpsEvaluator();

        $p0 = $evaluator->getPropensity(0);
        $p1 = $evaluator->getPropensity(1);
        $p5 = $evaluator->getPropensity(5);

        $this->assertEquals(1.0, $p0);
        $this->assertLessThan($p0, $p1);
        $this->assertLessThan($p1, $p5);
    }

    public function test_ips_evaluator_scores_superior_policy_higher(): void
    {
        $evaluator = new IpsEvaluator();

        // 100 logged impressions:
        // Position 0: Shown 'item_popular', clicked 20 times (20% raw CTR)
        // Position 3: Shown 'item_relevant', clicked 15 times despite low position (high intrinsic affinity!)
        $logs = [];
        for ($i = 0; $i < 100; $i++) {
            $isPos3 = ($i % 2 === 0);
            $entityId = $isPos3 ? 'item_relevant' : 'item_popular';
            $pos = $isPos3 ? 3 : 0;
            // item_relevant has high click probability (e.g. 70%), item_popular has 30%
            $clicked = $isPos3 ? ($i % 3 !== 0) : ($i % 5 === 0);

            $logs[] = [
                'session_id' => 'sess_' . $i,
                'entity_id' => $entityId,
                'position' => $pos,
                'clicked' => $clicked,
                'context' => ['user_interest' => 'shiva'],
            ];
        }

        // Policy A (Superior): Predicts ['item_relevant', 'item_other']
        $policySuperior = fn (array $ctx) => ['item_relevant', 'item_other'];

        // Policy B (Inferior): Predicts ['item_unrelated', 'item_other']
        $policyInferior = fn (array $ctx) => ['item_unrelated', 'item_other'];

        $resultSuperior = $evaluator->evaluate($logs, $policySuperior);
        $resultInferior = $evaluator->evaluate($logs, $policyInferior);

        $this->assertGreaterThan(0, $resultSuperior['ips_score']);
        $this->assertGreaterThan($resultInferior['ips_score'], $resultSuperior['ips_score']);
        $this->assertGreaterThan($resultInferior['snips_score'], $resultSuperior['snips_score']);
    }
}

