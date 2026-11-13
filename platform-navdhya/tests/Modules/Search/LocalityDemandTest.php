<?php

namespace Tests\Modules\Search;

use App\Modules\Search\Models\SearchLocalityDemand;
use App\Modules\Search\Models\SearchQueryEvent;
use App\Modules\Search\Models\SearchRegion;
use App\Modules\Search\Services\PatternAggregator;
use Illuminate\Support\Str;
use Tests\TenantIntegrationTestCase;

class LocalityDemandTest extends TenantIntegrationTestCase
{
    public function test_locality_demand_maps_supply_gap_and_unserved_rate(): void
    {
        $tenant = $this->makeTenant();
        $this->resolveContext($tenant);

        $region = SearchRegion::create([
            'id' => (string) Str::ulid(),
            'code' => 'IN-UP-VARANASI',
            'name_en' => 'Varanasi',
            'name_hi' => 'वाराणसी',
            'state_en' => 'Uttar Pradesh',
            'tier' => 1,
            'is_spiritual_hub' => true,
        ]);

        // 3 searches in Varanasi, 2 empty
        for ($i = 0; $i < 3; $i++) {
            SearchQueryEvent::create([
                'id' => (string) Str::ulid(),
                'trace_id' => (string) Str::ulid(),
                'surface' => 'web_search_page',
                'query_text_raw' => 'Ganga Aarti',
                'query_text_norm' => 'ganga aarti',
                'region_code' => 'Varanasi',
                'result_count' => $i === 0 ? 2 : 0,
                'searched_at' => now()->subMinutes(15),
            ]);
        }

        $aggregator = new PatternAggregator();
        $result = $aggregator->aggregate(now()->subDay(), now());

        $this->assertGreaterThan(0, $result['demand_rows']);

        $demand = SearchLocalityDemand::where('region_name', 'Varanasi')->first();
        $this->assertNotNull($demand);
        $this->assertSame($region->id, $demand->region_id);
        $this->assertSame(3, $demand->total_searches);
        $this->assertSame(2, $demand->empty_searches);
        $this->assertEquals(66.67, $demand->unserved_rate);
    }
}

