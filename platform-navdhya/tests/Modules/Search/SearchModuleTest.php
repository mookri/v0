<?php

namespace Tests\Modules\Search;

use App\Modules\Search\Models\SearchDocument;
use App\Modules\Search\Models\SearchQuery;
use App\Modules\Search\SearchIndexer;
use App\Modules\SeoContent\Models\SeoPage;
use App\Modules\SeoContent\Models\SeoCluster;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Tests\TestCase;

class SearchModuleTest extends TestCase
{
    // Truncation, not RefreshDatabase's wrapping transaction: InnoDB
    // FULLTEXT cannot see uncommitted rows (proven on MySQL 8 — a row
    // inserted inside a transaction is invisible to MATCH AGAINST until
    // commit), so transactional seeding would make every FULLTEXT
    // assertion silently return zero results on MySQL while passing on
    // sqlite. Truncated seeding commits immediately on both drivers.
    use DatabaseTruncation;

    protected function setUp(): void
    {
        parent::setUp();
        // Run migrations for central connection
        $this->artisan('migrate', ['--path' => 'app/Modules/Search/Database/Migrations', '--database' => 'central', '--force' => true]);
        $this->artisan('migrate', ['--path' => 'app/Modules/SeoContent/Database/Migrations', '--database' => 'central', '--force' => true]);
    }

    public function test_search_module_has_manifest_provider_and_migrations(): void
    {
        $this->assertFileExists(app_path('Modules/Search/module.json'));
        $this->assertFileExists(app_path('Modules/Search/ModuleServiceProvider.php'));
        $this->assertDirectoryExists(app_path('Modules/Search/Database/Migrations'));
    }

    public function test_search_documents_table_exists(): void
    {
        $this->assertTrue(\Schema::connection('central')->hasTable('search_documents'));
    }

    public function test_search_queries_table_exists(): void
    {
        $this->assertTrue(\Schema::connection('central')->hasTable('search_queries'));
    }

    public function test_search_indexer_can_be_resolved(): void
    {
        $indexer = app(SearchIndexer::class);
        $this->assertInstanceOf(SearchIndexer::class, $indexer);
    }

    public function test_search_documents_model_can_store_and_retrieve(): void
    {
        $doc = SearchDocument::create([
            'entity_type' => 'seo_page',
            'entity_id' => 'test-slug',
            'tenant_id' => null,
            'slug' => 'test-slug',
            'url_path' => '/test/test-slug',
            'title' => 'Test Page',
            'subtitle' => 'Test subtitle',
            'body' => 'Test body content',
            'city' => null,
            'card_json' => json_encode(['test' => true]),
            'status' => 'ACTIVE',
            'indexed_at' => now(),
        ]);

        $this->assertDatabaseHas('search_documents', [
            'entity_type' => 'seo_page',
            'entity_id' => 'test-slug',
        ]);

        $retrieved = SearchDocument::find($doc->id);
        $this->assertEquals('Test Page', $retrieved->title);
        $this->assertEquals('ACTIVE', $retrieved->status);
    }

    public function test_search_queries_model_can_store_and_retrieve(): void
    {
        $query = SearchQuery::create([
            'query_hash' => 'abc123',
            'query_text' => 'test query',
            'entity_type' => 'seo_page',
            'city' => null,
            'result_count' => 5,
            'zero_results' => false,
            'searched_at' => now(),
        ]);

        $this->assertDatabaseHas('search_queries', [
            'query_hash' => 'abc123',
            'query_text' => 'test query',
        ]);

        $retrieved = SearchQuery::find($query->id);
        $this->assertEquals('test query', $retrieved->query_text);
        $this->assertEquals(5, $retrieved->result_count);
        $this->assertFalse($retrieved->zero_results);
    }

    public function test_search_indexer_reindex_returns_stats(): void
    {
        // Create some SEO pages first
        SeoCluster::create([
            'cluster' => 'test_cluster',
            'label' => 'Test Cluster',
            'base_path' => 'test',
        ]);

        SeoPage::create([
            'slug' => 'test-page-1',
            'cluster' => 'test_cluster',
            'noindex' => false,
            'title' => 'Test Page 1',
            'meta_title' => 'Test Page 1',
            'meta_description' => 'Description 1',
            'content_hash' => 'hash1',
            'page_json' => ['title' => 'Test Page 1'],
            'search_text' => 'test page one content',
        ]);

        SeoPage::create([
            'slug' => 'test-page-2',
            'cluster' => 'test_cluster',
            'noindex' => false,
            'title' => 'Test Page 2',
            'meta_title' => 'Test Page 2',
            'meta_description' => 'Description 2',
            'content_hash' => 'hash2',
            'page_json' => ['title' => 'Test Page 2'],
            'search_text' => 'test page two content',
        ]);

        $indexer = app(SearchIndexer::class);
        $stats = $indexer->reindex('seo_page');

        $this->assertArrayHasKey('total', $stats);
        $this->assertArrayHasKey('upserted', $stats);
        $this->assertArrayHasKey('deleted', $stats);
        $this->assertGreaterThanOrEqual(2, $stats['total']);
        $this->assertGreaterThanOrEqual(2, $stats['upserted']);
    }

    public function test_search_api_routes_exist(): void
    {
        $routeCollection = \Illuminate\Support\Facades\Route::getRoutes();
        $found = false;
        foreach ($routeCollection as $r) {
            if (str_contains($r->uri(), 'search/') || str_contains($r->uri(), 'seo/')) {
                $found = true;
                break;
            }
        }
        $this->assertTrue($found, "Search/SEO routes should be registered");
    }

    public function test_search_endpoints_answer_anonymously(): void
    {
        // The web BFF calls these without a session and without a tenant
        // header (public discovery + autocomplete), so the module routes must
        // opt out of auth.any/tenant.
        SearchDocument::create([
            'entity_type' => 'temple',
            'entity_id' => 'temple-1',
            'tenant_id' => null,
            'slug' => 'shree-ganesh-temple',
            'url_path' => '/temples/shree-ganesh-temple',
            'title' => 'Ganesh Temple Pune',
            'subtitle' => 'Pune',
            'body' => 'A well known temple for Ganesh chaturthi pujas.',
            'city' => 'Pune',
            'card_json' => ['verified' => true, 'city' => 'Pune'],
            'status' => 'ACTIVE',
            'indexed_at' => now(),
        ]);

        $this->getJson('/api/v1/search?q=Ganesh')
            ->assertOk()
            ->assertJsonPath('data.results.0.slug', 'shree-ganesh-temple');

        $this->getJson('/api/v1/search/suggest?q=Ga')
            ->assertOk()
            ->assertJsonStructure(['data' => ['suggestions' => [['type', 'label', 'items']]]]);

        $this->getJson('/api/v1/search/entities/temple/shree-ganesh-temple')
            ->assertOk()
            ->assertJsonPath('data.slug', 'shree-ganesh-temple');

        $this->getJson('/api/v1/search/trending')->assertOk();
    }

    public function test_search_analytics_ingest_accepts_anonymous_batch(): void
    {
        $this->postJson('/api/v1/search/analytics', [
            'queries' => [
                ['q' => 'ganesh puja', 'results' => 3],
                ['q' => 'unknown ritual xyz', 'results' => 0],
            ],
        ])
            ->assertOk()
            ->assertJsonPath('data.accepted', 2);

        $this->assertDatabaseCount('search_queries', 2);
    }

    public function test_search_analytics_rejects_oversized_batch(): void
    {
        $queries = array_fill(0, 101, ['q' => 'spam', 'results' => 1]);

        $this->postJson('/api/v1/search/analytics', ['queries' => $queries])
            ->assertStatus(422);
    }
}
