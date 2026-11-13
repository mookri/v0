<?php

namespace App\Modules\Recommendations\Services;

class PersonalizedPageRank
{
    private const DEFAULT_DAMPING = 0.85;
    private const MAX_ITERATIONS = 20;
    private const CONVERGENCE_EPSILON = 1e-5;

    /**
     * @var array<string, array<string, float>> Adjacency list: node => [neighbor => weight]
     */
    private array $adjacency = [];

    /**
     * Add an edge to the graph.
     */
    public function addEdge(string $from, string $to, float $weight = 1.0): self
    {
        $this->adjacency[$from][$to] = ($this->adjacency[$from][$to] ?? 0.0) + $weight;
        // Undirected graph by default for entity associations
        $this->adjacency[$to][$from] = ($this->adjacency[$to][$from] ?? 0.0) + $weight;

        return $this;
    }

    /**
     * Compute Personalized PageRank seeded from preference nodes.
     *
     * @param array<string, float> $seedPreferences [node_id => weight]
     * @param float $alpha Damping factor (default 0.85)
     * @param int $maxIterations Maximum power iteration steps (max 20)
     * @return array<string, float> Ranked nodes with personalized scores
     */
    public function compute(
        array $seedPreferences,
        float $alpha = self::DEFAULT_DAMPING,
        int $maxIterations = self::MAX_ITERATIONS
    ): array {
        $nodes = array_unique(array_merge(
            array_keys($this->adjacency),
            array_keys($seedPreferences)
        ));

        if (empty($nodes) || empty($seedPreferences)) {
            return [];
        }

        // Normalize seed preference vector (p)
        $pTotal = array_sum($seedPreferences) ?: 1.0;
        $p = [];
        foreach ($nodes as $node) {
            $p[$node] = ($seedPreferences[$node] ?? 0.0) / $pTotal;
        }

        // Initialize score vector r = p
        $r = $p;

        // Precompute out-degree sums for normalization
        $outDegrees = [];
        foreach ($this->adjacency as $u => $neighbors) {
            $outDegrees[$u] = array_sum($neighbors) ?: 1.0;
        }

        // Bounded power iteration: strictly <= 20 iterations
        $iterations = min($maxIterations, self::MAX_ITERATIONS);
        for ($iter = 0; $iter < $iterations; $iter++) {
            $rNext = [];
            foreach ($nodes as $node) {
                $rNext[$node] = (1.0 - $alpha) * $p[$node];
            }

            foreach ($this->adjacency as $u => $neighbors) {
                if (! isset($r[$u]) || $r[$u] <= 0.0) {
                    continue;
                }
                $uContribution = $alpha * $r[$u] / $outDegrees[$u];
                foreach ($neighbors as $v => $weight) {
                    $rNext[$v] = ($rNext[$v] ?? 0.0) + ($uContribution * $weight);
                }
            }

            // Check convergence
            $diff = 0.0;
            foreach ($nodes as $node) {
                $diff += abs($rNext[$node] - $r[$node]);
            }

            $r = $rNext;

            if ($diff < self::CONVERGENCE_EPSILON) {
                break;
            }
        }

        arsort($r);

        return array_map(fn ($val) => round($val, 6), $r);
    }
}

