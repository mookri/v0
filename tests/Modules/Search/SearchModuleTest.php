<?php

namespace Tests\Modules\Search;

use App\Modules\Search\Models\SearchDocument;
use App\Modules\Search\Models\SearchQuery;
use App\Modules\Search\SearchIndexer;
use App\Modules\SeoContent\Models\SeoPage;
use App\Modules\SeoContent\Models\SeoCluster;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SearchModuleTest extends TestCase
{
    use RefreshDatabase;

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
        $tables = \DB::connection('central')->select("SELECT name FROM sqlite_master WHERE type='table' AND name='search_documents'");
        $this->assertNotEmpty($tables);
    }

    public function test_search_queries_table_exists(): void
    {
        $tables = \DB::connection('central')->select("SELECT name FROM sqlite_master WHERE type='table' AND name='search_queries'");
        $this->assertNotEmpty($tables);
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
}
