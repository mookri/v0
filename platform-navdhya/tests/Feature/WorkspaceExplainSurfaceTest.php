<?php

namespace Tests\Feature;

use Tests\TenantIntegrationTestCase;

/**
 * Console consumers for the last two platform reads (plan slice 11,
 * doc 06 §7): /account/workspaces renders GET /api/v1/platform/tenants
 * (every membership state — the discovery surface that explains
 * denials), and /platform/explain drives GET /api/v1/authz/explain (the
 * SEC-022 chain trace, capability platform:authz:explain). Both pages
 * are shell + JS reads; the payload pins below assert exactly the
 * fields the page scripts consume. Endpoint behavior stays owned by
 * TenantSelfServiceTest and AuthzExplainApiTest.
 */
final class WorkspaceExplainSurfaceTest extends TenantIntegrationTestCase
{
    public function test_workspaces_page_renders_and_links_from_topbar(): void
    {
        $this->withoutVite();

        $tenant = $this->makeTenant();
        [$user] = $this->actingAsMembership($tenant);

        $this->actingAs($user)->get('/account/workspaces')
            ->assertOk()
            ->assertSee('id="workspace-rows"', false)
            ->assertSee('id="workspace-load-more"', false)
            // Topbar dropdown entry — exact href with closing quote so no
            // other account URL can match it.
            ->assertSee('href="http://localhost/account/workspaces"', false);
    }

    public function test_workspaces_payload_lists_every_state_the_page_renders(): void
    {
        $tenant = $this->makeTenant(['slug' => 'acme', 'status' => 'active']);
        [$user] = $this->actingAsMembership($tenant);
        // One membership per user+tenant — the suspended and revoked states
        // live in their own tenants, all belonging to the same user.
        $this->makeMembership($this->makeTenant(), $user, ['state' => 'suspended']);
        $this->makeMembership($this->makeTenant(), $user, ['state' => 'revoked']);

        $data = $this->actingAs($user)
            ->getJson('/api/v1/platform/tenants')
            ->assertOk()
            ->json('data');

        // Non-active memberships stay listed (TEN-006 §4.3: all states, so
        // denials are explainable) — that is what the page's badges show.
        $states = collect($data)->pluck('state');
        $this->assertContains('active', $states);
        $this->assertContains('suspended', $states);
        $this->assertContains('revoked', $states);

        // The tenant summary shape the page's name/slug/status cells read.
        $acmeRow = collect($data)->first(fn (array $row): bool => ($row['tenant']['slug'] ?? null) === 'acme');
        $this->assertNotNull($acmeRow, 'the active membership carries its tenant summary');
        $this->assertSame('tenant_membership', $acmeRow['object']);
        $this->assertSame('active', $acmeRow['tenant']['status']);
    }

    public function test_explain_page_renders_with_nav_entry(): void
    {
        $this->withoutVite();

        $tenant = $this->makeTenant();
        [$user] = $this->actingAsMembership($tenant, ['platform:authz:explain']);

        $this->actingAs($user)->get('/platform/explain')
            ->assertOk()
            ->assertSee('id="explain-form"', false)
            ->assertSee('id="explain-stage-rows"', false)
            ->assertSee('id="explain-workspace"', false)
            // Administration nav entry — exact href with closing quote.
            ->assertSee('href="http://localhost/platform/explain"', false);
    }

    public function test_explain_payload_carries_the_stage_fields_the_page_renders(): void
    {
        $tenant = $this->makeTenant();
        $this->actingAsMembership($tenant, ['platform:authz:explain']);
        $subject = $this->makeMembership($tenant);
        $this->grantCapabilities($tenant, $subject, ['sales:order:approve']);

        $response = $this->getJson(
            '/api/v1/authz/explain?membership_id='.$subject->getKey().'&capability=sales:order:approve',
            $this->tenantHeaders($tenant),
        );

        $response->assertOk();
        $this->assertContains($response->json('data.decision'), ['allow', 'deny']);
        $this->assertNotEmpty($response->json('data.deciding_stage'));

        // Every stage row carries the four fields the trace table renders
        // (stage, outcome, reason, details) and an outcome the badges map.
        $stages = $response->json('data.stages');
        $this->assertNotEmpty($stages);
        foreach ($stages as $stage) {
            $this->assertArrayHasKey('stage', $stage);
            $this->assertArrayHasKey('outcome', $stage);
            $this->assertArrayHasKey('reason', $stage);
            $this->assertArrayHasKey('details', $stage);
            $this->assertContains($stage['outcome'], ['allow', 'deny', 'continue']);
        }
    }
}
