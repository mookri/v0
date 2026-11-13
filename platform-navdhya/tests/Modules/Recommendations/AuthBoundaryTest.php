<?php

namespace Tests\Modules\Recommendations;

use App\Modules\Recommendations\Models\RecoUserProfile;
use App\Modules\Search\Models\SearchDocument;
use Illuminate\Support\Str;
use Tests\TenantIntegrationTestCase;

class AuthBoundaryTest extends TenantIntegrationTestCase
{
    public function test_cold_start_for_anonymous_user_serves_popular_items_without_fabricating_profiles(): void
    {
        SearchDocument::create([
            'id' => (string) Str::ulid(),
            'document_id' => 'doc_p1',
            'entity_type' => 'puja_service',
            'entity_id' => 'puja_rudra',
            'slug' => 'rudrabhishek',
            'url_path' => '/pujas/rudrabhishek',
            'title' => 'Rudrabhishek',
            'bookings_30d' => 10,
            'views_7d' => 50,
            'is_active' => true,
        ]);

        $initialProfilesCount = RecoUserProfile::count();

        // Anonymous request without X-User-Id
        $res = $this->postJson('/api/v1/discovery', [
            'surface' => 'home_pujas',
        ]);

        $res->assertOk();
        $this->assertNotEmpty($res->json('data.items'));
        $this->assertSame('puja_rudra', $res->json('data.items.0.entity_id'));

        // Assert no fake user profile was created
        $this->assertSame($initialProfilesCount, RecoUserProfile::count());
    }

    public function test_logout_and_anonymous_transition_leaves_zero_residual_personalization(): void
    {
        $userId = 6001;

        // Devotee has personalized profile
        RecoUserProfile::create([
            'user_id' => $userId,
            'tradition' => 'smartha',
            'gotra' => 'kashyapa',
            'personalization_enabled' => true,
        ]);

        // Personalized request
        $resUser = $this->postJson('/api/v1/discovery', [
            'surface' => 'home_pujas',
        ], [
            'X-User-Id' => (string) $userId,
        ]);
        $resUser->assertOk();

        // User logs out: Next request has new anonymous session and NO user_id
        $resAnon = $this->postJson('/api/v1/discovery', [
            'surface' => 'home_pujas',
        ], [
            'X-Session-Id' => 'fresh_anon_session_' . Str::random(8),
        ]);
        $resAnon->assertOk();

        // Assert that the items returned to anonymous user do not leak user-specific reasons
        $items = $resAnon->json('data.items');
        foreach ($items as $item) {
            $this->assertNotSame('TRADITION_MATCH', $item['reason_code']);
        }
    }
}

