<?php

namespace Tests\Modules\Pandit;

use App\Models\User;
use App\Modules\Pandit\Models\PanditAvailability;
use App\Modules\Pandit\Models\PanditProfile;
use App\Platform\Tenancy\Models\Tenant;
use Tests\TenantIntegrationTestCase;

/**
 * Pandit browse directory (P9b): capability-free reads for any tenant
 * principal, fail-closed hiding of everything unverified/suspended, and
 * tenant isolation of every row. The kernel binds a tenant only for an
 * authenticated principal, so the directory is not anonymous — a guest
 * is refused before any route logic runs.
 */
final class PanditPublicDirectoryTest extends TenantIntegrationTestCase
{
    private function seedProfile(Tenant $tenant, array $overrides = []): PanditProfile
    {
        $this->resolveContext($tenant);

        try {
            return PanditProfile::factory()->create($overrides);
        } finally {
            $this->clearContext();
        }
    }

    private function seedAvailability(Tenant $tenant, string $panditId, int $day): PanditAvailability
    {
        $this->resolveContext($tenant);

        try {
            return PanditAvailability::factory()->create([
                'pandit_id' => $panditId,
                'day_of_week' => $day,
                'start_time' => '09:00:00',
                'end_time' => '12:00:00',
            ]);
        } finally {
            $this->clearContext();
        }
    }

    public function test_guests_are_refused_before_route_logic(): void
    {
        $tenant = $this->makeTenant();
        $headers = $this->tenantHeaders($tenant);

        // No principal: auth.any rejects before any route logic runs, so no
        // directory row can leak (401 unauthenticated, not 400/404).
        $this->getJson('/api/v1/pandit/profiles', $headers)
            ->assertUnauthorized()->assertJsonPath('error.code', 'unauthenticated');
        $this->getJson('/api/v1/pandit/profiles/01JQZZZZZZZZZZZZZZZZZZZZZZ', $headers)
            ->assertUnauthorized();
        $this->getJson('/api/v1/pandit/profiles/01JQZZZZZZZZZZZZZZZZZZZZZZ/availability', $headers)
            ->assertUnauthorized();
    }

    public function test_member_browses_only_verified_profiles(): void
    {
        $tenant = $this->makeTenant();
        $user = User::factory()->create();
        $this->makeMembership($tenant, $user);
        $this->actingAs($user);
        $headers = $this->tenantHeaders($tenant);

        $verified = $this->seedProfile($tenant);
        $pending = $this->seedProfile($tenant, [
            'verification_status' => PanditProfile::VERIFICATION_PENDING,
        ]);
        $suspended = $this->seedProfile($tenant, [
            'status' => PanditProfile::STATUS_SUSPENDED,
        ]);

        // No capability required: the browse directory is capability-free.
        $list = $this->getJson('/api/v1/pandit/profiles', $headers)->assertOk();
        $ids = array_column($list->json('data'), 'id');
        $this->assertContains((string) $verified->getKey(), $ids);
        $this->assertNotContains((string) $pending->getKey(), $ids);
        $this->assertNotContains((string) $suspended->getKey(), $ids);
        $this->assertArrayHasKey('page', $list->json('meta'));

        $this->getJson('/api/v1/pandit/profiles/'.(string) $verified->getKey(), $headers)
            ->assertOk()
            ->assertJsonPath('data.verification_status', 'VERIFIED')
            ->assertJsonPath('data.status', 'ACTIVE');

        // Unverified, suspended and unknown ids collapse to one 404 — no
        // existence oracle for profiles awaiting vetting.
        $this->getJson('/api/v1/pandit/profiles/'.(string) $pending->getKey(), $headers)
            ->assertNotFound()->assertJsonPath('error.code', 'PANDIT_NOT_FOUND');
        $this->getJson('/api/v1/pandit/profiles/'.(string) $suspended->getKey(), $headers)
            ->assertNotFound()->assertJsonPath('error.code', 'PANDIT_NOT_FOUND');
        $this->getJson('/api/v1/pandit/profiles/01JQZZZZZZZZZZZZZZZZZZZZZZ', $headers)
            ->assertNotFound()->assertJsonPath('error.code', 'PANDIT_NOT_FOUND');
    }

    public function test_directory_cursor_advances_to_the_next_page(): void
    {
        $tenant = $this->makeTenant();
        $user = User::factory()->create();
        $this->makeMembership($tenant, $user);
        $this->actingAs($user);
        $headers = $this->tenantHeaders($tenant);

        $this->seedProfile($tenant);
        $this->seedProfile($tenant);
        $this->seedProfile($tenant);

        $pageOne = $this->getJson('/api/v1/pandit/profiles?limit=2', $headers)->assertOk();
        $this->assertCount(2, $pageOne->json('data'));
        $this->assertTrue($pageOne->json('meta.page.has_more'));
        $cursor = $pageOne->json('meta.page.next_cursor');
        $this->assertIsString($cursor);

        // The advertised cursor must actually advance the window: page 2
        // holds the remaining row and never replays page 1.
        $pageTwo = $this->getJson('/api/v1/pandit/profiles?limit=2&cursor='.urlencode($cursor), $headers)
            ->assertOk();
        $this->assertCount(1, $pageTwo->json('data'));
        $this->assertFalse($pageTwo->json('meta.page.has_more'));
        $this->assertSame(
            [],
            array_values(array_intersect(
                array_column($pageOne->json('data'), 'id'),
                array_column($pageTwo->json('data'), 'id'),
            )),
        );
    }

    public function test_availability_is_equally_fail_closed(): void
    {
        $tenant = $this->makeTenant();
        $user = User::factory()->create();
        $this->makeMembership($tenant, $user);
        $this->actingAs($user);
        $headers = $this->tenantHeaders($tenant);

        $verified = $this->seedProfile($tenant);
        $pending = $this->seedProfile($tenant, [
            'verification_status' => PanditProfile::VERIFICATION_PENDING,
        ]);

        $this->seedAvailability($tenant, (string) $verified->getKey(), 2);
        $this->seedAvailability($tenant, (string) $pending->getKey(), 2);

        $open = $this->getJson('/api/v1/pandit/profiles/'.(string) $verified->getKey().'/availability', $headers)
            ->assertOk();
        $this->assertCount(1, $open->json('data'));
        $this->assertSame('09:00:00', $open->json('data.0.start_time'));

        // Rows exist, but an unverified pandit is hidden behind the same
        // 404 as a missing id.
        $this->getJson('/api/v1/pandit/profiles/'.(string) $pending->getKey().'/availability', $headers)
            ->assertNotFound()->assertJsonPath('error.code', 'PANDIT_NOT_FOUND');
        $this->getJson('/api/v1/pandit/profiles/01JQZZZZZZZZZZZZZZZZZZZZZZ/availability', $headers)
            ->assertNotFound()->assertJsonPath('error.code', 'PANDIT_NOT_FOUND');
    }

    public function test_verified_pandit_without_rows_lists_empty(): void
    {
        $tenant = $this->makeTenant();
        $user = User::factory()->create();
        $this->makeMembership($tenant, $user);
        $this->actingAs($user);
        $profile = $this->seedProfile($tenant);

        $this->getJson(
            '/api/v1/pandit/profiles/'.(string) $profile->getKey().'/availability',
            $this->tenantHeaders($tenant),
        )->assertOk()->assertJsonPath('data', []);
    }

    public function test_directory_is_tenant_scoped(): void
    {
        $tenantA = $this->makeTenant();
        $tenantB = $this->makeTenant();

        // One principal, two memberships: rows never cross the header tenant.
        $user = User::factory()->create();
        $this->makeMembership($tenantA, $user);
        $this->makeMembership($tenantB, $user);
        $this->actingAs($user);

        $id = (string) $this->seedProfile($tenantA)->getKey();

        $this->getJson('/api/v1/pandit/profiles', $this->tenantHeaders($tenantA))
            ->assertOk()->assertJsonCount(1, 'data');
        $this->getJson('/api/v1/pandit/profiles', $this->tenantHeaders($tenantB))
            ->assertOk()->assertJsonCount(0, 'data');

        $this->getJson("/api/v1/pandit/profiles/{$id}", $this->tenantHeaders($tenantB))
            ->assertNotFound()->assertJsonPath('error.code', 'PANDIT_NOT_FOUND');
        $this->getJson("/api/v1/pandit/profiles/{$id}/availability", $this->tenantHeaders($tenantB))
            ->assertNotFound()->assertJsonPath('error.code', 'PANDIT_NOT_FOUND');
    }
}
