<?php

namespace Tests\Feature;

use App\Platform\Identity\Models\Invite;
use Tests\TenantIntegrationTestCase;

/**
 * Invites console surface (plan slice 10, doc 06 §7 + A-SEC-001-1): the
 * /platform/invites page plus the shared issue/revoke actions, and the
 * revoke authorization the OpenAPI fragment always declared but the code
 * never enforced — tenant present (422), issuer active in it (403),
 * invite looked up scoped to that tenant (404, the same vocabulary as a
 * miss, never an existence oracle). Issue/accept envelopes stay
 * byte-identical: InviteTest + the full suite are the parity pin.
 */
final class AdminInvitesSurfaceTest extends TenantIntegrationTestCase
{
    private const PAGE = 'http://localhost/platform/invites';

    public function test_invites_page_renders_issue_form_and_table(): void
    {
        $this->withoutVite();

        $tenant = $this->makeTenant();
        [$user] = $this->actingAsMembership($tenant);

        $this->actingAs($user)->get('/platform/invites')
            ->assertOk()
            ->assertSee('id="invite-create-form"', false)
            ->assertSee('id="invite-rows"', false)
            ->assertSee('id="invite-workspace"', false)
            // Topbar/section nav entry — exact href with closing quote so
            // no other /platform/invites reference can match it.
            ->assertSee('href="'.self::PAGE.'"', false);
    }

    public function test_issue_via_web_carries_the_one_time_token_and_validates(): void
    {
        $tenant = $this->makeTenant();
        [$issuer] = $this->actingAsMembership($tenant);
        $tid = (string) $tenant->getKey();

        $this->withHeader('Referer', self::PAGE)
            ->actingAs($issuer)
            ->post('/platform/invites', [
                'tenant_id' => $tid,
                'track' => 'customer',
                'identifier' => 'invitee@example.test',
            ])
            ->assertRedirect(self::PAGE)
            ->assertSessionHas('flash');
        $flash = (string) session('flash');
        $this->assertStringStartsWith('Invite issued', $flash);
        $this->assertStringContainsString('inv_', $flash);
        $this->assertDatabaseCount('invites', 1, 'central');
        $this->assertDatabaseHas('invites', ['identifier' => 'invitee@example.test', 'state' => Invite::STATE_ISSUED], 'central');

        // Missing identifier → framework validation redirect with $errors,
        // no write.
        $this->withHeader('Referer', self::PAGE)
            ->actingAs($issuer)
            ->post('/platform/invites', ['tenant_id' => $tid, 'track' => 'customer'])
            ->assertRedirect(self::PAGE)
            ->assertSessionHasErrors('identifier');
        $this->assertDatabaseCount('invites', 1, 'central');
    }

    public function test_revoke_via_web_is_tenant_scoped(): void
    {
        $tenantA = $this->makeTenant();
        $tenantB = $this->makeTenant();
        [$issuer] = $this->actingAsMembership($tenantA);
        $idA = (string) Invite::factory()->create(['tenant_id' => (string) $tenantA->getKey()])->getKey();
        $idB = (string) Invite::factory()->create(['tenant_id' => (string) $tenantB->getKey()])->getKey();

        // Cross-tenant attempt: the returned 404 negotiates to flash_error
        // on web (returned envelopes never become HTML status pages), and
        // the foreign row is untouched.
        $this->withHeader('Referer', self::PAGE)
            ->actingAs($issuer)
            ->post("/platform/invites/{$idB}:revoke", ['tenant_id' => (string) $tenantA->getKey()])
            ->assertRedirect(self::PAGE)
            ->assertSessionHas('flash_error');
        $this->assertStringContainsString('not_found', (string) session('flash_error'));
        $this->assertDatabaseHas('invites', ['id' => $idB, 'state' => Invite::STATE_ISSUED], 'central');

        // Own tenant → flashed success, state flipped.
        $this->withHeader('Referer', self::PAGE)
            ->actingAs($issuer)
            ->post("/platform/invites/{$idA}:revoke", ['tenant_id' => (string) $tenantA->getKey()])
            ->assertRedirect(self::PAGE)
            ->assertSessionHas('flash', 'Invite revoked.');
        $this->assertDatabaseHas('invites', ['id' => $idA, 'state' => Invite::STATE_REVOKED], 'central');
    }

    public function test_list_endpoint_scopes_to_tenant_and_membership(): void
    {
        $tenantA = $this->makeTenant();
        $tenantB = $this->makeTenant();
        [$issuer] = $this->actingAsMembership($tenantA);
        $tid = (string) $tenantA->getKey();

        Invite::factory()->create(['tenant_id' => $tid, 'identifier' => 'mine@example.test']);
        Invite::factory()->create(['tenant_id' => (string) $tenantB->getKey(), 'identifier' => 'foreign@example.test']);

        // Missing tenant → 422 (the platform read convention).
        $this->actingAs($issuer)
            ->getJson('/api/v1/identity/invites')
            ->assertUnprocessable();

        $listed = $this->actingAs($issuer)
            ->getJson("/api/v1/identity/invites?tenant_id={$tid}")
            ->assertOk()
            ->json('data');
        $identifiers = array_column($listed, 'identifier');
        $this->assertContains('mine@example.test', $identifiers);
        $this->assertNotContains('foreign@example.test', $identifiers);
        $this->assertArrayNotHasKey('token', $listed[0]);
        $this->assertArrayNotHasKey('token_hash', $listed[0]);

        // Issuer without a membership here → 403 membership_inactive.
        [$lone] = $this->actingAsMembership($this->makeTenant());
        $this->actingAs($lone)
            ->getJson("/api/v1/identity/invites?tenant_id={$tid}")
            ->assertForbidden()
            ->assertJsonPath('error.code', 'membership_inactive');
    }

    public function test_revoke_endpoint_requires_tenant_and_stays_scoped(): void
    {
        $tenantA = $this->makeTenant();
        $tenantB = $this->makeTenant();
        [$issuer] = $this->actingAsMembership($tenantA);
        $idB = (string) Invite::factory()->create(['tenant_id' => (string) $tenantB->getKey()])->getKey();

        // Tenant absent (no header, no field) → 422: the fragment's
        // required X-Tenant-Id is finally enforced.
        $this->actingAs($issuer)
            ->postJson("/api/v1/identity/invites/{$idB}:revoke")
            ->assertUnprocessable();

        // Issuer naming their OWN tenant against a foreign invite → scoped
        // miss (404), never an existence oracle, row untouched — the IDOR
        // the unscoped find() used to allow.
        $this->actingAs($issuer)
            ->postJson("/api/v1/identity/invites/{$idB}:revoke", [], $this->tenantHeaders($tenantA))
            ->assertNotFound();
        $this->assertDatabaseHas('invites', ['id' => $idB, 'state' => Invite::STATE_ISSUED], 'central');

        // An outsider naming tenant B has no active membership there →
        // 403 membership_inactive, row untouched.
        [$lone] = $this->actingAsMembership($this->makeTenant());
        $this->actingAs($lone)
            ->postJson("/api/v1/identity/invites/{$idB}:revoke", [], ['X-Tenant-Id' => (string) $tenantB->getKey()])
            ->assertForbidden()
            ->assertJsonPath('error.code', 'membership_inactive');
        $this->assertDatabaseHas('invites', ['id' => $idB, 'state' => Invite::STATE_ISSUED], 'central');
    }
}
