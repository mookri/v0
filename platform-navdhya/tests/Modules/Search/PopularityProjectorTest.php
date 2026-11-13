<?php

namespace Tests\Modules\Search;

use App\Modules\Search\Models\SearchDictionary;
use App\Modules\Search\Models\SearchDocument;
use App\Modules\Search\Models\SearchImpression;
use App\Modules\Search\Models\SearchSelection;
use App\Modules\Search\Services\PopularityProjector;
use Illuminate\Support\Str;
use Tests\TenantIntegrationTestCase;

class PopularityProjectorTest extends TenantIntegrationTestCase
{
    public function test_popularity_projector_computes_bayesian_popularity_from_telemetry(): void
    {
        $tenant = $this->makeTenant();
        $this->resolveContext($tenant);

        $doc = SearchDocument::create([
            'id' => (string) Str::ulid(),
            'entity_type' => 'puja_service',
            'entity_id' => 'puja_rudra_01',
            'tenant_id' => $tenant->id,
            'slug' => 'rudrabhishek',
            'url_path' => '/pujas/rudrabhishek',
            'title' => 'Rudrabhishek',
            'status' => 'ACTIVE',
        ]);

        SearchDictionary::create([
            'id' => (string) Str::ulid(),
            'tenant_id' => $tenant->id,
            'locale' => 'en',
            'scheme' => 'latin_fold',
            'term_key' => 'rudrabhishek',
            'key_len' => 12,
            'doc_freq' => 1,
            'popularity' => 0,
        ]);

        // Record 10 impressions and 4 clicks
        for ($i = 0; $i < 10; $i++) {
            SearchImpression::create([
                'id' => (string) Str::ulid(),
                'trace_id' => (string) Str::ulid(),
                'entity_type' => 'puja_service',
                'entity_id' => 'puja_rudra_01',
                'impressed_at' => now(),
            ]);
        }

        for ($i = 0; $i < 4; $i++) {
            SearchSelection::create([
                'id' => (string) Str::ulid(),
                'trace_id' => (string) Str::ulid(),
                'entity_type' => 'puja_service',
                'entity_id' => 'puja_rudra_01',
                'selected_at' => now(),
            ]);
        }

        $projector = new PopularityProjector();
        $stats = $projector->project();

        $this->assertGreaterThan(0, $stats['documents_updated']);

        $doc->refresh();
        $this->assertSame(10, $doc->views_7d);
        $this->assertNotNull($doc->popularity_updated_at);

        $dict = SearchDictionary::where('term_key', 'rudrabhishek')->first();
        $this->assertNotNull($dict);
        $this->assertGreaterThan(0, $dict->popularity);
    }
}
