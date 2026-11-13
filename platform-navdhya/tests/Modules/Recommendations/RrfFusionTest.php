<?php

namespace Tests\Modules\Recommendations;

use App\Modules\Recommendations\Services\RrfCandidateCombiner;
use App\Modules\Search\Models\SearchDocument;
use Tests\TenantIntegrationTestCase;

class RrfFusionTest extends TenantIntegrationTestCase
{
    public function test_rrf_combiner_merges_multiple_ranked_lists_with_k60(): void
    {
        $docA = new SearchDocument();
        $docA->entity_type = 'puja_service';
        $docA->entity_id = 'puja_a';

        $docB = new SearchDocument();
        $docB->entity_type = 'puja_service';
        $docB->entity_id = 'puja_b';

        $docC = new SearchDocument();
        $docC->entity_type = 'puja_service';
        $docC->entity_id = 'puja_c';

        // Source 1: [A (rank 0), B (rank 1)]
        $source1 = [
            [
                'document' => $docA,
                'reason_code' => 'TRENDING_IN_CITY',
                'reason_label' => 'Trending in your city',
            ],
            [
                'document' => $docB,
                'reason_code' => 'TRENDING_IN_CITY',
                'reason_label' => 'Trending in your city',
            ],
        ];

        // Source 2: [B (rank 0), C (rank 1)]
        $source2 = [
            [
                'document' => $docB,
                'reason_code' => 'FREQUENTLY_BOOKED_TOGETHER',
                'reason_label' => 'Frequently booked together',
            ],
            [
                'document' => $docC,
                'reason_code' => 'FREQUENTLY_BOOKED_TOGETHER',
                'reason_label' => 'Frequently booked together',
            ],
        ];

        $combiner = new RrfCandidateCombiner();
        $results = $combiner->combine([
            'source1' => $source1,
            'source2' => $source2,
        ], 60);

        // Expected RRF:
        // A: 1/(60 + 1) = 1/61 = 0.016393
        // B: 1/(60 + 2) + 1/(60 + 1) = 1/62 + 1/61 = 0.016129 + 0.016393 = 0.032522
        // C: 1/(60 + 2) = 1/62 = 0.016129
        // B should be ranked #1
        $this->assertCount(3, $results);
        $this->assertSame('puja_b', $results[0]['document']->entity_id);
        $this->assertGreaterThan($results[1]['score'], $results[0]['score']);
        $this->assertSame('puja_a', $results[1]['document']->entity_id);
        $this->assertSame('puja_c', $results[2]['document']->entity_id);
    }
}

