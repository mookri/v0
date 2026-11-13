<?php

namespace Tests\Modules\Recommendations;

use App\Modules\Recommendations\Models\RecoInteraction;
use App\Modules\Recommendations\Models\RecoUserInterest;
use App\Modules\Recommendations\Models\RecoUserProfile;
use App\Modules\Search\Models\SearchQueryEvent;
use Illuminate\Support\Str;
use Tests\TenantIntegrationTestCase;

class UserDiscoveryControlsTest extends TenantIntegrationTestCase
{
    public function test_get_and_patch_discovery_preferences(): void
    {
        $userId = 5001;

        // GET default
        $res = $this->getJson('/api/v1/me/discovery-preferences', [
            'X-User-Id' => (string) $userId,
        ]);
        $res->assertOk();
        $res->assertJson([
            'user_id' => $userId,
            'personalization_enabled' => true,
        ]);

        // PATCH
        $patchRes = $this->patchJson('/api/v1/me/discovery-preferences', [
            'personalization_enabled' => false,
            'tradition' => 'smartha',
            'gotra' => 'kashyapa',
        ], [
            'X-User-Id' => (string) $userId,
        ]);

        $patchRes->assertOk();
        $patchRes->assertJson([
            'user_id' => $userId,
            'personalization_enabled' => false,
            'tradition' => 'smartha',
            'gotra' => 'kashyapa',
        ]);

        $profile = RecoUserProfile::where('user_id', $userId)->first();
        $this->assertNotNull($profile);
        $this->assertFalse((bool) $profile->personalization_enabled);
        $this->assertSame('smartha', $profile->tradition);
    }

    public function test_add_exclusion_hides_entity_without_blocking_category(): void
    {
        $userId = 5002;

        $res = $this->postJson('/api/v1/me/exclusions', [
            'entity_type' => 'pandit',
            'entity_id' => 'pandit_sharma_99',
            'reason' => 'user_dismissed',
        ], [
            'X-User-Id' => (string) $userId,
        ]);

        $res->assertStatus(201);
        $res->assertJsonPath('exclusion.entity_type', 'pandit');
        $res->assertJsonPath('exclusion.entity_id', 'pandit_sharma_99');

        $this->assertDatabaseHas('reco_exclusions', [
            'user_id' => $userId,
            'entity_type' => 'pandit',
            'entity_id' => 'pandit_sharma_99',
        ], 'central');
    }

    public function test_reset_recommendations_wipes_inferred_but_preserves_explicit_preferences(): void
    {
        $userId = 5003;

        // Inferred interest
        RecoUserInterest::create([
            'id' => (string) Str::ulid(),
            'user_id' => $userId,
            'interest_kind' => 'category',
            'interest_key' => 'rudrabhishek',
            'weight' => 1.5,
            'source' => 'inferred',
        ]);

        // Explicit interest
        RecoUserInterest::create([
            'id' => (string) Str::ulid(),
            'user_id' => $userId,
            'interest_kind' => 'tradition',
            'interest_key' => 'smartha',
            'weight' => 2.0,
            'source' => 'explicit',
        ]);

        // Interaction
        RecoInteraction::create([
            'id' => (string) Str::ulid(),
            'user_id' => $userId,
            'session_id' => 's123',
            'entity_type' => 'puja_service',
            'entity_id' => 'p1',
            'interaction_type' => 'view_detail',
            'interacted_at' => now(),
        ]);

        $res = $this->postJson('/api/v1/me/recommendations/reset', [], [
            'X-User-Id' => (string) $userId,
        ]);

        $res->assertOk();
        $res->assertJson(['reset' => true]);

        // Inferred interest should be gone
        $this->assertDatabaseMissing('reco_user_interests', [
            'user_id' => $userId,
            'source' => 'inferred',
        ], 'central');

        // Explicit interest must remain
        $this->assertDatabaseHas('reco_user_interests', [
            'user_id' => $userId,
            'source' => 'explicit',
            'interest_key' => 'smartha',
        ], 'central');

        // Interactions should be wiped
        $this->assertDatabaseMissing('reco_interactions', [
            'user_id' => $userId,
        ], 'central');
    }

    public function test_delete_search_history_removes_user_events(): void
    {
        $userId = 5004;

        SearchQueryEvent::create([
            'id' => (string) Str::ulid(),
            'trace_id' => 'trace_user_delete_1',
            'user_id' => $userId,
            'surface' => 'web_discovery',
            'query_text_raw' => 'rudrabhishek',
            'query_text_norm' => 'rudrabhishek',
            'script_family' => 'latin',
            'result_count' => 5,
        ]);

        $res = $this->deleteJson('/api/v1/me/search-history', [], [
            'X-User-Id' => (string) $userId,
        ]);

        $res->assertOk();
        $res->assertJson(['deleted' => true]);

        $this->assertDatabaseMissing('search_query_events', [
            'user_id' => $userId,
        ], 'central');
    }
}
