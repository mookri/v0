<?php

namespace Tests\Modules\Search;

use App\Modules\Search\Models\SearchQueryEvent;
use App\Modules\Search\Models\SearchQueryPattern;
use App\Modules\Search\Services\PatternAggregator;
use Illuminate\Support\Str;
use Tests\TenantIntegrationTestCase;

class PatternAggregationTest extends TenantIntegrationTestCase
{
    public function test_pattern_aggregator_materializes_pattern_families(): void
    {
        $tenant = $this->makeTenant();
        $this->resolveContext($tenant);

        $sessionId = (string) Str::ulid();

        // 1. Successful query
        SearchQueryEvent::create([
            'id' => (string) Str::ulid(),
            'trace_id' => (string) Str::ulid(),
            'session_id' => $sessionId,
            'surface' => 'web_search_page',
            'query_text_raw' => 'Rudrabhishek',
            'query_text_norm' => 'rudrabhishek',
            'script_family' => 'Latin',
            'result_count' => 5,
            'stage_total_ms' => 12,
            'searched_at' => now()->subMinutes(10),
        ]);

        // 2. Reformulation within 30s in same session
        SearchQueryEvent::create([
            'id' => (string) Str::ulid(),
            'trace_id' => (string) Str::ulid(),
            'session_id' => $sessionId,
            'surface' => 'web_search_page',
            'query_text_raw' => 'Rudrabhishek Puja',
            'query_text_norm' => 'rudrabhishek puja',
            'script_family' => 'Latin',
            'result_count' => 3,
            'stage_total_ms' => 15,
            'searched_at' => now()->subMinutes(10)->addSeconds(25),
        ]);

        // 3. Cold query (repeated zero-result search)
        for ($i = 0; $i < 2; $i++) {
            SearchQueryEvent::create([
                'id' => (string) Str::ulid(),
                'trace_id' => (string) Str::ulid(),
                'session_id' => (string) Str::ulid(),
                'surface' => 'web_search_page',
                'query_text_raw' => 'NonExistentRitual123',
                'query_text_norm' => 'nonexistentritual123',
                'script_family' => 'Latin',
                'result_count' => 0,
                'zero_result_reason' => 'no_token',
                'stage_total_ms' => 8,
                'searched_at' => now()->subMinutes(5),
            ]);
        }

        $aggregator = new PatternAggregator();
        $result = $aggregator->aggregate(now()->subDay(), now());

        $this->assertGreaterThan(0, $result['patterns_created']);

        // Assert script distribution pattern
        $this->assertDatabaseHas('search_query_patterns', [
            'pattern_type' => 'script_distribution',
            'pattern_key' => 'Latin',
        ], 'central');

        // Assert cold query pattern
        $this->assertDatabaseHas('search_query_patterns', [
            'pattern_type' => 'cold_query',
            'pattern_key' => 'nonexistentritual123',
            'sample_count' => 2,
        ], 'central');

        // Assert reformulation chain
        $this->assertDatabaseHas('search_query_patterns', [
            'pattern_type' => 'reformulation',
            'pattern_key' => 'rudrabhishek -> rudrabhishek puja',
        ], 'central');

        // Assert latency histogram
        $hist = SearchQueryPattern::where('pattern_type', 'latency_histogram')->first();
        $this->assertNotNull($hist);
        $this->assertArrayHasKey('p50', $hist->metrics_json);
    }
}

