<?php

namespace Tests\Modules\Recommendations;

use App\Modules\Recommendations\Models\RecoInteraction;
use App\Modules\Recommendations\Models\RecoItemNeighbor;
use App\Modules\Recommendations\Services\CollaborativeFilteringAggregator;
use Illuminate\Support\Str;
use Tests\TenantIntegrationTestCase;

class ItemToItemCFTest extends TenantIntegrationTestCase
{
    public function test_collaborative_filtering_computes_neighbor_similarities(): void
    {
        // 2 sessions viewing Griha Pravesh and Vastu Puja together
        for ($s = 1; $s <= 2; $s++) {
            $sess = 'session_' . $s;

            RecoInteraction::create([
                'id' => (string) Str::ulid(),
                'session_id' => $sess,
                'entity_type' => 'puja_service',
                'entity_id' => 'puja_griha_pravesh',
                'interaction_type' => 'view_detail',
                'interacted_at' => now(),
            ]);

            RecoInteraction::create([
                'id' => (string) Str::ulid(),
                'session_id' => $sess,
                'entity_type' => 'puja_service',
                'entity_id' => 'puja_vastu_shanti',
                'interaction_type' => 'view_detail',
                'interacted_at' => now(),
            ]);
        }

        $aggregator = new CollaborativeFilteringAggregator();
        $updated = $aggregator->compute();

        $this->assertGreaterThan(0, $updated);

        $neighbor = RecoItemNeighbor::where('source_id', 'puja_griha_pravesh')
            ->where('neighbor_id', 'puja_vastu_shanti')
            ->first();

        $this->assertNotNull($neighbor);
        $this->assertSame(1.0, (float) $neighbor->sim_final);
        $this->assertSame('complementary', $neighbor->relation_kind);
    }
}

