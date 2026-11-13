<?php

namespace App\Modules\Recommendations\Services;

use App\Modules\Recommendations\Models\RecoBanditStat;

class ThompsonSamplingBandit
{
    private const PRIOR_ALPHA = 1;
    private const PRIOR_BETA = 19; // Prior expectation 5% CTR

    /**
     * Sample from Beta(alpha, beta) distribution.
     */
    public function sampleBeta(float $alpha, float $beta): float
    {
        $gammaA = $this->sampleGamma($alpha);
        $gammaB = $this->sampleGamma($beta);

        $total = $gammaA + $gammaB;
        if ($total <= 0.0) {
            return 0.0;
        }

        return $gammaA / $total;
    }

    /**
     * Marsaglia and Tsang method for Gamma(a, 1) where a >= 1.
     */
    private function sampleGamma(float $a): float
    {
        if ($a < 1.0) {
            // Johnk's generator or boost
            $u = $this->randomUniform();
            return $this->sampleGamma($a + 1.0) * pow($u, 1.0 / $a);
        }

        $d = $a - (1.0 / 3.0);
        $c = 1.0 / sqrt(9.0 * $d);

        while (true) {
            $z = $this->sampleStandardNormal();
            $v = 1.0 + ($c * $z);
            if ($v <= 0.0) {
                continue;
            }
            $v = $v * $v * $v;
            $u = $this->randomUniform();

            if ($u < 1.0 - (0.0331 * $z * $z * $z * $z)) {
                return $d * $v;
            }

            if (log($u) < (0.5 * $z * $z) + ($d * (1.0 - $v + log($v)))) {
                return $d * $v;
            }
        }
    }

    private function sampleStandardNormal(): float
    {
        // Box-Muller transform
        $u1 = max(1e-10, $this->randomUniform());
        $u2 = $this->randomUniform();

        return sqrt(-2.0 * log($u1)) * cos(2.0 * M_PI * $u2);
    }

    private function randomUniform(): float
    {
        return mt_rand(1, 1000000) / 1000001.0;
    }

    /**
     * Select best arm among candidate entity IDs via Thompson Sampling.
     *
     * @param list<array{entity_type: string, entity_id: string}> $candidates
     * @return array{entity_type: string, entity_id: string, sampled_theta: float}|null
     */
    public function selectArm(array $candidates): ?array
    {
        if (empty($candidates)) {
            return null;
        }

        $entityIds = array_column($candidates, 'entity_id');
        $stats = RecoBanditStat::whereIn('entity_id', $entityIds)
            ->get()
            ->keyBy('entity_id');

        $bestArm = null;
        $bestSample = -1.0;

        foreach ($candidates as $cand) {
            $id = $cand['entity_id'];
            $stat = $stats->get($id);

            $alpha = $stat ? (float) $stat->alpha : (float) self::PRIOR_ALPHA;
            $beta = $stat ? (float) $stat->beta : (float) self::PRIOR_BETA;

            $theta = $this->sampleBeta($alpha, $beta);

            if ($theta > $bestSample) {
                $bestSample = $theta;
                $bestArm = [
                    'entity_type' => $cand['entity_type'],
                    'entity_id' => $id,
                    'sampled_theta' => round($theta, 4),
                ];
            }
        }

        return $bestArm;
    }

    /**
     * Record outcome for an entity: click = success, unclicked impression = failure.
     */
    public function recordFeedback(string $entityType, string $entityId, bool $isClick): void
    {
        $stat = RecoBanditStat::firstOrCreate(
            ['entity_type' => $entityType, 'entity_id' => $entityId],
            ['alpha' => self::PRIOR_ALPHA, 'beta' => self::PRIOR_BETA]
        );

        if ($isClick) {
            $stat->increment('alpha');
        } else {
            $stat->increment('beta');
        }
    }

    /**
     * Inject at most 1 exploration slot into a ranked list at a specified position.
     *
     * @template T of array<string, mixed>
     * @param list<T> $rankedList
     * @param T $explorationItem
     * @param int $slotPosition Zero-based index (default 3)
     * @return list<T>
     */
    public function injectExplorationSlot(array $rankedList, array $explorationItem, int $slotPosition = 3): array
    {
        // Prevent duplicate injection
        $expId = $explorationItem['entity_id'] ?? null;
        foreach ($rankedList as $item) {
            if (isset($item['entity_id']) && $item['entity_id'] === $expId) {
                return $rankedList;
            }
        }

        $pos = min($slotPosition, count($rankedList));
        array_splice($rankedList, $pos, 0, [$explorationItem]);

        return $rankedList;
    }
}

