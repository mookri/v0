<?php

namespace Tests\Feature;

use App\Models\User;
use App\Platform\Authz\Models\SodRule;
use Tests\TenantIntegrationTestCase;

/**
 * Admin edit surfaces (plan slice 5, doc 06 §7): PATCH edit forms post to
 * the SAME controller actions as /api/v1 — session + CSRF on web, bearer
 * on the API, one strict validator underneath. The policy grammar is the
 * shared choke point either way: the API answers 422 validation_failed
 * (PolicyApiTest), and the web surface keeps the framework's thrown-
 * exception negotiation — real status, fail-closed, no partial write.
 * Step-up-gated mutations stay API-only until their /oauth/stepup form
 * flow lands (doc 06 §7.5).
 */
final class AdminEditSurfacesTest extends TenantIntegrationTestCase
{
    public function test_policy_edit_via_web_shares_the_patch_action(): void
    {
        $tenant = $this->makeTenant();
        $this->actingAsMembership($tenant, ['platform:policy:configure']);

        $id = $this->postJson('/api/v1/policies', [
            'name' => 'edit-target',
            'effect' => 'allow',
            'capability_selector' => ['module' => 'pandit', 'resource' => 'profile', 'action' => 'view'],
            'priority' => 5,
        ], $this->tenantHeaders($tenant))->assertStatus(201)->json('data.id');

        $response = $this->withHeader('Referer', 'http://localhost/platform/policies')
            ->post("/platform/policies/{$id}", [
                // Transport keys are stripped before the strict unknown-key
                // validator; priority arrives as a string (HTML transport)
                // and is adapted, not relaxed.
                '_token' => 'not-a-real-token',
                '_method' => 'PATCH',
                'tenant_id' => $tenant->getKey(),
                'name' => 'edit-target-renamed',
                'description' => 'edited from the console',
                'effect' => 'deny',
                'priority' => '25',
                'capability_selector' => ['module' => 'sales', 'resource' => 'order', 'action' => 'approve'],
            ]);

        $response->assertRedirect('http://localhost/platform/policies');
        $response->assertSessionHas('flash', 'Policy updated.');

        $this->assertDatabaseHas('policies', [
            'id' => $id,
            'name' => 'edit-target-renamed',
            'description' => 'edited from the console',
            'effect' => 'deny',
            'priority' => 25,
            'version' => 2,
        ], 'central');
    }

    public function test_policy_edit_on_web_is_denied_without_the_capability(): void
    {
        $tenant = $this->makeTenant();
        $this->actingAsMembership($tenant, ['platform:policy:configure']);

        $id = $this->postJson('/api/v1/policies', [
            'name' => 'denied-edit-target',
            'effect' => 'allow',
            'capability_selector' => ['module' => 'pandit', 'resource' => 'profile', 'action' => 'view'],
        ], $this->tenantHeaders($tenant))->assertStatus(201)->json('data.id');

        $plain = User::factory()->create();
        $this->makeMembership($tenant, $plain);

        $this->actingAs($plain)
            ->post("/platform/policies/{$id}", [
                'tenant_id' => $tenant->getKey(),
                '_method' => 'PATCH',
                'name' => 'should-never-rename',
            ])
            ->assertForbidden();

        $this->assertDatabaseHas('policies', ['id' => $id, 'name' => 'denied-edit-target'], 'central');
    }

    public function test_policy_edit_grammar_violation_fails_closed_on_web(): void
    {
        $tenant = $this->makeTenant();
        $this->actingAsMembership($tenant, ['platform:policy:configure']);

        $id = $this->postJson('/api/v1/policies', [
            'name' => 'grammar-target',
            'effect' => 'allow',
            'capability_selector' => ['module' => 'pandit', 'resource' => 'profile', 'action' => 'view'],
        ], $this->tenantHeaders($tenant))->assertStatus(201)->json('data.id');

        // The shared validator throws InvalidPolicyPayloadException (a
        // RuntimeException): the web layer keeps the framework's default
        // error rendering — real status, fail-closed, no partial write.
        // The API surface of the SAME action answers 422 validation_failed
        // (covered by PolicyApiTest), so status parity by surface stands.
        $this->post("/platform/policies/{$id}", [
            'tenant_id' => $tenant->getKey(),
            '_method' => 'PATCH',
            'name' => 'valid-name',
            'effect' => 'maybe',
        ])->assertStatus(500);

        $this->assertDatabaseHas('policies', ['id' => $id, 'name' => 'grammar-target'], 'central');
    }

    public function test_sod_rule_edit_via_web_shares_the_patch_action(): void
    {
        $tenant = $this->makeTenant();
        $this->actingAsMembership($tenant);

        $this->withHeader('Referer', 'http://localhost/platform/sod-rules')
            ->post('/platform/sod-rules', [
                'tenant_id' => $tenant->getKey(),
                'code' => 'sod.editor.approver',
                'name' => 'Editor cannot approve',
                'kind' => 'conflict',
                'severity' => 'block',
                'status' => 'active',
                'role_set' => ['editor', 'approver'],
            ])
            ->assertRedirect('http://localhost/platform/sod-rules')
            ->assertSessionHas('flash', 'Rule created.');

        $id = (string) SodRule::query()->where('code', 'sod.editor.approver')->value('id');

        // The edit form posts sets as role_set[] arrays (split from the
        // comma inputs by the page JS before submit).
        $this->withHeader('Referer', 'http://localhost/platform/sod-rules')
            ->post("/platform/sod-rules/{$id}", [
                '_token' => 'not-a-real-token',
                '_method' => 'PATCH',
                'tenant_id' => $tenant->getKey(),
                'name' => 'Editor cannot release',
                'severity' => 'warn',
                'role_set' => ['editor', 'releaser'],
            ])
            ->assertRedirect('http://localhost/platform/sod-rules')
            ->assertSessionHas('flash', 'Rule updated.');

        $rule = SodRule::query()->findOrFail($id);
        $this->assertSame('Editor cannot release', $rule->name);
        $this->assertSame('warn', $rule->severity);
        $this->assertSame(['editor', 'releaser'], $rule->role_set);
    }

    public function test_admin_pages_render_their_edit_forms(): void
    {
        $this->withoutVite();

        $tenant = $this->makeTenant();
        $user = User::factory()->create();
        $this->makeMembership($tenant, $user);

        $policies = $this->actingAs($user)->get('/platform/policies');
        $policies->assertOk();
        $policies->assertSee('id="policy-edit-card"', false);
        $policies->assertSee('id="policy-edit-form"', false);
        $policies->assertSee('name="_method"', false);

        $sod = $this->actingAs($user)->get('/platform/sod-rules');
        $sod->assertOk();
        $sod->assertSee('id="sod-edit-card"', false);
        $sod->assertSee('id="sod-edit-form"', false);
        $sod->assertSee('id="sod-edit-role-set"', false);
    }
}
