<?php

namespace Tests\Modules\Search;

use App\Modules\Search\Models\SearchDocument;
use App\Modules\Search\Services\DiversityReranker;
use PHPUnit\Framework\TestCase;

class DiversityRerankerTest extends TestCase
{
    public function test_diversity_reranker_caps_single_entity_type_at_half_slots(): void
    {
        $reranker = new DiversityReranker();

        $candidates = [];
        // 8 puja services
        for ($i = 1; $i <= 8; $i++) {
            $doc = new SearchDocument([
                'entity_type' => 'puja_service',
                'entity_id' => 'puja_' . $i,
                'title' => 'Puja ' . $i,
            ]);
            $candidates[] = ['doc' => $doc, 'score' => 10.0 - $i];
        }
        // 4 temples
        for ($i = 1; $i <= 4; $i++) {
            $doc = new SearchDocument([
                'entity_type' => 'temple',
                'entity_id' => 'temple_' . $i,
                'title' => 'Temple ' . $i,
            ]);
            $candidates[] = ['doc' => $doc, 'score' => 5.0 - $i];
        }

        // Limit = 10 -> max 5 puja services allowed
        $results = $reranker->rerank($candidates, 10, null);

        $this->assertCount(9, $results); // 5 pujas + 4 temples

        $typeCounts = array_count_values(array_map(fn ($r) => $r['doc']->entity_type, $results));
        $this->assertLessThanOrEqual(5, $typeCounts['puja_service']);
        $this->assertSame(4, $typeCounts['temple']);
    }

    public function test_diversity_reranker_bypasses_capping_when_type_filter_is_present(): void
    {
        $reranker = new DiversityReranker();

        $candidates = [];
        for ($i = 1; $i <= 8; $i++) {
            $doc = new SearchDocument([
                'entity_type' => 'temple',
                'entity_id' => 'temple_' . $i,
                'title' => 'Temple ' . $i,
            ]);
            $candidates[] = ['doc' => $doc, 'score' => 10.0 - $i];
        }

        // Explicit filter 'temple'
        $results = $reranker->rerank($candidates, 6, 'temple');
        $this->assertCount(6, $results);
        foreach ($results as $r) {
            $this->assertSame('temple', $r['doc']->entity_type);
        }
    }
}
