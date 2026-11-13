<?php

namespace Tests\Modules\Search;

use App\Modules\Search\Models\SearchDictionary;
use App\Modules\Search\Services\DictionaryBuilder;
use App\Modules\Search\Services\FuzzyNeighborhoodMatcher;
use Illuminate\Support\Str;
use Tests\TenantIntegrationTestCase;

class DeleteNeighborhoodFuzzyTest extends TenantIntegrationTestCase
{
    public function test_deletion_neighborhood_generates_one_and_two_edit_forms(): void
    {
        $builder = new DictionaryBuilder();

        // 1. Term of length 5 generates 5 1-edit deletions (no 2-edit because len < 7)
        $builder->indexDeletions('rudra', 'latin_fold');

        $this->assertDatabaseHas('search_deletes', [
            'original_key' => 'rudra',
            'delete_form' => 'udra',
            'edits' => 1,
        ], 'central');
        $this->assertDatabaseHas('search_deletes', [
            'original_key' => 'rudra',
            'delete_form' => 'rudr',
            'edits' => 1,
        ], 'central');

        // 2. Term of length >= 7 generates both 1-edit and 2-edit deletions
        $builder->indexDeletions('rudrabhishek', 'latin_fold');

        $this->assertDatabaseHas('search_deletes', [
            'original_key' => 'rudrabhishek',
            'edits' => 1,
        ], 'central');
        $this->assertDatabaseHas('search_deletes', [
            'original_key' => 'rudrabhishek',
            'edits' => 2,
        ], 'central');
    }

    public function test_fuzzy_matcher_resolves_typos_within_two_edits(): void
    {
        $tenant = $this->makeTenant();
        $this->resolveContext($tenant);

        // Seed dictionary entry
        SearchDictionary::create([
            'id' => (string) Str::ulid(),
            'tenant_id' => $tenant->id,
            'locale' => 'en',
            'scheme' => 'latin_fold',
            'term_key' => 'rudrabhishek',
            'key_len' => 12,
            'doc_freq' => 5,
            'popularity' => 10,
        ]);

        $builder = new DictionaryBuilder();
        $builder->indexDeletions('rudrabhishek', 'latin_fold');

        $matcher = new FuzzyNeighborhoodMatcher();

        // Query with 1 typo edit: "rudrabishek" (missing 'h')
        $results1 = $matcher->match('rudrabishek', $tenant->id, 'latin_fold', 2);
        $this->assertNotEmpty($results1);
        $this->assertSame('rudrabhishek', $results1[0]['term']);
        $this->assertSame(1, $results1[0]['distance']);

        // Query with 2 typo edits: "rudrabishekk" (missing 'h' + extra 'k')
        $results2 = $matcher->match('rudrabishekk', $tenant->id, 'latin_fold', 2);
        $this->assertNotEmpty($results2);
        $this->assertSame('rudrabhishek', $results2[0]['term']);
        $this->assertSame(2, $results2[0]['distance']);
    }

    public function test_did_you_mean_suggests_corrected_query(): void
    {
        $tenant = $this->makeTenant();
        $this->resolveContext($tenant);

        SearchDictionary::create([
            'id' => (string) Str::ulid(),
            'tenant_id' => $tenant->id,
            'locale' => 'en',
            'scheme' => 'latin_fold',
            'term_key' => 'satyanarayan',
            'key_len' => 12,
            'doc_freq' => 10,
            'popularity' => 20,
        ]);

        $builder = new DictionaryBuilder();
        $builder->indexDeletions('satyanarayan', 'latin_fold');

        $matcher = new FuzzyNeighborhoodMatcher();

        $suggested = $matcher->didYouMean('satyanarayn', $tenant->id);
        $this->assertSame('satyanarayan', $suggested);
    }
}
