<?php

namespace Tests\Modules\Recommendations;

use App\Modules\Search\Models\SearchDocument;
use Illuminate\Support\Str;
use Tests\TenantIntegrationTestCase;

class DiscoveryEndpointTest extends TenantIntegrationTestCase
{
    public function test_discovery_feed_endpoint_serves_cards_with_reason_codes_and_records_impressions(): void
    {
        $tenant = $this->makeTenant();
        $this->resolveContext($tenant);

        SearchDocument::create([
            'id' => (string) Str::ulid(),
            'entity_type' => 'puja_service',
            'entity_id' => 'puja_ganesh_01',
            'slug' => 'ganesh-puja',
            'url_path' => '/pujas/ganesh',
            'title' => 'Ganesh Chaturthi Special Puja',
            'bookings_30d' => 25,
            'views_7d' => 120,
            'city' => 'Bengaluru',
            'status' => 'ACTIVE',
        ]);

        $response = $this->postJson('/api/v1/discovery', [
            'surface' => 'home_pujas',
            'limit' => 5,
            'city' => 'Bengaluru',
        ]);

        $response->assertOk();
        $response->assertHeader('ETag');

        $data = $response->json('data');
        $this->assertSame('home_pujas', $data['surface']);
        $this->assertNotEmpty($data['items']);
        $this->assertSame('Ganesh Chaturthi Special Puja', $data['items'][0]['title']);
        $this->assertArrayHasKey('reason_code', $data['items'][0]);
        $this->assertArrayHasKey('reason_label', $data['items'][0]);

        // Verify impression was recorded in database
        $this->assertDatabaseHas('reco_impressions', [
            'surface' => 'home_pujas',
            'entity_id' => 'puja_ganesh_01',
        ], 'central');
    }

    public function test_discovery_interactions_endpoint_ingests_events(): void
    {
        $payload = [
            'interactions' => [
                [
                    'entity_type' => 'puja_service',
                    'entity_id' => 'puja_ganesh_01',
                    'action' => 'click',
                    'dwell_ms' => 450,
                ],
            ],
        ];

        $response = $this->postJson('/api/v1/discovery/interactions', $payload);
        $response->assertOk();
        $this->assertSame(1, $response->json('data.accepted'));

        $this->assertDatabaseHas('reco_interactions', [
            'entity_id' => 'puja_ganesh_01',
            'interaction_type' => 'click',
            'dwell_ms' => 450,
        ], 'central');
    }
}

