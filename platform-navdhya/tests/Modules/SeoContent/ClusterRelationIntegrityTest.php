<?php

namespace Tests\Modules\SeoContent;

use App\Modules\Search\Contracts\SeoPageSearchProvider;
use App\Modules\SeoContent\Models\SeoCluster;
use App\Modules\SeoContent\Models\SeoPage;
use Illuminate\Support\Facades\DB;
use Tests\TenantIntegrationTestCase;

class ClusterRelationIntegrityTest extends TenantIntegrationTestCase
{
    public function test_bulk_seo_page_search_provider_avoids_n_plus_one_closes_p16(): void
    {
        SeoCluster::create([
            'cluster' => 'panchang',
            'base_path' => '/panchang',
            'label' => 'Daily Panchang',
        ]);

        for ($i = 1; $i <= 5; $i++) {
            SeoPage::create([
                'slug' => "test-page-{$i}",
                'cluster' => 'panchang',
                'noindex' => false,
                'title' => "Test Page {$i}",
                'page_json' => ['readingTime' => 3],
            ]);
        }

        $provider = new SeoPageSearchProvider();

        DB::connection('central')->enableQueryLog();
        DB::connection('central')->flushQueryLog();

        $docs = iterator_to_array($provider->getDocuments());
        $this->assertCount(5, $docs);

        $queries = DB::connection('central')->getQueryLog();
        // Without eager loading, 5 pages would issue 1 base query + 5 individual cluster queries = 6 queries.
        // With eager loading (or with('cluster')), it issues at most 2 queries: 1 for pages + 1 for cluster.
        $this->assertLessThanOrEqual(3, count($queries), 'Bulk documents indexing should not cause N+1 query regression.');
    }
}

