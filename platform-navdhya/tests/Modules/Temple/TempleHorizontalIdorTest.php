<?php

namespace Tests\Modules\Temple;

use App\Models\User;
use App\Modules\Temple\Models\Temple;
use App\Modules\Temple\Models\TempleManager;
use App\Modules\Temple\Models\TemplePuja;
use App\Platform\Tenancy\Models\Tenant;
use Tests\TenantIntegrationTestCase;

/**
 * Temple Horizontal IDOR Security Tests (C0):
 * In a multi-partner tenant environment, an authenticated partner User A managing
 * Temple 1 must never be able to view, modify, manage, or leak data from Temple 2
 * (managed by User B). All single-temple CRUD and dashboard endpoints fail closed (404).
 */
final class TempleHorizontalIdorTest extends TenantIntegrationTestCase
{
    public function test_user_a_cannot_access_or_mutate_temple_b_resources(): void
    {
        $tenant = $this->makeTenant();

        // User A (Partner A)
        $userA = User::factory()->create();
        $membershipA = $this->makeMembership($tenant, $userA);
        $this->grantCapabilities($tenant, $membershipA, ['temple:manager:manage']);

        // User B (Partner B)
        $userB = User::factory()->create();
        $membershipB = $this->makeMembership($tenant, $userB);

        // Temple 1 owned by User A
        $temple1 = Temple::factory()->create([
            'tenant_id' => (string) $tenant->getKey(),
            'owner_id' => (string) $userA->getKey(),
            'name' => 'Kashi Vishwanath Mandir',
            'status' => Temple::STATUS_ACTIVE,
            'verification_status' => Temple::VERIFICATION_VERIFIED,
        ]);
        TempleManager::forceCreate([
            'tenant_id' => (string) $tenant->getKey(),
            'temple_id' => (string) $temple1->getKey(),
            'user_id' => (string) $userA->getKey(),
            'role' => TempleManager::ROLE_OWNER,
            'invited_at' => now()->toISOString(),
            'accepted_at' => now()->toISOString(),
        ]);

        // Temple 2 owned by User B
        $temple2 = Temple::factory()->create([
            'tenant_id' => (string) $tenant->getKey(),
            'owner_id' => (string) $userB->getKey(),
            'name' => 'Meenakshi Amman Temple',
            'status' => Temple::STATUS_ACTIVE,
            'verification_status' => Temple::VERIFICATION_VERIFIED,
        ]);
        $managerB = TempleManager::forceCreate([
            'tenant_id' => (string) $tenant->getKey(),
            'temple_id' => (string) $temple2->getKey(),
            'user_id' => (string) $userB->getKey(),
            'role' => TempleManager::ROLE_OWNER,
            'invited_at' => now()->toISOString(),
            'accepted_at' => now()->toISOString(),
        ]);

        // Puja assignment on Temple 2
        $puja2 = TemplePuja::factory()->create([
            'tenant_id' => (string) $tenant->getKey(),
            'temple_id' => (string) $temple2->getKey(),
            'puja_id' => 'puja_sahasranama',
            'price_override_minor' => 25000,
            'status' => TemplePuja::STATUS_ACTIVE,
        ]);

        // Act as User A
        $this->actingAs($userA);
        $headers = $this->tenantHeaders($tenant);

        $t1Id = (string) $temple1->getKey();
        $t2Id = (string) $temple2->getKey();
        $p2Id = (string) $puja2->getKey();
        $mBId = (string) $managerB->getKey();

        // 1. Positive control: User A can access Temple 1
        $this->getJson("/api/v1/temple/partner/temples/{$t1Id}", $headers)
            ->assertOk()
            ->assertJsonPath('data.id', $t1Id);

        $this->getJson("/api/v1/temple/partner/temples/{$t1Id}/dashboard/overview", $headers)
            ->assertOk();

        $this->getJson("/api/v1/temple/partner/temples/{$t1Id}/dashboard/settings", $headers)
            ->assertOk();

        // 2. IDOR Prevention: User A accessing Temple 2 details -> 404
        $this->getJson("/api/v1/temple/partner/temples/{$t2Id}", $headers)
            ->assertNotFound()
            ->assertJsonPath('error.code', 'TEMPLE_NOT_FOUND');

        // 3. IDOR Prevention: User A updating Temple 2 profile -> 404
        $this->patchJson("/api/v1/temple/partner/temples/{$t2Id}", [
            'name' => 'Hacked Temple Name',
        ], $headers)
            ->assertNotFound()
            ->assertJsonPath('error.code', 'TEMPLE_NOT_FOUND');

        // Verify Temple 2 name was not mutated
        $this->assertSame('Meenakshi Amman Temple', $temple2->fresh()->name);

        // 4. IDOR Prevention: User A listing Temple 2 pujas -> 404
        $this->getJson("/api/v1/temple/partner/temples/{$t2Id}/pujas", $headers)
            ->assertNotFound()
            ->assertJsonPath('error.code', 'TEMPLE_NOT_FOUND');

        // 5. IDOR Prevention: User A assigning a puja to Temple 2 -> 404
        $this->postJson("/api/v1/temple/partner/temples/{$t2Id}/pujas", [
            'puja_id' => 'puja_unauthorized',
        ], $headers)
            ->assertNotFound()
            ->assertJsonPath('error.code', 'TEMPLE_NOT_FOUND');

        // 6. IDOR Prevention: User A reading Temple 2 puja -> 404
        $this->getJson("/api/v1/temple/partner/temples/{$t2Id}/pujas/{$p2Id}", $headers)
            ->assertNotFound()
            ->assertJsonPath('error.code', 'TEMPLE_PUJA_NOT_FOUND');

        // 7. IDOR Prevention: User A updating Temple 2 puja -> 404
        $this->putJson("/api/v1/temple/partner/temples/{$t2Id}/pujas/{$p2Id}", [
            'price_override_minor' => 1,
        ], $headers)
            ->assertNotFound()
            ->assertJsonPath('error.code', 'TEMPLE_PUJA_NOT_FOUND');

        // Verify price was not mutated
        $this->assertSame(25000, $puja2->fresh()->price_override_minor);

        // 8. IDOR Prevention: User A deleting Temple 2 puja -> 404
        $this->deleteJson("/api/v1/temple/partner/temples/{$t2Id}/pujas/{$p2Id}", [], $headers)
            ->assertNotFound()
            ->assertJsonPath('error.code', 'TEMPLE_PUJA_NOT_FOUND');

        $this->assertNotNull($puja2->fresh());

        // 9. IDOR Prevention: User A listing Temple 2 managers -> 404
        $this->getJson("/api/v1/temple/partner/temples/{$t2Id}/managers", $headers)
            ->assertNotFound()
            ->assertJsonPath('error.code', 'TEMPLE_NOT_FOUND');

        // 10. IDOR Prevention: User A adding manager to Temple 2 -> 404
        $randomUser = User::factory()->create();
        $this->postJson("/api/v1/temple/partner/temples/{$t2Id}/managers", [
            'user_id' => (string) $randomUser->getKey(),
            'role' => 'manager',
        ], $headers)
            ->assertNotFound()
            ->assertJsonPath('error.code', 'TEMPLE_NOT_FOUND');

        // 11. IDOR Prevention: User A updating Temple 2 manager -> 404
        $this->putJson("/api/v1/temple/partner/temples/{$t2Id}/managers/{$mBId}", [
            'role' => 'viewer',
        ], $headers)
            ->assertNotFound()
            ->assertJsonPath('error.code', 'TEMPLE_NOT_FOUND');

        // 12. IDOR Prevention: User A removing Temple 2 manager -> 404
        $this->deleteJson("/api/v1/temple/partner/temples/{$t2Id}/managers/{$mBId}", [], $headers)
            ->assertNotFound()
            ->assertJsonPath('error.code', 'TEMPLE_NOT_FOUND');

        // 13. IDOR Prevention: User A viewing Temple 2 dashboard overview -> 404
        $this->getJson("/api/v1/temple/partner/temples/{$t2Id}/dashboard/overview", $headers)
            ->assertNotFound();

        // 14. IDOR Prevention: User A viewing Temple 2 dashboard settings -> 404
        $this->getJson("/api/v1/temple/partner/temples/{$t2Id}/dashboard/settings", $headers)
            ->assertNotFound();

        // 15. IDOR Prevention: User A querying Temple 2 dashboard pujas -> 0 pujas returned
        $this->getJson("/api/v1/temple/partner/temples/{$t2Id}/dashboard/pujas", $headers)
            ->assertOk()
            ->assertJsonPath('data', []);

        // 16. IDOR Prevention: User A querying Temple 2 dashboard bookings -> 0 bookings returned
        $this->getJson("/api/v1/temple/partner/temples/{$t2Id}/dashboard/bookings", $headers)
            ->assertOk()
            ->assertJsonPath('data', []);

        // 17. IDOR Prevention: User A querying Temple 2 dashboard calendar -> 0 slots returned
        $this->getJson("/api/v1/temple/partner/temples/{$t2Id}/dashboard/calendar?year=2026&month=10", $headers)
            ->assertOk()
            ->assertJsonPath('data', []);

        // 18. IDOR Prevention: User A querying Temple 2 dashboard analytics -> 0 occupancy and peak hours
        $this->getJson("/api/v1/temple/partner/temples/{$t2Id}/dashboard/analytics?months=3", $headers)
            ->assertOk()
            ->assertJsonPath('data.occupancy_by_puja', [])
            ->assertJsonPath('data.peak_hours', []);
    }
}
