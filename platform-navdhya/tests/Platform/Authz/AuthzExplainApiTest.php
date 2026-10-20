<?php

namespace Tests\Platform\Authz;

use App\Platform\Authz\Models\Policy;
use App\Platform\Tenancy\Models\Tenant;
use Illuminate\Support\Str;
use Tests\TenantIntegrationTestCase;

/**
 * Restricted authorization explain surface (SEC-022, T-0K01-02.3):
 * proves AC-T-0K01-02-4 (full chain trace for principals visible in the
 * caller's own tenant, 403 authorization_denied otherwise) and
 * AC-T-0K01-02-5 (callers without platform:authz:explain are denied
 * with a denial audit record).
 */
final class AuthzExplainApiTest extends TenantIntegrationTestCase
{
    public function test_explain_returns_full_chain_trace_for_visible_membership(): void
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
        $response->assertJsonStructure([
            'data' => [
                'object',
                'decision',
                'deciding_stage',
                'capability',
                'principal_id',
                'evaluated_policies',
                'stages',
                'cached',
                'logged',
            ],
            'meta' => ['request_id'],
        ]);
        $response->assertJsonPath('data.object', 'authz_decision');
        $response->assertJsonPath('data.decision', 'allow');
        $response->assertJsonPath('data.deciding_stage', 'allow_grant');
        $response->assertJsonPath('data.capability', 'sales:order:approve');
        $response->assertJsonPath('data.principal_id', (string) $subject->user_id);
        $response->assertJsonPath('data.cached', false);

        // The trace walks the SEC-022 chain in order and ends at the
        // deciding stage (K-013).
        $this->assertSame(
            ['explicit_deny', 'security_constraint', 'policy_constraint', 'allow_grant'],
            array_column($response->json('data.stages'), 'stage'),
        );
    }

    public function test_explain_reports_explicit_deny_with_evaluated_policy(): void
    {
        $tenant = $this->makeTenant();
        $this->actingAsMembership($tenant, ['platform:authz:explain']);

        $subject = $this->makeMembership($tenant);
        $this->grantCapabilities($tenant, $subject, ['sales:order:approve']);
        $policy = $this->activePolicy($tenant, 'deny');

        $response = $this->getJson(
            '/api/v1/authz/explain?membership_id='.$subject->getKey().'&capability=sales:order:approve',
            $this->tenantHeaders($tenant),
        );

        $response->assertOk();
        $response->assertJsonPath('data.decision', 'deny');
        $response->assertJsonPath('data.deciding_stage', 'explicit_deny');
        $this->assertContains(
            (string) $policy->getKey(),
            $response->json('data.evaluated_policies'),
            'The trace names the evaluated policy ids (K-013).',
        );
    }

    public function test_explain_forwards_condition_attributes_from_query(): void
    {
        $tenant = $this->makeTenant();
        $this->actingAsMembership($tenant, ['platform:authz:explain']);

        $subject = $this->makeMembership($tenant);
        $this->grantCapabilities($tenant, $subject, ['sales:order:approve']);
        $this->activePolicy($tenant, 'deny', [
            'conditions' => [['field' => 'amount', 'operator' => 'gt', 'value' => 10000]],
        ]);

        $base = '/api/v1/authz/explain?membership_id='.$subject->getKey().'&capability=sales:order:approve';

        // Above the threshold the deny policy matches...
        $this->getJson($base.'&amount=15000', $this->tenantHeaders($tenant))
            ->assertOk()
            ->assertJsonPath('data.deciding_stage', 'explicit_deny');

        // ...below it, the grant decides.
        $this->getJson($base.'&amount=500', $this->tenantHeaders($tenant))
            ->assertOk()
            ->assertJsonPath('data.deciding_stage', 'allow_grant');
    }

    public function test_explain_denies_membership_from_foreign_tenant(): void
    {
        $tenant = $this->makeTenant();
        $this->actingAsMembership($tenant, ['platform:authz:explain']);

        $foreignTenant = $this->makeTenant();
        $foreignSubject = $this->makeMembership($foreignTenant);

        $response = $this->getJson(
            '/api/v1/authz/explain?membership_id='.$foreignSubject->getKey().'&capability=sales:order:approve',
            $this->tenantHeaders($tenant),
        );

        $this->assertEnvelopeCode($response, 403, 'authorization_denied');
    }

    public function test_explain_without_capability_returns_403_and_writes_denial_audit(): void
    {
        $tenant = $this->makeTenant();
        // Membership without the sensitive platform:authz:explain grant.
        $this->actingAsMembership($tenant, ['platform:policy:view']);

        $subject = $this->makeMembership($tenant);

        $response = $this->getJson(
            '/api/v1/authz/explain?membership_id='.$subject->getKey().'&capability=sales:order:approve',
            $this->tenantHeaders($tenant),
        );

        $this->assertEnvelopeCode($response, 403, 'authorization_denied');
        $response->assertJsonPath('error.details.capability', 'platform:authz:explain');

        $this->assertAuditRecorded($tenant, 'authz.denied');
    }

    public function test_explain_rejects_missing_parameters_with_422(): void
    {
        $tenant = $this->makeTenant();
        $this->actingAsMembership($tenant, ['platform:authz:explain']);

        $response = $this->getJson('/api/v1/authz/explain', $this->tenantHeaders($tenant));

        $this->assertEnvelopeCode($response, 422, 'validation_failed');
        $response->assertJsonStructure(['error' => ['details' => ['fields' => ['membership_id', 'capability']]]]);
    }

    public function test_explain_rejects_invalid_attribute_values_with_422(): void
    {
        $tenant = $this->makeTenant();
        $this->actingAsMembership($tenant, ['platform:authz:explain']);

        $subject = $this->makeMembership($tenant);

        $response = $this->getJson(
            '/api/v1/authz/explain?membership_id='.$subject->getKey().'&capability=sales:order:approve&currency=usd',
            $this->tenantHeaders($tenant),
        );

        $this->assertEnvelopeCode($response, 422, 'validation_failed');
        $response->assertJsonStructure(['error' => ['details' => ['fields' => ['currency']]]]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function activePolicy(Tenant $tenant, string $effect, array $overrides = []): Policy
    {
        $this->resolveContext($tenant);

        $policy = Policy::create($overrides + [
            'name' => 'policy-'.$effect.'-'.Str::lower(Str::random(8)),
            'effect' => $effect,
            'capability_selector' => ['module' => 'sales', 'resource' => 'order', 'action' => 'approve'],
            'status' => 'active',
        ]);

        $this->clearContext();

        return $policy;
    }
}
