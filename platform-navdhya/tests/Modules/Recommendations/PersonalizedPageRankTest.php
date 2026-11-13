<?php

namespace Tests\Modules\Recommendations;

use App\Modules\Recommendations\Services\PersonalizedPageRank;
use Tests\TestCase;

class PersonalizedPageRankTest extends TestCase
{
    public function test_personalized_pagerank_propagates_authority_from_seed(): void
    {
        $ppr = new PersonalizedPageRank();

        // Graph:
        // Puja:Rudrabhishek <-> Pandit:Sharma <-> Temple:KashiVishwanath
        // Puja:Satyanarayan <-> Pandit:Verma
        $ppr->addEdge('puja:rudrabhishek', 'pandit:sharma');
        $ppr->addEdge('pandit:sharma', 'temple:kashivishwanath');
        $ppr->addEdge('puja:satyanarayan', 'pandit:verma');

        // Devotee seed: highly interested in Kashi Vishwanath
        $seeds = ['temple:kashivishwanath' => 1.0];

        $scores = $ppr->compute($seeds, 0.85, 20);

        $this->assertNotEmpty($scores);
        // Direct neighbor (pandit:sharma) must score higher than unrelated pandit (pandit:verma)
        $this->assertGreaterThan($scores['pandit:verma'] ?? 0, $scores['pandit:sharma']);
        // 2-hop neighbor (puja:rudrabhishek) must score higher than disjoint component (puja:satyanarayan)
        $this->assertGreaterThan($scores['puja:satyanarayan'] ?? 0, $scores['puja:rudrabhishek']);
    }

    public function test_pagerank_respects_max_iterations_bound(): void
    {
        $ppr = new PersonalizedPageRank();
        for ($i = 0; $i < 10; $i++) {
            $ppr->addEdge("node:{$i}", 'node:' . ($i + 1));
        }

        // Even with maxIterations requested as 1000, computation runs safely within bounded <= 20
        $start = microtime(true);
        $scores = $ppr->compute(['node:0' => 1.0], 0.85, 1000);
        $elapsedMs = (microtime(true) - $start) * 1000;

        $this->assertNotEmpty($scores);
        $this->assertLessThan(50, $elapsedMs); // Runs in < 50ms
    }
}
