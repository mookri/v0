<?php

namespace Tests\Modules\Search;

use Tests\TenantIntegrationTestCase;

class SearchHealthEndpointTest extends TenantIntegrationTestCase
{
    public function test_health_endpoint_returns_system_diagnostics(): void
    {
        $response = $this->getJson('/api/v1/search/health');
        $response->assertOk();

        $data = $response->json();
        $this->assertArrayHasKey('status', $data);
        $this->assertArrayHasKey('generation', $data);
        $this->assertArrayHasKey('documents_indexed', $data);
        $this->assertArrayHasKey('dictionary_terms', $data);
        $this->assertArrayHasKey('outbox_lag', $data);
        $this->assertArrayHasKey('freshness_seconds', $data);
        $this->assertSame('healthy', $data['status']);
    }
}

