<?php

namespace Tests\Modules\Recommendations;

use App\Modules\Recommendations\Models\RecoItemNeighbor;
use App\Modules\Recommendations\Services\MmrReranker;
use App\Modules\Search\Models\SearchDocument;
use Illuminate\Support\Str;
use Tests\TenantIntegrationTestCase;

class MmrRerankerTest extends TenantIntegrationTestCase
{
    public function test_mmr_reranker_promotes_diversity_and_penalizes_substitutes(): void
    {
        $doc1 = new SearchDocument();
        $doc1->entity_type = 'puja_service';
        $doc1->entity_id = 'griha_pravesh_standard';
        $doc1->subtitle = 'Griha Pravesh';

        $doc2 = new SearchDocument();
        $doc2->entity_type = 'puja_service';
        $doc2->entity_id = 'griha_pravesh_premium'; // Substitute for doc1
        $doc2->subtitle = 'Griha Pravesh';

        $doc3 = new SearchDocument();
        $doc3->entity_type = 'temple';
        $doc3->entity_id = 'temple_somnath';
        $doc3->subtitle = 'Jyotirlinga';

        // Candidates ordered by relevance
        $candidates = [
            [
                'document' => $doc1,
                'score' => 1.0,
                'reason_code' => 'TRENDING_IN_CITY',
                'reason_label' => 'Trending in your city',
            ],
            [
                'document' => $doc2,
                'score' => 0.95,
                'reason_code' => 'TRENDING_IN_CITY',
                'reason_label' => 'Trending in your city',
            ],
            [
                'document' => $doc3,
                'score' => 0.80,
                'reason_code' => 'SACRED_PILGRIMAGE',
                'reason_label' => 'Sacred pilgrimage temple',
            ],
        ];

        $reranker = new MmrReranker();
        // With lambda = 0.70, doc1 is chosen first.
        // For 2nd item:
        // doc2: MMR = 0.7 * (0.95/1.0) - 0.3 * 0.85 = 0.665 - 0.255 = 0.410
        // doc3: MMR = 0.7 * (0.80/1.0) - 0.3 * 0.10 = 0.560 - 0.030 = 0.530
        // Therefore, doc3 (diverse temple) must be picked ahead of doc2 (substitute)!
        $reranked = $reranker->rerank($candidates, 2, 0.70);

        $this->assertCount(2, $reranked);
        $this->assertSame('griha_pravesh_standard', $reranked[0]['document']->entity_id);
        $this->assertSame('temple_somnath', $reranked[1]['document']->entity_id);
    }

    public function test_mmr_reranker_respects_complementary_neighbor_relation(): void
    {
        $doc1 = new SearchDocument();
        $doc1->entity_type = 'puja_service';
        $doc1->entity_id = 'p1';

        $doc2 = new SearchDocument();
        $doc2->entity_type = 'puja_service';
        $doc2->entity_id = 'p2';

        // Precompute complementary relationship (e.g. puja + havan samagri kit)
        RecoItemNeighbor::create([
            'id' => (string) Str::ulid(),
            'source_type' => 'puja_service',
            'source_id' => 'p1',
            'neighbor_type' => 'puja_service',
            'neighbor_id' => 'p2',
            'sim_cooccurrence' => 0.9,
            'sim_text' => 0.8,
            'sim_final' => 0.85,
            'relation_kind' => 'complementary',
        ]);

        $candidates = [
            [
                'document' => $doc1,
                'score' => 1.0,
                'reason_code' => 'MATCH',
                'reason_label' => 'Match',
            ],
            [
                'document' => $doc2,
                'score' => 0.90,
                'reason_code' => 'COMPLEMENTARY',
                'reason_label' => 'Complementary',
            ],
        ];

        $reranker = new MmrReranker();
        $reranked = $reranker->rerank($candidates, 2, 0.70);

        $this->assertCount(2, $reranked);
        $this->assertSame('p1', $reranked[0]['document']->entity_id);
        $this->assertSame('p2', $reranked[1]['document']->entity_id);
    }
}
