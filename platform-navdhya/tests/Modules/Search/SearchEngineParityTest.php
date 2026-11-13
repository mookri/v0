<?php

namespace Tests\Modules\Search;

use App\Modules\Search\Models\SearchDocument;
use App\Modules\Search\Services\SearchEngine;
use Illuminate\Support\Str;
use Tests\TenantIntegrationTestCase;

class SearchEngineParityTest extends TenantIntegrationTestCase
{
    public function test_search_engine_returns_ranked_results_and_execution_plan(): void
    {
        $tenant = $this->makeTenant();
        $this->resolveContext($tenant);

        SearchDocument::create([
            'id' => (string) Str::ulid(),
            'entity_type' => 'puja_service',
            'entity_id' => (string) Str::ulid(),
            'tenant_id' => $tenant->id,
            'slug' => 'satyanarayan-katha',
            'url_path' => '/pujas/satyanarayan',
            'title' => 'Satyanarayan Katha Puja',
            'subtitle' => 'Sacred Katha ceremony',
            'status' => 'ACTIVE',
        ]);

        $engine = app(SearchEngine::class);
        $result = $engine->search('Satyanarayan', $tenant->id, null, null, 'en', 10, true);

        $this->assertNotEmpty($result['results']);
        $this->assertSame('Satyanarayan Katha Puja', $result['results'][0]['title']);
        $this->assertNotNull($result['plan']);
        $this->assertArrayHasKey('explain', $result['results'][0]);
    }
}
