<?php

namespace Tests\Modules\Search;

use App\Modules\Search\Models\SearchTerm;
use Illuminate\Support\Str;
use Tests\TenantIntegrationTestCase;

class TrendingQueriesTest extends TenantIntegrationTestCase
{
    public function test_trending_endpoint_serves_precomputed_popular_queries(): void
    {
        $tenant = $this->makeTenant();
        $this->resolveContext($tenant);

        // Precompute popular query terms
        SearchTerm::create([
            'id' => (string) Str::ulid(),
            'term' => 'Satyanarayan Puja',
            'normalized' => 'satyanarayan puja',
            'kind' => 'popular_query',
            'weight' => 9.5,
            'locale' => 'en',
        ]);

        SearchTerm::create([
            'id' => (string) Str::ulid(),
            'term' => 'Rudrabhishek',
            'normalized' => 'rudrabhishek',
            'kind' => 'popular_query',
            'weight' => 8.2,
            'locale' => 'en',
        ]);

        $response = $this->getJson('/api/v1/search/trending');
        $response->assertOk();

        $data = $response->json('data.trending');
        $this->assertNotEmpty($data);
        $this->assertSame('Satyanarayan Puja', $data[0]['query']);
        $this->assertSame('Rudrabhishek', $data[1]['query']);
    }
}

