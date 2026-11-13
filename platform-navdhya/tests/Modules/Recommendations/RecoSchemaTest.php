<?php

namespace Tests\Modules\Recommendations;

use App\Modules\Recommendations\Models\RecoImpression;
use App\Modules\Recommendations\Models\RecoItemNeighbor;
use App\Modules\Recommendations\Models\RecoUserProfile;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\TenantIntegrationTestCase;

class RecoSchemaTest extends TenantIntegrationTestCase
{
    public function test_recommendation_tables_exist_on_central_connection(): void
    {
        $this->assertTrue(Schema::connection('central')->hasTable('reco_impressions'));
        $this->assertTrue(Schema::connection('central')->hasTable('reco_interactions'));
        $this->assertTrue(Schema::connection('central')->hasTable('reco_item_neighbors'));
        $this->assertTrue(Schema::connection('central')->hasTable('reco_transitions'));
        $this->assertTrue(Schema::connection('central')->hasTable('reco_user_profiles'));
        $this->assertTrue(Schema::connection('central')->hasTable('reco_user_interests'));
        $this->assertTrue(Schema::connection('central')->hasTable('reco_exclusions'));
    }

    public function test_recommendation_models_persist_with_integer_user_id(): void
    {
        $userId = 1042; // Unsigned big integer devotee identity

        $imp = RecoImpression::create([
            'id' => (string) Str::ulid(),
            'surface' => 'home_pujas',
            'user_id' => $userId,
            'entity_type' => 'puja_service',
            'entity_id' => 'puja_rudra_01',
            'position' => 1,
            'reason_code' => 'POPULAR_IN_CATEGORY',
            'impressed_at' => now(),
        ]);
        $this->assertDatabaseHas('reco_impressions', ['id' => $imp->id, 'user_id' => $userId], 'central');

        $neighbor = RecoItemNeighbor::create([
            'id' => (string) Str::ulid(),
            'source_type' => 'puja_service',
            'source_id' => 'puja_rudra_01',
            'neighbor_type' => 'puja_service',
            'neighbor_id' => 'puja_mahamrityunjaya_02',
            'sim_final' => 0.875,
            'relation_kind' => 'complementary',
        ]);
        $this->assertDatabaseHas('reco_item_neighbors', ['id' => $neighbor->id], 'central');

        $profile = RecoUserProfile::create([
            'id' => (string) Str::ulid(),
            'user_id' => $userId,
            'tradition' => 'PURNIMANTA',
            'gotra' => 'Kashyapa',
            'preferred_language' => 'hi',
        ]);
        $this->assertDatabaseHas('reco_user_profiles', ['user_id' => $userId, 'tradition' => 'PURNIMANTA'], 'central');
    }
}
