<?php

namespace Tests\Platform\Identity;

use App\Platform\Authz\Models\Role;
use App\Platform\Identity\Models\Invite;
use Tests\TenantIntegrationTestCase;

/**
 * Track-scoped invites: one endpoint family for every persona. Tokens are
 * hashes (plaintext once), acceptance is atomic (user + membership + role
 * grants + track seed), failures share one shape (no oracle).
 */
final class InviteTest extends TenantIntegrationTestCase
{
    private function seedReviewerRole(\App\Platform\Tenancy\Models\Tenant $tenant): Role
    {
        $this->resolveContext($tenant);
        try {
            $role = new Role();
            $role->forceFill(['code' => 'reviewer', 'name' => 'Reviewer', 'state' => Role::STATE_ACTIVE]);
            $role->save();

            return $role;
        } finally {
            $this->clearContext();
        }
    }

    public function test_issue_validates_track_roles_and_rate_limits(): void
    {
        $tenant = $this->makeTenant();
        [$issuer] = $this->actingAsMembership($tenant);
        $headers = $this->tenantHeaders($tenant);

        $this->actingAs($issuer)->postJson('/api/v1/identity/invites', [
            'tenant_id' => (string) $tenant->getKey(),
            'track' => 'nope',
            'identifier' => 'a@example.com',
        ], $headers)->assertStatus(422);

        $this->actingAs($issuer)->postJson('/api/v1/identity/invites', [
            'tenant_id' => (string) $tenant->getKey(),
            'track' => 'pandit',
            'identifier' => 'a@example.com',
            'roles' => ['ghost-role'],
        ], $headers)->assertStatus(422);

        $this->seedReviewerRole($tenant);
        $issued = [];
        for ($i = 0; $i < 10; $i++) {
            $res = $this->actingAs($issuer)->postJson('/api/v1/identity/invites', [
                'tenant_id' => (string) $tenant->getKey(),
                'track' => 'pandit',
                'identifier' => "u{$i}@example.com",
                'roles' => ['reviewer'],
            ], $headers);
            $res->assertCreated();
            $issued[] = $res->json('data.token');
        }
        $this->assertMatchesRegularExpression('/^inv_[A-Za-z0-9]+$/', $issued[0]);

        $this->actingAs($issuer)->postJson('/api/v1/identity/invites', [
            'tenant_id' => (string) $tenant->getKey(),
            'track' => 'pandit',
            'identifier' => 'overflow@example.com',
        ], $headers)->assertStatus(429);
    }

    public function test_accept_creates_user_membership_grants_and_draft(): void
    {
        $tenant = $this->makeTenant();
        [$issuer] = $this->actingAsMembership($tenant);
        $this->seedReviewerRole($tenant);
        $issued = $this->actingAs($issuer)->postJson('/api/v1/identity/invites', [
            'tenant_id' => (string) $tenant->getKey(),
            'track' => 'pandit',
            'identifier' => 'pandit1@example.com',
            'roles' => ['reviewer'],
        ], $this->tenantHeaders($tenant))->assertCreated();
        $token = $issued->json('data.token');

        $accepted = $this->postJson('/api/v1/identity/invites:accept', ['token' => $token])->assertOk();
        $accepted->assertJsonPath('data.seeded.track_version', 'pandit-v1');
        $userId = $accepted->json('data.user_id');

        $this->assertDatabaseHas('tenant_memberships', [
            'tenant_id' => (string) $tenant->getKey(),
            'user_id' => $userId,
            'state' => 'active',
        ], 'central');
        $this->assertDatabaseHas('partner_applications', ['identity_id' => $userId], 'tenant_shared');

        // Replay converges to the same failure (no duplicate membership).
        $this->postJson('/api/v1/identity/invites:accept', ['token' => $token])
            ->assertUnauthorized();
        $this->assertSame(1, \App\Platform\Tenancy\Models\TenantMembership::query()
            ->where('tenant_id', (string) $tenant->getKey())->where('user_id', $userId)->count());

        // Unknown tokens share the failure shape (no oracle).
        $this->postJson('/api/v1/identity/invites:accept', ['token' => 'inv_bogus'])
            ->assertUnauthorized()
            ->assertJsonPath('error.code', 'unauthenticated');
    }

    public function test_revoke_kills_invite_before_accept(): void
    {
        $tenant = $this->makeTenant();
        [$issuer] = $this->actingAsMembership($tenant);
        $headers = $this->tenantHeaders($tenant);
        $issued = $this->actingAs($issuer)->postJson('/api/v1/identity/invites', [
            'tenant_id' => (string) $tenant->getKey(),
            'track' => 'customer',
            'identifier' => 'c@example.com',
        ], $headers)->assertCreated();
        $token = $issued->json('data.token');
        $id = Invite::query()->where('token_hash', hash('sha256', $token))->firstOrFail()->getKey();

        $this->actingAs($issuer)->postJson("/api/v1/identity/invites/{$id}:revoke", [], $headers)->assertOk();

        $this->postJson('/api/v1/identity/invites:accept', ['token' => $token])->assertUnauthorized();
    }
}
