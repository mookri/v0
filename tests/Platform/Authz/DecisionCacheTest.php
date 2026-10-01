<?php

namespace Tests\Platform\Authz;

use App\Models\User;
use App\Platform\Authz\AuthorizationRequest;
use App\Platform\Authz\Contracts\DecidingStage;
use App\Platform\Authz\Models\Policy;
use App\Platform\Authz\Models\Role;
use App\Platform\Authz\PolicyEvaluationEngine;
use App\Platform\Tenancy\Models\Tenant;
use App\Platform\Tenancy\Models\TenantMembership;
use Tests\TenantIntegrationTestCase;

/**
 * Decision cache + generation-based invalidation (T-0K01-02.2,
 * AC-T-0K01-02-3): identical evaluations are memoized; any grant, policy
 * or platform-default mutation makes cached decisions unreachable within
 * the same request cycle; SEC-024 machine principals bypass the cache
 * entirely.
 */
final class DecisionCacheTest extends TenantIntegrationTestCase
{
    public function test_identical_evaluation_is_served_from_cache(): void
    {
        [$engine, $request] = $this->grantedEngine('sales:order:approve');

        $first = $engine->evaluate($request);
        $second = $engine->evaluate($request);

        $this->assertFalse($first->cached);
        $this->assertTrue($second->cached);
        $this->assertSame($first->decision, $second->decision);
        $this->assertSame($first->decidingStage, $second->decidingStage);

        $this->clearContext();
    }

    public function test_grant_revocation_invalidates_within_the_same_request_cycle(): void
    {
        $tenant = $this->makeTenant();
        $membership = $this->makeMembership($tenant);
        $role = $this->grantCapabilities($tenant, $membership, ['sales:order:approve']);
        $user = User::findOrFail($membership->user_id);

        $this->resolveContext($tenant, $membership);
        $engine = app(PolicyEvaluationEngine::class);
        $request = new AuthorizationRequest(principal: $user, capability: 'sales:order:approve');

        $first = $engine->evaluate($request);
        $this->assertTrue($first->allowed());

        // Disable the granting role — model-level mutation fires the
        // invalidation hook; no app refresh, same request cycle.
        $role->update(['state' => Role::STATE_DISABLED]);

        $second = $engine->evaluate($request);

        $this->assertFalse($second->cached, 'The cached decision must be unreachable after a grant change.');
        $this->assertSame('deny', $second->decision);
        $this->assertSame(DecidingStage::DefaultDeny, $second->decidingStage);

        $this->clearContext();
    }

    public function test_new_deny_policy_invalidates_cached_allow(): void
    {
        [$engine, $request, $tenant, $membership] = $this->grantedEngine('sales:order:approve');

        $first = $engine->evaluate($request);
        $this->assertTrue($first->allowed());

        $this->resolveContext($tenant, $membership);
        Policy::create([
            'name' => 'late-deny',
            'effect' => 'deny',
            'capability_selector' => ['module' => 'sales', 'resource' => 'order', 'action' => 'approve'],
            'status' => 'active',
        ]);

        $second = $engine->evaluate($request);

        $this->assertFalse($second->cached);
        $this->assertSame('deny', $second->decision);
        $this->assertSame(DecidingStage::ExplicitDeny, $second->decidingStage);

        $this->clearContext();
    }

    public function test_platform_default_change_invalidates_every_tenant(): void
    {
        [$engine, $request, $tenant, $membership] = $this->grantedEngine('sales:order:approve');

        $first = $engine->evaluate($request);
        $this->assertTrue($first->allowed());

        // Platform-default rows (tenant_id NULL) affect every tenant and
        // bump the global generation.
        $this->clearContext();
        Policy::create([
            'tenant_id' => null,
            'name' => 'global-deny',
            'effect' => 'deny',
            'capability_selector' => ['module' => 'sales', 'resource' => 'order', 'action' => 'approve'],
            'status' => 'active',
        ]);

        $this->resolveContext($tenant, $membership);

        $second = $engine->evaluate($request);

        $this->assertFalse($second->cached);
        $this->assertSame('deny', $second->decision);
        $this->assertSame(DecidingStage::ExplicitDeny, $second->decidingStage);

        $this->clearContext();
    }

    public function test_machine_principals_are_never_served_from_cache(): void
    {
        [$engine, $request] = $this->grantedEngine('platform:policy:view');

        $agentRequest = new AuthorizationRequest(
            principal: $request->principal,
            capability: $request->capability,
            principalKind: AuthorizationRequest::KIND_AGENT,
        );

        $first = $engine->evaluate($agentRequest);
        $second = $engine->evaluate($agentRequest);

        $this->assertFalse($first->cached);
        $this->assertFalse($second->cached, 'SEC-024: agent authorization is re-evaluated on every call.');
        $this->assertTrue($second->allowed());

        $this->clearContext();
    }

    public function test_attribute_changes_produce_distinct_cache_entries(): void
    {
        [$engine, $request, $tenant, $membership] = $this->grantedEngine('sales:order:approve');

        $this->resolveContext($tenant, $membership);
        Policy::create([
            'name' => 'amount-deny',
            'effect' => 'deny',
            'capability_selector' => ['module' => 'sales', 'resource' => 'order', 'action' => 'approve'],
            'conditions' => [['field' => 'amount', 'operator' => 'gt', 'value' => 10000]],
            'status' => 'active',
        ]);

        $low = new AuthorizationRequest(principal: $request->principal, capability: 'sales:order:approve', attributes: ['amount' => 500]);
        $high = new AuthorizationRequest(principal: $request->principal, capability: 'sales:order:approve', attributes: ['amount' => 20000]);

        $firstLow = $engine->evaluate($low);
        $firstHigh = $engine->evaluate($high);
        $secondLow = $engine->evaluate($low);

        $this->assertFalse($firstLow->cached);
        $this->assertTrue($firstLow->allowed());
        $this->assertFalse($firstHigh->cached);
        $this->assertSame('deny', $firstHigh->decision);
        $this->assertTrue($secondLow->cached, 'Unchanged attributes hit their own cache entry.');
        $this->assertTrue($secondLow->allowed());

        $this->clearContext();
    }

    /**
     * Boot a resolved context (tenant + membership) with a membership
     * granted the capability. The context stays resolved on return.
     *
     * @return array{0: PolicyEvaluationEngine, 1: AuthorizationRequest, 2: Tenant, 3: TenantMembership}
     */
    private function grantedEngine(string $capability): array
    {
        $tenant = $this->makeTenant();
        $membership = $this->makeMembership($tenant);
        $this->grantCapabilities($tenant, $membership, [$capability]);
        $user = User::findOrFail($membership->user_id);

        $this->resolveContext($tenant, $membership);

        return [
            app(PolicyEvaluationEngine::class),
            new AuthorizationRequest(principal: $user, capability: $capability),
            $tenant,
            $membership,
        ];
    }
}
