<?php

namespace Tests\Modules\SeoContent;

use App\Modules\SeoContent\Models\SeoPage;
use App\Modules\SeoContent\Models\SeoCluster;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeoContentModuleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate', ['--path' => 'app/Modules/SeoContent/Database/Migrations', '--database' => 'central', '--force' => true]);
    }

    public function test_seo_content_module_has_manifest_provider_and_migrations(): void
    {
        $this->assertFileExists(app_path('Modules/SeoContent/module.json'));
        $this->assertFileExists(app_path('Modules/SeoContent/ModuleServiceProvider.php'));
        $this->assertDirectoryExists(app_path('Modules/SeoContent/Database/Migrations'));
    }

    public function test_seo_pages_table_exists(): void
    {
        $tables = \DB::connection('central')->select("SELECT name FROM sqlite_master WHERE type='table' AND name='seo_pages'");
        $this->assertNotEmpty($tables);
    }

    public function test_seo_clusters_table_exists(): void
    {
        $tables = \DB::connection('central')->select("SELECT name FROM sqlite_master WHERE type='table' AND name='seo_clusters'");
        $this->assertNotEmpty($tables);
    }

    public function test_seo_page_model_can_store_and_retrieve(): void
    {
        SeoCluster::create([
            'cluster' => 'test_cluster',
            'label' => 'Test Cluster',
            'base_path' => 'test',
        ]);

        $page = SeoPage::create([
            'slug' => 'test-page',
            'cluster' => 'test_cluster',
            'noindex' => false,
            'title' => 'Test Page',
            'meta_title' => 'Test Page',
            'meta_description' => 'Test description',
            'content_hash' => 'hash123',
            'page_json' => ['title' => 'Test Page'],
            'search_text' => 'test page content',
        ]);

        $this->assertDatabaseHas('seo_pages', [
            'slug' => 'test-page',
            'cluster' => 'test_cluster',
        ]);

        $retrieved = SeoPage::find($page->id);
        $this->assertEquals('Test Page', $retrieved->title);
        $this->assertEquals('test_cluster', $retrieved->cluster);
    }

    public function test_seo_cluster_model_can_store_and_retrieve(): void
    {
        $cluster = SeoCluster::create([
            'cluster' => 'test_cluster',
            'label' => 'Test Cluster',
            'base_path' => 'test',
        ]);

        $this->assertDatabaseHas('seo_clusters', [
            'cluster' => 'test_cluster',
        ]);

        $retrieved = SeoCluster::find('test_cluster');
        $this->assertEquals('Test Cluster', $retrieved->label);
        $this->assertEquals('test', $retrieved->base_path);
    }

    public function test_seo_import_command_exists(): void
    {
        $this->assertFileExists(app_path('Modules/SeoContent/Console/SeoImportCommand.php'));
    }

    public function test_seo_api_routes_exist(): void
    {
        $routeCollection = \Illuminate\Support\Facades\Route::getRoutes();
        $found = false;
        foreach ($routeCollection as $r) {
            if (str_contains($r->uri(), 'seo/')) {
                $found = true;
                break;
            }
        }
        $this->assertTrue($found, "SEO routes should be registered");
    }
}
