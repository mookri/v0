<?php

namespace Tests\Platform\Authz;

use Tests\TenantIntegrationTestCase;

/**
 * Role + assignment administration: tenant-scoped roles, grant/revoke on
 * memberships with cross-link protection, issuer membership required.
 */
final class RoleAdminTest extends TenantIntegrationTestCase
{
    public function test_guests_are_denied(): void
    {
        $this->getJson('/api/v1/platform/roles?tenant_id=x')->assertUnauthorized();
    }

    public function test_membership_alone_does_not_grant_role_administration(): void
    {
        // SEC-022: an active membership in the tenant is NOT an
        // authorization grant. Without platform:role:* the issuer cannot
        // read roles, and — the escalation this gates — cannot mint a role
        // and hand it to a member of their own choosing.
        $tenant = $this->makeTenant();
        [$issuer] = $this->actingAsMembership($tenant);
        $tid = (string) $tenant->getKey();
        $this->stepUp($issuer);
        $authed = fn () => $this->actingAs($issuer);

        $authed()->getJson("/api/v1/platform/roles?tenant_id={$tid}")
            ->assertForbidden()
            ->assertJsonPath('error.code', 'authorization_denied')
            ->assertJsonPath('error.details.capability', 'platform:role:view');

        $authed()->postJson('/api/v1/platform/roles', [
            'tenant_id' => $tid, 'code' => 'reviewer', 'name' => 'Reviewer',
        ])->assertForbidden()->assertJsonPath('error.code', 'authorization_denied');

        // The denial is audited like a route-level CheckCapability denial.
        $this->assertAuditRecorded($tenant, 'authz.denied');
    }

    public function test_roles_crud_and_assignment_lifecycle(): void
    {
        $tenant = $this->makeTenant();
        [$issuer] = $this->actingAsMembership($tenant, ['platform:role:view', 'platform:role:manage']);
        $tid = (string) $tenant->getKey();
        $this->stepUp($issuer);
        $authed = fn () => $this->actingAs($issuer);

        $authed()->getJson('/api/v1/platform/roles')->assertStatus(422);

        $created = $authed()->postJson('/api/v1/platform/roles', [
            'tenant_id' => $tid, 'code' => 'reviewer', 'name' => 'Reviewer',
        ])->assertCreated();
        $created->assertJsonPath('data.code', 'reviewer');

        $authed()->postJson('/api/v1/platform/roles', [
            'tenant_id' => $tid, 'code' => 'reviewer', 'name' => 'Dupe',
        ])->assertStatus(409);

        $listed = $authed()->getJson("/api/v1/platform/roles?tenant_id={$tid}")->assertOk();
        $codes = collect($listed->json('data'))->pluck('code')->all();
        // The harness grants platform:role:* through a role of its own, and
        // roles are tenant rows — so that role appears here too. Assert the
        // two properties this test owns: the created row is listed, and the
        // listing keeps its code ordering.
        $this->assertContains('reviewer', $codes);
        $this->assertSame($codes, collect($codes)->sort()->values()->all());

        $member = \App\Models\User::factory()->create();
        $membership = $this->makeMembership($tenant, $member);
        $granted = $authed()->postJson("/api/v1/platform/memberships/{$membership->getKey()}/roles", [
            'tenant_id' => $tid, 'role_code' => 'reviewer',
        ])->assertCreated();
        $assignmentId = $granted->json('data.id');

        // Idempotent re-grant.
        $authed()->postJson("/api/v1/platform/memberships/{$membership->getKey()}/roles", [
            'tenant_id' => $tid, 'role_code' => 'reviewer',
        ])->assertCreated();

        // Unknown role fails closed.
        $authed()->postJson("/api/v1/platform/memberships/{$membership->getKey()}/roles", [
            'tenant_id' => $tid, 'role_code' => 'ghost',
        ])->assertStatus(422);

        // Cross-membership revoke path leaks nothing.
        $other = $this->makeMembership($tenant);
        $authed()->deleteJson(
            "/api/v1/platform/memberships/{$other->getKey()}/roles/{$assignmentId}?tenant_id={$tid}"
        )->assertNotFound();

        $authed()->deleteJson("/api/v1/platform/memberships/{$membership->getKey()}/roles/{$assignmentId}?tenant_id={$tid}")
            ->assertOk();
    }

    public function test_issuer_without_membership_is_denied(): void
    {
        $tenant = $this->makeTenant();
        $outsider = \App\Models\User::factory()->create();

        $this->actingAs($outsider)->postJson('/api/v1/platform/roles', [
            'tenant_id' => (string) $tenant->getKey(), 'code' => 'x', 'name' => 'X',
        ])->assertForbidden()->assertJsonPath('error.code', 'membership_inactive');
    }

    public function test_role_revoke_is_step_up_gated_like_grant(): void
    {
        $tenant = $this->makeTenant();
        [$issuer] = $this->actingAsMembership($tenant, ['platform:role:view', 'platform:role:manage']);
        $tid = (string) $tenant->getKey();
        $this->stepUp($issuer);
        $authed = fn () => $this->actingAs($issuer);

        $authed()->postJson('/api/v1/platform/roles', [
            'tenant_id' => $tid, 'code' => 'reviewer', 'name' => 'Reviewer',
        ])->assertCreated();

        $member = \App\Models\User::factory()->create();
        $membership = $this->makeMembership($tenant, $member);
        $assignmentId = $authed()->postJson("/api/v1/platform/memberships/{$membership->getKey()}/roles", [
            'tenant_id' => $tid, 'role_code' => 'reviewer',
        ])->assertCreated()->json('data.id');

        // Age the proof out: revoke (privilege-altering like grant)
        // must fail without a fresh proof.
        \App\Platform\Identity\Models\MfaStepUp::query()
            ->where('user_id', $issuer->getAuthIdentifier())
            ->update(['verified_at' => gmdate('Y-m-d H:i:s', time() - 16 * 60)]);

        $revoke = fn () => $authed()->deleteJson(
            "/api/v1/platform/memberships/{$membership->getKey()}/roles/{$assignmentId}?tenant_id={$tid}"
        );
        $revoke()->assertForbidden()->assertJsonPath('error.code', 'step_up_required');

        $this->stepUp($issuer);
        $revoke()->assertOk();
    }
}
