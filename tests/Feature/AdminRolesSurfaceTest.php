<?php

namespace Tests\Feature;

use App\Models\User;
use App\Platform\Identity\Models\MfaStepUp;
use Tests\TenantIntegrationTestCase;

/**
 * Membership role surfaces (plan slice 7, doc 06 §7 + §7.5): the
 * assignments read endpoint (the console roles panel's data source) plus
 * the shared grant/revoke actions behind the step-up gate — web form posts
 * negotiate a redirect + coded flash, API envelopes stay untouched (the
 * full suite is the parity pin). Cross-tenant membership reads fail closed
 * as 404, the same vocabulary as a plain miss.
 */
final class AdminRolesSurfaceTest extends TenantIntegrationTestCase
{
    public function test_guests_cannot_read_assignments(): void
    {
        $this->getJson('/api/v1/platform/memberships/x/roles?tenant_id=x')
            ->assertUnauthorized();
    }

    public function test_assignments_endpoint_scopes_to_membership_and_tenant(): void
    {
        $tenant = $this->makeTenant();
        [$issuer] = $this->actingAsMembership($tenant);
        $tid = (string) $tenant->getKey();
        $this->stepUp($issuer);
        $authed = fn () => $this->actingAs($issuer);

        // Missing tenant_id fails closed, like roles index.
        $authed()->getJson('/api/v1/platform/memberships/x/roles')->assertStatus(422);

        $authed()->postJson('/api/v1/platform/roles', [
            'tenant_id' => $tid, 'code' => 'reviewer', 'name' => 'Reviewer',
        ])->assertCreated();

        $member = User::factory()->create();
        $membership = $this->makeMembership($tenant, $member);
        $authed()->postJson("/api/v1/platform/memberships/{$membership->getKey()}/roles", [
            'tenant_id' => $tid, 'role_code' => 'reviewer',
        ])->assertCreated();

        $listed = $authed()
            ->getJson("/api/v1/platform/memberships/{$membership->getKey()}/roles?tenant_id={$tid}")
            ->assertOk();
        $this->assertSame(['reviewer'], collect($listed->json('data'))->pluck('role_code')->all());
        $this->assertSame((string) $membership->getKey(), $listed->json('data.0.membership_id'));
        $this->assertSame('role_assignment', $listed->json('data.0.object'));

        // Cross-tenant membership: 404 — a miss, not a probe oracle.
        $foreign = $this->makeMembership($this->makeTenant());
        $authed()
            ->getJson("/api/v1/platform/memberships/{$foreign->getKey()}/roles?tenant_id={$tid}")
            ->assertNotFound();
    }

    public function test_role_grant_via_web_shares_the_action_and_gate(): void
    {
        $tenant = $this->makeTenant();
        [$issuer] = $this->actingAsMembership($tenant);
        $tid = (string) $tenant->getKey();

        $this->actingAs($issuer)->postJson('/api/v1/platform/roles', [
            'tenant_id' => $tid, 'code' => 'reviewer', 'name' => 'Reviewer',
        ])->assertCreated();

        $member = User::factory()->create();
        $membership = $this->makeMembership($tenant, $member);

        $grant = fn () => $this->withHeader('Referer', 'http://localhost/platform/users')
            ->actingAs($issuer)
            ->post("/platform/memberships/{$membership->getKey()}/roles", [
                'tenant_id' => $tid,
                'role_code' => 'reviewer',
            ]);

        // No fresh proof → the step-up gate holds (403 page), no write.
        $grant()->assertForbidden();
        $this->assertDatabaseCount('membership_role_assignments', 0, 'central');

        $this->stepUp($issuer);
        $grant()
            ->assertRedirect('http://localhost/platform/users')
            ->assertSessionHas('flash', 'Role granted.');
        $this->assertDatabaseCount('membership_role_assignments', 1, 'central');

        // Returned envelopes (422 unknown role) negotiate to flash_error —
        // no write, same validator as the API path.
        $this->withHeader('Referer', 'http://localhost/platform/users')
            ->actingAs($issuer)
            ->post("/platform/memberships/{$membership->getKey()}/roles", [
                'tenant_id' => $tid,
                'role_code' => 'ghost',
            ])
            ->assertRedirect('http://localhost/platform/users')
            ->assertSessionHas('flash_error', 'validation_failed: Unknown or disabled role.');
        $this->assertDatabaseCount('membership_role_assignments', 1, 'central');
    }

    public function test_role_revoke_via_web_shares_the_action_and_gate(): void
    {
        $tenant = $this->makeTenant();
        [$issuer] = $this->actingAsMembership($tenant);
        $tid = (string) $tenant->getKey();
        $this->stepUp($issuer);
        $authed = fn () => $this->actingAs($issuer);

        $authed()->postJson('/api/v1/platform/roles', [
            'tenant_id' => $tid, 'code' => 'reviewer', 'name' => 'Reviewer',
        ])->assertCreated();

        $member = User::factory()->create();
        $membership = $this->makeMembership($tenant, $member);
        $assignmentId = $authed()
            ->postJson("/api/v1/platform/memberships/{$membership->getKey()}/roles", [
                'tenant_id' => $tid, 'role_code' => 'reviewer',
            ])
            ->assertCreated()
            ->json('data.id');

        // Age the proof out: revoke (privilege-altering like grant) must
        // fail without a fresh proof — mirrored on the web surface.
        MfaStepUp::query()
            ->where('user_id', $issuer->getAuthIdentifier())
            ->update(['verified_at' => gmdate('Y-m-d H:i:s', time() - 16 * 60)]);

        $revoke = fn () => $this->withHeader('Referer', 'http://localhost/platform/users')
            ->actingAs($issuer)
            ->post("/platform/memberships/{$membership->getKey()}/roles/{$assignmentId}", [
                'tenant_id' => $tid,
                '_method' => 'DELETE',
            ]);

        $revoke()->assertForbidden();
        $this->assertDatabaseCount('membership_role_assignments', 1, 'central');

        $this->stepUp($issuer);
        $revoke()
            ->assertRedirect('http://localhost/platform/users')
            ->assertSessionHas('flash', 'Role revoked.');
        $this->assertDatabaseCount('membership_role_assignments', 0, 'central');
    }

    public function test_users_page_renders_the_roles_panel(): void
    {
        $this->withoutVite();

        $tenant = $this->makeTenant();
        [$user] = $this->actingAsMembership($tenant);

        $this->actingAs($user)->get('/platform/users')
            ->assertOk()
            ->assertSee('id="user-roles-card"', false)
            ->assertSee('id="user-roles-list"', false)
            ->assertSee('id="user-roles-grant"', false);
    }
}
