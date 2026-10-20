<?php

namespace Tests\Feature;

use App\Models\User;
use App\Platform\Authz\Models\SodRule;
use Tests\TenantIntegrationTestCase;

/**
 * Admin write surfaces (plan slice 3, doc 06 §7.1-7.2): the console's
 * forms post to the SAME controller actions as /api/v1 — session + CSRF
 * on the web side, bearer on the API side, one service and validation
 * path underneath. Response negotiation is the only per-surface concern:
 * web redirects back with a coded flash (or $errors from validation),
 * API answers keep their envelopes untouched (the full suite is the
 * parity pin).
 */
final class AdminWriteSurfacesTest extends TenantIntegrationTestCase
{
    public function test_policy_create_via_web_form_shares_the_api_action(): void
    {
        $tenant = $this->makeTenant();
        $this->actingAsMembership($tenant, ['platform:policy:configure']);

        $response = $this->withHeader('Referer', 'http://localhost/platform/policies')
            ->post('/platform/policies', [
                // Transport-only keys: the CSRF token is stripped before
                // the strict unknown-key validator; priority arrives as a
                // string (HTML transport) and is adapted, not relaxed.
                '_token' => 'not-a-real-token',
                'tenant_id' => $tenant->getKey(),
                'name' => 'web-created-policy',
                'description' => '',
                'effect' => 'allow',
                'capability_selector' => ['module' => 'pandit', 'resource' => 'profile', 'action' => 'view'],
                'priority' => '10',
            ]);

        $response->assertRedirect('http://localhost/platform/policies');
        $response->assertSessionHas('flash', 'Policy created.');

        $this->assertDatabaseHas('policies', [
            'name' => 'web-created-policy',
            'tenant_id' => $tenant->getKey(),
            'priority' => 10,
            'status' => 'draft',
        ], 'central');
    }

    public function test_policy_create_on_web_is_denied_without_the_capability(): void
    {
        $tenant = $this->makeTenant();
        $user = User::factory()->create();
        $this->makeMembership($tenant, $user);
        $this->actingAs($user);

        $this->post('/platform/policies', [
            'tenant_id' => $tenant->getKey(),
            'name' => 'should-never-exist',
            'effect' => 'allow',
            'capability_selector' => ['module' => 'pandit', 'resource' => 'profile', 'action' => 'view'],
        ])->assertForbidden();

        $this->assertDatabaseMissing('policies', ['name' => 'should-never-exist'], 'central');
    }

    public function test_policy_transition_via_web_redirects_back_with_flash(): void
    {
        $tenant = $this->makeTenant();
        $this->actingAsMembership($tenant, ['platform:policy:configure']);

        $id = $this->postJson('/api/v1/policies', [
            'name' => 'transition-target',
            'effect' => 'deny',
            'capability_selector' => ['module' => 'sales', 'resource' => 'order', 'action' => 'approve'],
        ], $this->tenantHeaders($tenant))->assertStatus(201)->json('data.id');

        $this->withHeader('Referer', 'http://localhost/platform/policies')
            ->post("/platform/policies/{$id}:activate", ['tenant_id' => $tenant->getKey()])
            ->assertRedirect('http://localhost/platform/policies')
            ->assertSessionHas('flash', 'Policy activated.');

        $this->assertDatabaseHas('policies', ['id' => $id, 'status' => 'active'], 'central');
    }

    public function test_role_create_via_web(): void
    {
        $tenant = $this->makeTenant();
        $this->actingAsMembership($tenant, ['platform:role:manage']);

        $this->withHeader('Referer', 'http://localhost/platform/roles')
            ->post('/platform/roles', [
                'tenant_id' => $tenant->getKey(),
                'code' => 'ops_manager',
                'name' => 'Ops Manager',
            ])
            ->assertRedirect('http://localhost/platform/roles')
            ->assertSessionHas('flash', 'Role created.');

        $this->assertDatabaseHas('roles', [
            'code' => 'ops_manager',
            'name' => 'Ops Manager',
            'tenant_id' => $tenant->getKey(),
        ], 'central');
    }

    public function test_role_create_on_web_is_denied_without_the_capability(): void
    {
        // Membership alone must not let a member mint a role for
        // themselves — the whole escalation this gate closes.
        $tenant = $this->makeTenant();
        $user = User::factory()->create();
        $this->makeMembership($tenant, $user);
        $this->actingAs($user);

        $this->post('/platform/roles', [
            'tenant_id' => $tenant->getKey(),
            'code' => 'self_promoted',
            'name' => 'Self Promoted',
        ])->assertForbidden();

        $this->assertDatabaseMissing('roles', ['code' => 'self_promoted'], 'central');
    }

    public function test_role_create_validation_failure_redirects_back_with_errors(): void
    {
        $tenant = $this->makeTenant();
        $this->actingAsMembership($tenant, ['platform:role:manage']);

        $this->withHeader('Referer', 'http://localhost/platform/roles')
            ->post('/platform/roles', [
                'tenant_id' => $tenant->getKey(),
                'name' => 'Missing Code',
            ])
            ->assertRedirect('http://localhost/platform/roles')
            ->assertSessionHasErrors('code');
    }

    public function test_sod_rule_create_toggle_and_delete_via_web(): void
    {
        $tenant = $this->makeTenant();
        $this->actingAsMembership($tenant, ['platform:sod:configure']);

        $this->withHeader('Referer', 'http://localhost/platform/sod-rules')
            ->post('/platform/sod-rules', [
                'tenant_id' => $tenant->getKey(),
                'code' => 'sod.approver.payer',
                'name' => 'Approver cannot pay',
                'kind' => 'conflict',
                'severity' => 'block',
                'status' => 'active',
                'role_set' => ['approver', 'payer'],
            ])
            ->assertRedirect('http://localhost/platform/sod-rules')
            ->assertSessionHas('flash', 'Rule created.');

        $this->assertDatabaseHas('sod_rules', [
            'code' => 'sod.approver.payer',
            'tenant_id' => $tenant->getKey(),
            'severity' => 'block',
        ], 'central');

        $id = (string) SodRule::query()->where('code', 'sod.approver.payer')->value('id');

        // The rows' buttons post with _method spoofing, exactly as the
        // browser forms do (Kernel enables the override in handle()).
        $this->post("/platform/sod-rules/{$id}", [
            '_method' => 'PATCH',
            'tenant_id' => $tenant->getKey(),
            'status' => 'disabled',
        ])
            ->assertRedirect('http://localhost/platform/sod-rules')
            ->assertSessionHas('flash', 'Rule updated.');

        $this->assertDatabaseHas('sod_rules', ['id' => $id, 'status' => 'disabled'], 'central');

        $this->post("/platform/sod-rules/{$id}", [
            '_method' => 'DELETE',
            'tenant_id' => $tenant->getKey(),
        ])
            ->assertRedirect('http://localhost/platform/sod-rules')
            ->assertSessionHas('flash', 'Rule deleted.');

        $this->assertDatabaseMissing('sod_rules', ['id' => $id], 'central');
    }

    public function test_admin_pages_render_their_write_forms(): void
    {
        $this->withoutVite();

        $tenant = $this->makeTenant();
        $user = User::factory()->create();
        $this->makeMembership($tenant, $user);

        $pages = [
            '/platform/policies' => 'action="http://localhost/platform/policies"',
            '/platform/roles' => 'action="http://localhost/platform/roles"',
            '/platform/sod-rules' => 'action="http://localhost/platform/sod-rules"',
        ];

        foreach ($pages as $path => $action) {
            $response = $this->actingAs($user)->get($path);

            $response->assertOk();
            $response->assertSee($action, false);
            $response->assertSee('name="csrf-token"', false);
            $response->assertSee('data-workspace-field', false);
        }

        $this->actingAs($user)->get('/platform/sod-rules')
            ->assertSee('id="sod-create-form"', false);
    }
}
