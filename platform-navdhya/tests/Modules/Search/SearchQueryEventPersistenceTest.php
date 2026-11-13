<?php

namespace Tests\Modules\Search;

use App\Modules\Search\Models\SearchQueryEvent;
use App\Modules\Search\Support\PiiScrubber;
use App\Modules\Search\Support\SearchDimensionRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class SearchQueryEventPersistenceTest extends TestCase
{
    use RefreshDatabase;

    public function test_telemetry_tables_exist_on_central(): void
    {
        $this->assertTrue(Schema::connection('central')->hasTable('search_query_events'));
        $this->assertTrue(Schema::connection('central')->hasTable('search_impressions'));
        $this->assertTrue(Schema::connection('central')->hasTable('search_selections'));
    }

    public function test_pii_scrubber_redacts_sensitive_data(): void
    {
        $dirty = 'Puja for test@example.com with phone 9876543210 and card 1234-5678-9012-3456';
        $clean = PiiScrubber::scrub($dirty);

        $this->assertStringNotContainsString('test@example.com', $clean);
        $this->assertStringNotContainsString('9876543210', $clean);
        $this->assertStringNotContainsString('1234-5678-9012-3456', $clean);
        $this->assertStringContainsString('[EMAIL]', $clean);
        $this->assertStringContainsString('[PHONE]', $clean);
        $this->assertStringContainsString('[REDACTED_NUM]', $clean);
    }

    public function test_script_detection_recognizes_scripts(): void
    {
        $this->assertSame('latin', SearchDimensionRegistry::detectScript('satyanarayan puja'));
        $this->assertSame('devanagari', SearchDimensionRegistry::detectScript('सत्यनारायण पूजा'));
        $this->assertSame('iast', SearchDimensionRegistry::detectScript('rudrābhiṣeka'));
        $this->assertSame('mixed', SearchDimensionRegistry::detectScript('satyanarayan पूजा'));
    }

    public function test_search_endpoint_records_search_query_event(): void
    {
        $response = $this->getJson('/api/v1/search?q=rudrabhishek');
        $response->assertStatus(200);

        $this->assertDatabaseHas('search_query_events', [
            'query_text_raw' => 'rudrabhishek',
            'script_family' => 'latin',
        ], 'central');

        $event = SearchQueryEvent::where('query_text_raw', 'rudrabhishek')->first();
        $this->assertNotNull($event);
        $this->assertNotNull($event->trace_id);
        $this->assertGreaterThanOrEqual(0, $event->stage_recall_ms);
        $this->assertGreaterThanOrEqual(0, $event->stage_rank_ms);
        $this->assertGreaterThanOrEqual(0, $event->stage_total_ms);
    }

    public function test_analytics_endpoint_ingests_impressions_and_selections(): void
    {
        $payload = [
            'queries' => [
                [
                    'q' => 'griha pravesh',
                    'results' => 4,
                    'trace_id' => 'trace_123',
                ],
            ],
            'impressions' => [
                [
                    'trace_id' => 'trace_123',
                    'entity_type' => 'puja_service',
                    'entity_id' => 'puja_griha_pravesh',
                    'position' => 0,
                    'dwell_ms' => 450,
                    'viewport_ratio' => 0.75,
                ],
            ],
            'selections' => [
                [
                    'trace_id' => 'trace_123',
                    'entity_type' => 'puja_service',
                    'entity_id' => 'puja_griha_pravesh',
                    'position' => 0,
                    'action' => 'click',
                    'dwell_before_click_ms' => 1200,
                ],
            ],
        ];

        $response = $this->postJson('/api/v1/search/analytics', $payload);
        $response->assertStatus(200);
        $response->assertJson([
            'data' => [
                'accepted' => 1,
                'accepted_impressions' => 1,
                'accepted_selections' => 1,
            ],
        ]);

        $this->assertDatabaseHas('search_query_events', [
            'trace_id' => 'trace_123',
            'query_text_norm' => 'griha pravesh',
        ], 'central');

        $this->assertDatabaseHas('search_impressions', [
            'trace_id' => 'trace_123',
            'entity_type' => 'puja_service',
            'entity_id' => 'puja_griha_pravesh',
            'dwell_ms' => 450,
        ], 'central');

        $this->assertDatabaseHas('search_selections', [
            'trace_id' => 'trace_123',
            'entity_type' => 'puja_service',
            'entity_id' => 'puja_griha_pravesh',
            'action' => 'click',
        ], 'central');
    }
}
