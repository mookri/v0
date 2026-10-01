<?php

namespace Tests\Platform\Authz;

use App\Platform\Authz\Events\PolicyCreated;
use App\Platform\Authz\Events\PolicyStatusChanged;
use App\Platform\Authz\Events\PolicyUpdated;
use App\Platform\Authz\Models\Policy;
use App\Platform\Tenancy\Models\Tenant;
use Illuminate\Support\Facades\Event;
use Tests\TenantIntegrationTestCase;

/**
 * Policy CRUD admin surface contract tests (SEC-021, T-0K01-01.4):
 * proves AC-T-0K01-01-2 (201/422), -3 (events with full selector payload),
 * -4 (403 + denial audit) and the read/update/transition paths.
 */
final class PolicyApiTest extends TenantIntegrationTestCase
{
    /**
     * @return array<string, mixed>
     */
    private function validPayload(): array
    {
        return [
            'name' => 'cap-approvals-over-10k',
            'description' => 'Deny approvals above 10k',
            'effect' => 'deny',
            'capability_selector' => ['module' => 'sales', 'resource' => 'order', 'action' => 'approve'],
            'scope_bindings' => [
                ['scope_type' => 'organization', 'scope_id' => 'org_1', 'scope_mode' => 'SUBTREE'],
            ],
            'conditions' => [
                ['field' => 'amount', 'operator' => 'gt', 'value' => 10000],
            ],
            'priority' => 10,
        ];
    }

    public function test_create_policy_returns_201_with_envelope(): void
    {
        $tenant = $this->makeTenant();
        $this->actingAsMembership($tenant, ['platform:policy:configure']);

        $response = $this->postJson('/api/v1/policies', $this->validPayload(), $this->tenantHeaders($tenant));

        $response->assertStatus(201);
        $response->assertJsonPath('data.object', 'policy');
        $response->assertJsonPath('data.name', 'cap-approvals-over-10k');
        $response->assertJsonPath('data.effect', 'deny');
        $response->assertJsonPath('data.status', 'draft');
        $response->assertJsonPath('data.version', 1);
        $response->assertJsonPath('data.capability_selector.module', 'sales');
        $response->assertJsonStructure(['data' => ['id', 'created_at'], 'meta' => ['request_id']]);

        $this->assertDatabaseHas('policies', ['name' => 'cap-approvals-over-10k', 'tenant_id' => $tenant->getKey()], 'central');
    }

    public function test_create_policy_emits_created_event_with_full_selector_payload(): void
    {
        Event::fake([PolicyCreated::class]);

        $tenant = $this->makeTenant();
        $this->actingAsMembership($tenant, ['platform:policy:configure']);

        $this->postJson('/api/v1/policies', $this->validPayload(), $this->tenantHeaders($tenant))
            ->assertStatus(201);

        Event::assertDispatched(PolicyCreated::class, function (PolicyCreated $event): bool {
            $payload = $event->toPlatformPayload();

            return $event->platformEventName() === 'platform.policy.created'
                && $payload['effect'] === 'deny'
                && $payload['capability_selector'] === ['module' => 'sales', 'resource' => 'order', 'action' => 'approve']
                && $payload['status'] === 'draft';
        });
    }

    public function test_create_policy_writes_audit_record(): void
    {
        $tenant = $this->makeTenant();
        $this->actingAsMembership($tenant, ['platform:policy:configure']);

        $this->postJson('/api/v1/policies', $this->validPayload(), $this->tenantHeaders($tenant))
            ->assertStatus(201);

        $this->assertAuditRecorded($tenant, 'policy.created');
    }

    public function test_create_policy_rejects_invalid_effect_with_422(): void
    {
        $tenant = $this->makeTenant();
        $this->actingAsMembership($tenant, ['platform:policy:configure']);

        $payload = $this->validPayload();
        $payload['effect'] = 'maybe';

        $response = $this->postJson('/api/v1/policies', $payload, $this->tenantHeaders($tenant));

        $this->assertEnvelopeCode($response, 422, 'validation_failed');
        $response->assertJsonStructure(['error' => ['details' => ['fields' => ['effect']]]]);
    }

    public function test_create_policy_rejects_invalid_selector_with_422(): void
    {
        $tenant = $this->makeTenant();
        $this->actingAsMembership($tenant, ['platform:policy:configure']);

        $payload = $this->validPayload();
        $payload['capability_selector'] = ['module' => 'Sales', 'resource' => 'order', 'action' => 'view'];

        $response = $this->postJson('/api/v1/policies', $payload, $this->tenantHeaders($tenant));

        $this->assertEnvelopeCode($response, 422, 'validation_failed');
        $response->assertJsonStructure(['error' => ['details' => ['fields' => ['capability_selector']]]]);
    }

    public function test_create_policy_rejects_free_form_condition_with_422(): void
    {
        $tenant = $this->makeTenant();
        $this->actingAsMembership($tenant, ['platform:policy:configure']);

        $payload = $this->validPayload();
        $payload['conditions'] = [
            ['field' => 'amount', 'operator' => 'eq', 'value' => ['expr' => 'cart.total * 1.1']],
        ];

        $response = $this->postJson('/api/v1/policies', $payload, $this->tenantHeaders($tenant));

        $this->assertEnvelopeCode($response, 422, 'validation_failed');
        $response->assertJsonStructure(['error' => ['details' => ['fields' => ['conditions']]]]);
    }

    public function test_create_policy_rejects_unknown_field_strictly(): void
    {
        $tenant = $this->makeTenant();
        $this->actingAsMembership($tenant, ['platform:policy:configure']);

        $payload = $this->validPayload();
        $payload['script'] = 'deny all';

        $response = $this->postJson('/api/v1/policies', $payload, $this->tenantHeaders($tenant));

        $this->assertEnvelopeCode($response, 422, 'validation_failed');
    }

    public function test_create_policy_name_conflict_returns_409(): void
    {
        $tenant = $this->makeTenant();
        $this->actingAsMembership($tenant, ['platform:policy:configure']);

        $this->postJson('/api/v1/policies', $this->validPayload(), $this->tenantHeaders($tenant))->assertStatus(201);

        $response = $this->postJson('/api/v1/policies', $this->validPayload(), $this->tenantHeaders($tenant));

        $this->assertEnvelopeCode($response, 409, 'conflict');
    }

    public function test_show_policy_returns_envelope(): void
    {
        $tenant = $this->makeTenant();
        $this->actingAsMembership($tenant, ['platform:policy:view']);

        $this->resolveContext($tenant);
        $policy = Policy::create([
            'name' => 'read-me',
            'effect' => 'allow',
            'capability_selector' => ['module' => 'sales', 'resource' => 'order', 'action' => 'view'],
            'status' => 'draft',
        ]);
        $this->clearContext();

        $response = $this->getJson('/api/v1/policies/'.$policy->getKey(), $this->tenantHeaders($tenant));

        $response->assertOk();
        $response->assertJsonPath('data.id', (string) $policy->getKey());
        $response->assertJsonPath('data.name', 'read-me');
    }

    public function test_show_policy_from_another_tenant_returns_404(): void
    {
        $tenantA = $this->makeTenant();
        $this->resolveContext($tenantA);
        $policy = Policy::create([
            'name' => 'foreign',
            'effect' => 'allow',
            'capability_selector' => ['module' => 'sales', 'resource' => 'order', 'action' => 'view'],
            'status' => 'draft',
        ]);
        $this->clearContext();

        $tenantB = $this->makeTenant();
        $this->actingAsMembership($tenantB, ['platform:policy:view']);

        $response = $this->getJson('/api/v1/policies/'.$policy->getKey(), $this->tenantHeaders($tenantB));

        $this->assertEnvelopeCode($response, 404, 'not_found');
    }

    public function test_update_policy_bumps_version_and_emits_updated_event(): void
    {
        Event::fake([PolicyUpdated::class]);

        $tenant = $this->makeTenant();
        $this->actingAsMembership($tenant, ['platform:policy:configure']);

        $this->resolveContext($tenant);
        $policy = Policy::create([
            'name' => 'update-me',
            'effect' => 'allow',
            'capability_selector' => ['module' => 'sales', 'resource' => 'order', 'action' => 'view'],
            'status' => 'draft',
            'version' => 1,
        ]);
        $this->clearContext();

        $response = $this->patchJson(
            '/api/v1/policies/'.$policy->getKey(),
            ['description' => 'updated note', 'priority' => 5],
            $this->tenantHeaders($tenant),
        );

        $response->assertOk();
        $response->assertJsonPath('data.version', 2);
        $response->assertJsonPath('data.priority', 5);

        Event::assertDispatched(PolicyUpdated::class, function (PolicyUpdated $event): bool {
            return $event->platformEventName() === 'platform.policy.updated'
                && $event->toPlatformPayload()['version'] === 2;
        });

        $this->assertAuditRecorded($tenant, 'policy.updated');
    }

    public function test_update_platform_default_policy_is_forbidden(): void
    {
        $tenant = $this->makeTenant();
        $this->actingAsMembership($tenant, ['platform:policy:configure']);

        // Platform default: tenant_id NULL (LD-15). Created outside tenant scope.
        $this->clearContext();
        $default = Policy::create([
            'tenant_id' => null,
            'name' => 'platform-default',
            'effect' => 'deny',
            'capability_selector' => ['module' => 'platform', 'resource' => 'policy', 'action' => 'delete'],
            'status' => 'active',
        ]);

        $this->actingAsMembership($tenant, ['platform:policy:configure']);

        $response = $this->patchJson(
            '/api/v1/policies/'.$default->getKey(),
            ['description' => 'try to edit'],
            $this->tenantHeaders($tenant),
        );

        $this->assertEnvelopeCode($response, 403, 'authorization_denied');
    }

    public function test_activate_transitions_status_and_emits_event(): void
    {
        Event::fake([PolicyStatusChanged::class]);

        $tenant = $this->makeTenant();
        $this->actingAsMembership($tenant, ['platform:policy:configure']);

        $this->resolveContext($tenant);
        $policy = Policy::create([
            'name' => 'activate-me',
            'effect' => 'allow',
            'capability_selector' => ['module' => 'sales', 'resource' => 'order', 'action' => 'view'],
            'status' => 'draft',
        ]);
        $this->clearContext();

        $response = $this->postJson('/api/v1/policies/'.$policy->getKey().':activate', [], $this->tenantHeaders($tenant));

        $response->assertOk();
        $response->assertJsonPath('data.status', 'active');

        Event::assertDispatched(PolicyStatusChanged::class, function (PolicyStatusChanged $event): bool {
            return $event->platformEventName() === 'platform.policy.status_changed'
                && $event->fromStatus === 'draft'
                && $event->toStatus === 'active';
        });

        $this->assertAuditRecorded($tenant, 'policy.status_changed');
    }

    public function test_disable_from_active_succeeds(): void
    {
        $tenant = $this->makeTenant();
        $this->actingAsMembership($tenant, ['platform:policy:configure']);

        $this->resolveContext($tenant);
        $policy = Policy::create([
            'name' => 'disable-me',
            'effect' => 'allow',
            'capability_selector' => ['module' => 'sales', 'resource' => 'order', 'action' => 'view'],
            'status' => 'active',
        ]);
        $this->clearContext();

        $response = $this->postJson('/api/v1/policies/'.$policy->getKey().':disable', [], $this->tenantHeaders($tenant));

        $response->assertOk();
        $response->assertJsonPath('data.status', 'disabled');
    }

    public function test_illegal_transition_returns_409_state_forbidden(): void
    {
        $tenant = $this->makeTenant();
        $this->actingAsMembership($tenant, ['platform:policy:configure']);

        $this->resolveContext($tenant);
        $policy = Policy::create([
            'name' => 'stuck',
            'effect' => 'allow',
            'capability_selector' => ['module' => 'sales', 'resource' => 'order', 'action' => 'view'],
            'status' => 'disabled',
        ]);
        $this->clearContext();

        // disabled -> draft is not a legal transition; only activate (-> active) is.
        // Attempt to re-activate is legal, so instead try :disable on an already-disabled policy.
        $response = $this->postJson('/api/v1/policies/'.$policy->getKey().':disable', [], $this->tenantHeaders($tenant));

        $this->assertEnvelopeCode($response, 409, 'state_forbidden');
    }

    public function test_configure_without_permission_returns_403_and_writes_denial_audit(): void
    {
        $tenant = $this->makeTenant();
        // Holds view only — NOT platform:policy:configure.
        $this->actingAsMembership($tenant, ['platform:policy:view']);

        $response = $this->postJson('/api/v1/policies', $this->validPayload(), $this->tenantHeaders($tenant));

        $this->assertEnvelopeCode($response, 403, 'authorization_denied');
        $response->assertJsonPath('error.details.capability', 'platform:policy:configure');

        $this->assertAuditRecorded($tenant, 'authz.denied');
    }

    public function test_view_without_permission_returns_403(): void
    {
        $tenant = $this->makeTenant();
        // Membership but no platform:policy:view grant.
        $this->actingAsMembership($tenant, []);

        $response = $this->getJson('/api/v1/policies', $this->tenantHeaders($tenant));

        $this->assertEnvelopeCode($response, 403, 'authorization_denied');
    }

    public function test_index_lists_policies_with_cursor_pagination(): void
    {
        $tenant = $this->makeTenant();
        $this->actingAsMembership($tenant, ['platform:policy:view']);

        $this->resolveContext($tenant);

        foreach (['p-one', 'p-two', 'p-three'] as $name) {
            Policy::create([
                'name' => $name,
                'effect' => 'allow',
                'capability_selector' => ['module' => 'sales', 'resource' => 'order', 'action' => 'view'],
                'status' => 'draft',
            ]);
        }

        $this->clearContext();

        $response = $this->getJson('/api/v1/policies?limit=2', $this->tenantHeaders($tenant));

        $response->assertOk();
        $response->assertJsonCount(2, 'data');
        $response->assertJsonPath('meta.page.has_more', true);
        $response->assertJsonStructure(['meta' => ['page' => ['limit', 'has_more', 'next_cursor']]]);
    }

    public function test_index_includes_platform_defaults_but_not_foreign_tenant_rows(): void
    {
        // Platform default (NULL tenant) + a row in tenant B.
        $this->clearContext();
        Policy::create([
            'tenant_id' => null,
            'name' => 'default-visible-everywhere',
            'effect' => 'deny',
            'capability_selector' => ['module' => 'platform', 'resource' => 'policy', 'action' => 'delete'],
            'status' => 'active',
        ]);

        $tenantB = $this->makeTenant();
        $this->resolveContext($tenantB);
        Policy::create([
            'name' => 'tenant-b-private',
            'effect' => 'allow',
            'capability_selector' => ['module' => 'sales', 'resource' => 'order', 'action' => 'view'],
            'status' => 'draft',
        ]);
        $this->clearContext();

        $tenantA = $this->makeTenant();
        $this->actingAsMembership($tenantA, ['platform:policy:view']);

        $response = $this->getJson('/api/v1/policies', $this->tenantHeaders($tenantA));

        $response->assertOk();
        $names = array_column($response->json('data'), 'name');

        $this->assertContains('default-visible-everywhere', $names, 'Platform defaults are readable by every tenant.');
        $this->assertNotContains('tenant-b-private', $names, 'Foreign tenant rows must not leak.');
    }

    public function test_index_rejects_undeclared_filter(): void
    {
        $tenant = $this->makeTenant();
        $this->actingAsMembership($tenant, ['platform:policy:view']);

        $response = $this->getJson('/api/v1/policies?bogus=1', $this->tenantHeaders($tenant));

        $this->assertEnvelopeCode($response, 422, 'filter_not_allowed');
    }
}
