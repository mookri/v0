<?php

namespace Tests\Modules\Search;

use App\Modules\Search\Models\SearchDocument;
use Illuminate\Support\Str;
use Tests\TenantIntegrationTestCase;

class SqliteLikeEscapingTest extends TenantIntegrationTestCase
{
    public function test_queries_with_percent_and_underscore_are_escaped_in_like_recall(): void
    {
        $tenant = $this->makeTenant();
        $this->resolveContext($tenant);

        // Create doc with literal %
        SearchDocument::create([
            'id' => (string) Str::ulid(),
            'entity_type' => 'puja_service',
            'entity_id' => (string) Str::ulid(),
            'tenant_id' => $tenant->id,
            'slug' => 'puja-100-percent-pure',
            'url_path' => '/pujas/100-percent',
            'title' => '100% Pure Vedic Samagri Puja',
            'subtitle' => 'Guaranteed authentic rituals',
            'status' => 'ACTIVE',
        ]);

        // Create normal doc
        SearchDocument::create([
            'id' => (string) Str::ulid(),
            'entity_type' => 'puja_service',
            'entity_id' => (string) Str::ulid(),
            'tenant_id' => $tenant->id,
            'slug' => 'regular-puja',
            'url_path' => '/pujas/regular',
            'title' => 'Regular Puja Service',
            'status' => 'ACTIVE',
        ]);

        $response = $this->withHeaders(['X-Tenant-Id' => $tenant->id])->getJson('/api/v1/search?q=100%25');
        $response->assertOk();

        $results = $response->json('data.results');
        $this->assertCount(1, $results);
        $this->assertSame('100% Pure Vedic Samagri Puja', $results[0]['title']);
    }
}
