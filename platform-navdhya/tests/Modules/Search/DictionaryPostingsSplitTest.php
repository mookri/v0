<?php

namespace Tests\Modules\Search;

use App\Modules\Search\Models\SearchDictionary;
use App\Modules\Search\Models\SearchDocument;
use App\Modules\Search\Models\SearchPosting;
use App\Modules\Search\Services\DictionaryBuilder;
use App\Modules\Search\Services\PrefixRangeRetriever;
use Illuminate\Support\Str;
use Tests\TenantIntegrationTestCase;

class DictionaryPostingsSplitTest extends TenantIntegrationTestCase
{
    public function test_dictionary_builder_splits_document_into_dictionary_and_postings(): void
    {
        $tenant = $this->makeTenant();
        $this->resolveContext($tenant);

        $doc = SearchDocument::create([
            'id' => (string) Str::ulid(),
            'entity_type' => 'puja_service',
            'entity_id' => (string) Str::ulid(),
            'tenant_id' => $tenant->id,
            'slug' => 'rudrabhishek-puja',
            'url_path' => '/pujas/rudrabhishek-puja',
            'title' => 'Rudrabhishek Puja at Home',
            'subtitle' => 'Vedic Shiva Rudrabhishek with milk and honey',
            'body' => 'Experience sacred Shiva worship for peace and prosperity',
            'status' => 'ACTIVE',
        ]);

        $builder = new DictionaryBuilder();
        $builder->indexDocument($doc, 1);

        // Check dictionary entries created
        $this->assertDatabaseHas('search_dictionary', [
            'tenant_id' => $tenant->id,
            'term_key' => 'rudrabhishek',
        ], 'central');

        $this->assertDatabaseHas('search_dictionary', [
            'tenant_id' => $tenant->id,
            'term_key' => 'shiva',
        ], 'central');

        $dict = SearchDictionary::where('term_key', 'rudrabhishek')->first();
        $this->assertNotNull($dict);
        $this->assertSame(1, $dict->doc_freq);
        $this->assertSame($doc->id, $dict->first_document_id);

        // Check postings created
        $postings = SearchPosting::where('search_document_id', $doc->id)->get();
        $this->assertNotEmpty($postings);

        $titlePosting = $postings->firstWhere('field', 'title');
        $this->assertNotNull($titlePosting);
        $this->assertEquals(1.0, $titlePosting->field_weight);
    }

    public function test_prefix_range_retriever_finds_terms_within_lexicographical_bound(): void
    {
        $tenant = $this->makeTenant();
        $this->resolveContext($tenant);

        $doc = SearchDocument::create([
            'id' => (string) Str::ulid(),
            'entity_type' => 'temple',
            'entity_id' => (string) Str::ulid(),
            'tenant_id' => $tenant->id,
            'slug' => 'kashi-vishwanath',
            'url_path' => '/temples/kashi-vishwanath',
            'title' => 'Kashi Vishwanath Jyotirlinga',
            'subtitle' => 'Varanasi Holy Temple',
            'status' => 'ACTIVE',
        ]);

        $builder = new DictionaryBuilder();
        $builder->indexDocument($doc, 1);

        $retriever = new PrefixRangeRetriever();
        $upper = PrefixRangeRetriever::getPrefixUpperBound('kash');
        $this->assertSame('kasi', $upper);

        $results = $retriever->retrieve('kash', $tenant->id);
        $this->assertNotEmpty($results);
        $this->assertTrue($results->contains('term_key', 'kashi'));
    }

    public function test_removing_document_postings_decrements_doc_frequency(): void
    {
        $tenant = $this->makeTenant();
        $this->resolveContext($tenant);

        $doc = SearchDocument::create([
            'id' => (string) Str::ulid(),
            'entity_type' => 'puja_service',
            'entity_id' => (string) Str::ulid(),
            'tenant_id' => $tenant->id,
            'slug' => 'satyanarayan-puja',
            'url_path' => '/pujas/satyanarayan',
            'title' => 'Satyanarayan Katha Puja',
            'status' => 'ACTIVE',
        ]);

        $builder = new DictionaryBuilder();
        $builder->indexDocument($doc, 1);

        $dict = SearchDictionary::where('term_key', 'satyanarayan')->first();
        $this->assertNotNull($dict);
        $this->assertSame(1, $dict->doc_freq);

        $builder->removeDocumentPostings($doc->id);

        $dict->refresh();
        $this->assertSame(0, $dict->doc_freq);
        $this->assertSame(0, SearchPosting::where('search_document_id', $doc->id)->count());
    }
}
