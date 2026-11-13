<?php

namespace Tests\Modules\Search;

use App\Modules\Pandit\Models\PanditProfile;
use App\Modules\Pandit\Models\PanditServiceArea;
use App\Modules\Search\Contracts\PanditSearchProvider;
use App\Modules\Search\Models\SearchDocument;
use App\Modules\Search\Models\SearchRegion;
use App\Modules\Search\Support\RegionResolver;
use Database\Seeders\SearchRegionsSeeder;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\TenantIntegrationTestCase;

class RegionResolverTest extends TenantIntegrationTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        RegionResolver::clearCache();
        $this->seed(SearchRegionsSeeder::class);
    }

    public function test_search_regions_table_exists_and_is_seeded(): void
    {
        $this->assertTrue(Schema::connection('central')->hasTable('search_regions'));
        $this->assertDatabaseHas('search_regions', ['name_en' => 'Varanasi'], 'central');
        $this->assertDatabaseHas('search_regions', ['name_en' => 'Haridwar'], 'central');
        $this->assertDatabaseHas('search_regions', ['name_en' => 'Bengaluru'], 'central');
    }

    public function test_region_resolver_resolves_all_aliases_to_canonical_model(): void
    {
        $v1 = RegionResolver::resolve('Varanasi');
        $this->assertNotNull($v1);
        $this->assertSame('Varanasi', $v1->name_en);

        $v2 = RegionResolver::resolve('Banaras');
        $this->assertNotNull($v2);
        $this->assertSame($v1->id, $v2->id);

        $v3 = RegionResolver::resolve('Benares');
        $this->assertNotNull($v3);
        $this->assertSame($v1->id, $v3->id);

        $v4 = RegionResolver::resolve('Kashi');
        $this->assertNotNull($v4);
        $this->assertSame($v1->id, $v4->id);

        $v5 = RegionResolver::resolve('वाराणसी');
        $this->assertNotNull($v5);
        $this->assertSame($v1->id, $v5->id);

        $v6 = RegionResolver::resolve('बनारस');
        $this->assertNotNull($v6);
        $this->assertSame($v1->id, $v6->id);
    }

    public function test_pandit_search_provider_resolves_locality_from_service_areas(): void
    {
        $this->assertTrue(Schema::connection('tenant_shared')->hasTable('pandit_service_areas'));

        $varanasi = SearchRegion::where('name_en', 'Varanasi')->firstOrFail();

        $tenant = $this->makeTenant();
        $this->resolveContext($tenant);

        $pandit = PanditProfile::create([
            'id' => (string) Str::ulid(),
            'tenant_id' => $tenant->getKey(),
            'user_id' => 999123,
            'display_name' => 'Pandit Ramesh Shastri',
            'status' => PanditProfile::STATUS_ACTIVE,
            'verification_status' => 'VERIFIED',
            'timezone' => 'Asia/Kolkata',
            'currency' => 'INR',
            'market' => 'IN',
        ]);

        PanditServiceArea::create([
            'id' => (string) Str::ulid(),
            'tenant_id' => $tenant->getKey(),
            'pandit_id' => $pandit->id,
            'region_id' => $varanasi->id,
            'region_name' => 'Kashi',
            'is_primary' => true,
            'max_distance_km' => 20,
            'is_active' => true,
        ]);

        $provider = new PanditSearchProvider();
        $doc = $provider->getDocument($pandit->id);

        $this->assertInstanceOf(SearchDocument::class, $doc);
        $this->assertSame('Varanasi', $doc->city);
        $this->assertSame($varanasi->id, $doc->region_id);
        $this->assertNotNull($doc->source_updated_at);
        $this->assertNotNull($doc->keys_json);
        $this->assertSame('Pandit Ramesh Shastri', $doc->keys_json['display']);
    }
}
