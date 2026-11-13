<?php

namespace Tests\Modules\Recommendations;

use App\Modules\Recommendations\Models\RecoExclusion;
use App\Modules\Recommendations\Services\ContentTaxonomyCandidateSource;
use App\Modules\Recommendations\Services\PopularityCandidateSource;
use App\Modules\Search\Models\SearchDocument;
use Illuminate\Support\Str;
use Tests\TenantIntegrationTestCase;

class CandidateSourcesTest extends TenantIntegrationTestCase
{
    public function test_popularity_candidate_source_returns_trending_items_and_filters_exclusions(): void
    {
        $userId = 2055;

        // Create 2 documents
        $doc1 = SearchDocument::create([
            'id' => (string) Str::ulid(),
            'entity_type' => 'puja_service',
            'entity_id' => 'puja_rudra',
            'slug' => 'rudrabhishek',
            'url_path' => '/pujas/rudrabhishek',
            'title' => 'Rudrabhishek',
            'bookings_30d' => 15,
            'views_7d' => 80,
            'status' => 'ACTIVE',
        ]);

        $doc2 = SearchDocument::create([
            'id' => (string) Str::ulid(),
            'entity_type' => 'puja_service',
            'entity_id' => 'puja_satyanarayan',
            'slug' => 'satyanarayan',
            'url_path' => '/pujas/satyanarayan',
            'title' => 'Satyanarayan',
            'bookings_30d' => 5,
            'views_7d' => 20,
            'status' => 'ACTIVE',
        ]);

        // User excluded doc1
        RecoExclusion::create([
            'id' => (string) Str::ulid(),
            'user_id' => $userId,
            'entity_type' => 'puja_service',
            'entity_id' => 'puja_rudra',
            'reason' => 'booked_recently',
        ]);

        $source = app(PopularityCandidateSource::class);
        $candidates = $source->getCandidates('home_pujas', $userId, null, null, 10);

        // doc1 must be excluded
        $this->assertCount(1, $candidates);
        $this->assertSame('puja_satyanarayan', $candidates[0]['document']->entity_id);
    }

    public function test_content_taxonomy_source_matches_user_tradition(): void
    {
        $doc = SearchDocument::create([
            'id' => (string) Str::ulid(),
            'entity_type' => 'puja_service',
            'entity_id' => 'puja_shravan',
            'slug' => 'shravan-somwar',
            'url_path' => '/pujas/shravan',
            'title' => 'Shravan Somwar Puja',
            'body' => 'Performed according to sacred PURNIMANTA calendar traditions',
            'status' => 'ACTIVE',
        ]);

        $source = app(ContentTaxonomyCandidateSource::class);
        $candidates = $source->getCandidates(null, null, 'PURNIMANTA', 5);

        $this->assertNotEmpty($candidates);
        $this->assertSame('SAME_TRADITION', $candidates[0]['reason_code']);
    }
}

