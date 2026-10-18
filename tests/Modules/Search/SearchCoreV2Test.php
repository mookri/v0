<?php

namespace Tests\Modules\Search;

use App\Modules\Search\Models\SearchDocument;
use App\Modules\Search\Models\SearchTerm;
use App\Modules\Search\SearchIndexer;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Tests\TestCase;

class SearchCoreV2Test extends TestCase
{
    use DatabaseTruncation;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate', ['--path' => 'app/Modules/Search/Database/Migrations', '--database' => 'central', '--force' => true]);
        $this->artisan('migrate', ['--path' => 'app/Modules/SeoContent/Database/Migrations', '--database' => 'central', '--force' => true]);
    }

    public function test_v2_tables_exist(): void
    {
        $this->assertTrue(\Schema::connection('central')->hasTable('search_terms'));
        $this->assertTrue(\Schema::connection('central')->hasTable('search_synonyms'));
        $this->assertTrue(\Schema::connection('central')->hasTable('search_meta'));
        $this->assertTrue(\Schema::connection('central')->hasColumn('search_documents', 'bookings_30d'));
        $this->assertTrue(\Schema::connection('central')->hasColumn('search_documents', 'locale'));
    }

    public function test_version_starts_at_zero(): void
    {
        $indexer = app(SearchIndexer::class);
        $this->assertSame(0, $indexer->getVersion());
    }

    public function test_upsert_bumps_version_and_writes_terms(): void
    {
        $indexer = app(SearchIndexer::class);
        $doc = SearchDocument::create([
            'entity_type' => 'temple',
            'entity_id' => 't-v2',
            'tenant_id' => null,
            'slug' => 't-v2',
            'url_path' => '/temples/t-v2',
            'title' => 'Ganesh Temple',
            'subtitle' => 'Pune',
            'body' => 'A temple for ganesh puja',
            'city' => 'Pune',
            'card_json' => ['verified' => true],
            'status' => 'ACTIVE',
            'indexed_at' => now(),
            'locale' => 'en',
            'bookings_30d' => 5,
            'views_7d' => 10,
        ]);

        $indexer->upsertDocument($doc);

        $this->assertGreaterThanOrEqual(1, $indexer->getVersion());
        $this->assertGreaterThan(0, SearchTerm::where('search_document_id', $doc->id)->count());
    }

    public function test_delete_bumps_version_and_removes_terms(): void
    {
        $indexer = app(SearchIndexer::class);
        $doc = SearchDocument::create([
            'entity_type' => 'temple',
            'entity_id' => 't-del',
            'tenant_id' => null,
            'slug' => 't-del',
            'url_path' => '/temples/t-del',
            'title' => 'Delete Me',
            'body' => 'body',
            'card_json' => [],
            'status' => 'ACTIVE',
            'indexed_at' => now(),
        ]);
        $indexer->upsertDocument($doc);
        $vBefore = $indexer->getVersion();

        $indexer->deleteDocument('temple', 't-del');
        $this->assertGreaterThan($vBefore, $indexer->getVersion());
        $this->assertSame(0, SearchTerm::where('search_document_id', $doc->id)->count());
    }

    public function test_reindex_command_runs(): void
    {
        $this->artisan('search:reindex', ['type' => 'seo_page', '--chunk' => '2'])
            ->assertExitCode(0);
    }
}