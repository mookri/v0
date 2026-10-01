<?php

namespace Tests\Platform\Identity;

use Tests\TenantIntegrationTestCase;

/**
 * User lifecycle administration: tenant-scoped listing, suspend/activate
 * with terminal-state protection, all behind user capabilities.
 */
final class UserAdminTest extends TenantIntegrationTestCase
{
    public function test_guests_are_denied(): void
    {
        $this->getJson('/api/v1/platform/users?tenant_id=x')->assertUnauthorized();
    }

    public function test_list_suspend_activate_cycle(): void
    {
        $tenant = $this->makeTenant();
        [$admin] = $this->actingAsMembership($tenant);
        $membership = \App\Platform\Tenancy\Models\TenantMembership::query()
            ->where('tenant_id', (string) $tenant->getKey())
            ->where('user_id', $admin->id)
            ->firstOrFail();
        $this->grantCapabilities($tenant, $membership, ['platform:user:view', 'platform:user:manage']);
        $tid = (string) $tenant->getKey();
        $headers = $this->tenantHeaders($tenant);
        $authed = fn () => $this->actingAs($admin);

        $listed = $authed()->getJson("/api/v1/platform/users?tenant_id={$tid}", $headers)->assertOk();
        $this->assertSame(1, count($listed->json('data')));
        $this->assertSame('active', $listed->json('data.0.user.status'));

        $target = \App\Models\User::factory()->create();
        $this->makeMembership($tenant, $target);

        $suspended = $authed()->postJson("/api/v1/platform/users/{$target->id}:suspend", ['tenant_id' => $tid], $headers)
            ->assertOk();
        $suspended->assertJsonPath('data.status', 'suspended');

        // Terminal-state guard: deactivation (set by offboarding flows)
        // can never be reversed through these endpoints.
        $authed()->postJson("/api/v1/platform/users/{$target->id}:activate", ['tenant_id' => $tid], $headers)
            ->assertOk()
            ->assertJsonPath('data.status', 'active');

        $authed()->postJson('/api/v1/platform/users/999999:suspend', ['tenant_id' => $tid], $headers)
            ->assertNotFound();

        // Cross-tenant admin is denied even with the capability.
        $other = $this->makeTenant();
        $authed()->postJson("/api/v1/platform/users/{$target->id}:suspend", ['tenant_id' => (string) $other->getKey()], $headers)
            ->assertForbidden();
    }

    public function test_without_capability_is_denied_and_audited(): void
    {
        $tenant = $this->makeTenant();
        [$user] = $this->actingAsMembership($tenant);
        $tid = (string) $tenant->getKey();
        $headers = $this->tenantHeaders($tenant);

        $this->actingAs($user)->getJson("/api/v1/platform/users?tenant_id={$tid}", $headers)
            ->assertForbidden();
        $this->actingAs($user)->postJson("/api/v1/platform/users/1:suspend", ['tenant_id' => $tid], $headers)
            ->assertForbidden();
    }
}
