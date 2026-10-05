<?php

namespace Tests\Modules\Temple;

use App\Models\User;
use App\Modules\Temple\Models\Temple;
use App\Platform\Tenancy\Models\Tenant;
use Tests\TenantIntegrationTestCase;

/**
 * Temple public directory (P11a + P11i): capability-free reads for any
 * tenant principal, fail-closed hiding of everything unverified or
 * non-active on every public read (list and detail agree on one
 * posture), and tenant isolation of every row. The kernel binds a
 * tenant only for an authenticated principal, so the directory is not
 * anonymous — a guest is refused before any route logic runs.
 */
final class TemplePublicSurfaceTest extends TenantIntegrationTestCase
{
    /**
     * @param  array<string, mixed>  $overrides
     */
    private function seedTemple(Tenant $tenant, array $overrides = []): Temple
    {
        return Temple::factory()->create([
            'tenant_id' => (string) $tenant->getKey(),
            'status' => Temple::STATUS_ACTIVE,
            'verification_status' => Temple::VERIFICATION_VERIFIED,
            ...$overrides,
        ]);
    }

    /**
     * @return array{0: Tenant, 1: array<string, string>}
     */
    private function actingInTenant(): array
    {
        $tenant = $this->makeTenant();
        $user = User::factory()->create();
        $this->makeMembership($tenant, $user);
        $this->actingAs($user);

        return [$tenant, $this->tenantHeaders($tenant)];
    }

    public function test_guests_are_refused_before_route_logic(): void
    {
        $tenant = $this->makeTenant();
        $headers = $this->tenantHeaders($tenant);

        // No principal: auth.any rejects before any route logic runs, so no
        // directory row can leak (401 unauthenticated, not 400/404).
        $this->getJson('/api/v1/temple/profiles', $headers)
            ->assertUnauthorized()->assertJsonPath('error.code', 'unauthenticated');
        $this->getJson('/api/v1/temple/profiles/01JQZZZZZZZZZZZZZZZZZZZZZZ', $headers)
            ->assertUnauthorized();
    }

    public function test_member_browses_only_verified_temples(): void
    {
        [$tenant, $headers] = $this->actingInTenant();

        $verified = $this->seedTemple($tenant);
        $pending = $this->seedTemple($tenant, [
            'verification_status' => Temple::VERIFICATION_PENDING,
        ]);
        $inactive = $this->seedTemple($tenant, [
            'status' => Temple::STATUS_INACTIVE,
        ]);

        // No capability required: the browse directory is capability-free.
        $list = $this->getJson('/api/v1/temple/profiles', $headers)->assertOk();
        $ids = array_column($list->json('data'), 'id');
        $this->assertContains((string) $verified->getKey(), $ids);
        $this->assertNotContains((string) $pending->getKey(), $ids);
        $this->assertNotContains((string) $inactive->getKey(), $ids);
        $this->assertArrayHasKey('page', $list->json('meta'));

        $this->getJson('/api/v1/temple/profiles/'.(string) $verified->getKey(), $headers)
            ->assertOk()
            ->assertJsonPath('data.verification_status', 'VERIFIED')
            ->assertJsonPath('data.status', 'ACTIVE');

        // Unverified, non-active and unknown ids collapse to one 404 — the
        // detail must not become an existence oracle for profiles awaiting
        // vetting (P11i closes the P11a gap).
        $this->getJson('/api/v1/temple/profiles/'.(string) $pending->getKey(), $headers)
            ->assertNotFound()->assertJsonPath('error.code', 'TEMPLE_NOT_FOUND');
        $this->getJson('/api/v1/temple/profiles/'.(string) $inactive->getKey(), $headers)
            ->assertNotFound()->assertJsonPath('error.code', 'TEMPLE_NOT_FOUND');
        $this->getJson('/api/v1/temple/profiles/01JQZZZZZZZZZZZZZZZZZZZZZZ', $headers)
            ->assertNotFound()->assertJsonPath('error.code', 'TEMPLE_NOT_FOUND');
    }

    public function test_directory_is_tenant_scoped(): void
    {
        $tenantA = $this->makeTenant();
        $foreign = $this->seedTemple($tenantA);

        [, $headers] = $this->actingInTenant();

        $list = $this->getJson('/api/v1/temple/profiles', $headers)->assertOk();
        $this->assertNotContains((string) $foreign->getKey(), array_column($list->json('data'), 'id'));

        $this->getJson('/api/v1/temple/profiles/'.(string) $foreign->getKey(), $headers)
            ->assertNotFound()->assertJsonPath('error.code', 'TEMPLE_NOT_FOUND');
    }
}
