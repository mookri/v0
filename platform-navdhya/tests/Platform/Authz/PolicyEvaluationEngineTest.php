<?php

namespace Tests\Platform\Authz;

use App\Models\User;
use App\Platform\Authz\AuthorizationDecision;
use App\Platform\Authz\AuthorizationRequest;
use App\Platform\Authz\Contracts\DecidingStage;
use App\Platform\Authz\Models\Policy;
use App\Platform\Authz\PolicyEvaluationEngine;
use App\Platform\Tenancy\Models\Tenant;
use App\Platform\Tenancy\Models\TenantMembership;
use Illuminate\Support\Str;
use Tests\TenantIntegrationTestCase;

/**
 * SEC-022 precedence-chain order matrix (T-0K01-02.2/.5): every stage —
 * explicit_deny, security_constraint, policy_constraint, allow_grant,
 * default_deny — decides with attribution, in order, fail-closed. Proves
 * AC-T-0K01-02-1 (explicit deny beats any grant), AC-T-0K01-02-2
 * (default deny) and the deterministic stage trace (AC-T-0K01-02-3).
 */
final class PolicyEvaluationEngineTest extends TenantIntegrationTestCase
{
    public function test_explicit_deny_beats_an_allow_grant(): void
    {
        $tenant = $this->makeTenant();
        $membership = $this->makeMembership($tenant);
        $this->grantCapabilities($tenant, $membership, ['sales:order:approve']);
        $user = User::findOrFail($membership->user_id);

        $this->activePolicy($tenant, 'deny');

        $decision = $this->decide($tenant, $membership, $user, 'sales:order:approve');

        $this->assertSame(AuthorizationDecision::DECISION_DENY, $decision->decision);
        $this->assertSame(DecidingStage::ExplicitDeny, $decision->decidingStage);
        $this->assertNotEmpty($decision->evaluatedPolicies, 'The evaluated policy ids are part of the K-013 decision.');
    }

    public function test_high_priority_allow_does_not_override_explicit_deny(): void
    {
        $tenant = $this->makeTenant();
        $membership = $this->makeMembership($tenant);
        $this->grantCapabilities($tenant, $membership, ['sales:order:approve']);
        $user = User::findOrFail($membership->user_id);

        $this->activePolicy($tenant, 'deny', ['priority' => -100]);
        $this->activePolicy($tenant, 'allow', ['priority' => 1000, 'name' => 'override-attempt']);

        $decision = $this->decide($tenant, $membership, $user, 'sales:order:approve');

        $this->assertSame(AuthorizationDecision::DECISION_DENY, $decision->decision);
        $this->assertSame(DecidingStage::ExplicitDeny, $decision->decidingStage);
    }

    public function test_default_deny_without_grant_or_policy(): void
    {
        $tenant = $this->makeTenant();
        $membership = $this->makeMembership($tenant);
        $user = User::findOrFail($membership->user_id);

        $decision = $this->decide($tenant, $membership, $user, 'sales:order:approve');

        $this->assertSame(AuthorizationDecision::DECISION_DENY, $decision->decision);
        $this->assertSame(DecidingStage::DefaultDeny, $decision->decidingStage);
    }

    public function test_allow_grant_when_no_policy_matches(): void
    {
        $tenant = $this->makeTenant();
        $membership = $this->makeMembership($tenant);
        $this->grantCapabilities($tenant, $membership, ['sales:order:approve']);
        $user = User::findOrFail($membership->user_id);

        // A policy on a different capability — must not affect this one.
        $this->activePolicy($tenant, 'deny', [
            'capability_selector' => ['module' => 'finance', 'resource' => 'invoice', 'action' => 'approve'],
        ]);

        $decision = $this->decide($tenant, $membership, $user, 'sales:order:approve');

        $this->assertSame(AuthorizationDecision::DECISION_ALLOW, $decision->decision);
        $this->assertSame(DecidingStage::AllowGrant, $decision->decidingStage);
    }

    public function test_deny_policy_with_unsatisfied_conditions_allows(): void
    {
        $tenant = $this->makeTenant();
        $membership = $this->makeMembership($tenant);
        $this->grantCapabilities($tenant, $membership, ['sales:order:approve']);
        $user = User::findOrFail($membership->user_id);

        $this->activePolicy($tenant, 'deny', [
            'conditions' => [['field' => 'amount', 'operator' => 'gt', 'value' => 10000]],
        ]);

        // Amount below the deny threshold: the deny does not apply.
        $decision = $this->decide($tenant, $membership, $user, 'sales:order:approve', ['amount' => 500]);

        $this->assertSame(AuthorizationDecision::DECISION_ALLOW, $decision->decision);
        $this->assertSame(DecidingStage::AllowGrant, $decision->decidingStage);
    }

    public function test_deny_policy_with_satisfied_conditions_denies(): void
    {
        $tenant = $this->makeTenant();
        $membership = $this->makeMembership($tenant);
        $this->grantCapabilities($tenant, $membership, ['sales:order:approve']);
        $user = User::findOrFail($membership->user_id);

        $policy = $this->activePolicy($tenant, 'deny', [
            'conditions' => [['field' => 'amount', 'operator' => 'gt', 'value' => 10000]],
        ]);

        $decision = $this->decide($tenant, $membership, $user, 'sales:order:approve', ['amount' => 20000]);

        $this->assertSame(AuthorizationDecision::DECISION_DENY, $decision->decision);
        $this->assertSame(DecidingStage::ExplicitDeny, $decision->decidingStage);
        $this->assertContains((string) $policy->getKey(), $decision->evaluatedPolicies);
    }

    public function test_allow_policy_constraint_denies_despite_grant(): void
    {
        $tenant = $this->makeTenant();
        $membership = $this->makeMembership($tenant);
        $this->grantCapabilities($tenant, $membership, ['sales:order:approve']);
        $user = User::findOrFail($membership->user_id);

        // A matching allow policy narrows the grant: its conditions must be
        // satisfied or the chain denies at the policy_constraint stage.
        $this->activePolicy($tenant, 'allow', [
            'conditions' => [['field' => 'amount', 'operator' => 'lt', 'value' => 1000]],
        ]);

        $denied = $this->decide($tenant, $membership, $user, 'sales:order:approve', ['amount' => 5000]);

        $this->assertSame(AuthorizationDecision::DECISION_DENY, $denied->decision);
        $this->assertSame(DecidingStage::PolicyConstraint, $denied->decidingStage);

        $allowed = $this->decide($tenant, $membership, $user, 'sales:order:approve', ['amount' => 500]);

        $this->assertSame(AuthorizationDecision::DECISION_ALLOW, $allowed->decision);
        $this->assertSame(DecidingStage::AllowGrant, $allowed->decidingStage);
    }

    public function test_draft_and_disabled_policies_are_not_evaluated(): void
    {
        $tenant = $this->makeTenant();
        $membership = $this->makeMembership($tenant);
        $this->grantCapabilities($tenant, $membership, ['sales:order:approve']);
        $user = User::findOrFail($membership->user_id);

        $this->activePolicy($tenant, 'deny', ['status' => 'draft']);
        $this->activePolicy($tenant, 'deny', ['status' => 'disabled', 'name' => 'disabled-deny']);

        $decision = $this->decide($tenant, $membership, $user, 'sales:order:approve');

        $this->assertSame(AuthorizationDecision::DECISION_ALLOW, $decision->decision);
        $this->assertSame([], $decision->evaluatedPolicies);
    }

    public function test_unresolved_context_denies_as_security_constraint(): void
    {
        $tenant = $this->makeTenant();
        $membership = $this->makeMembership($tenant);
        $this->grantCapabilities($tenant, $membership, ['sales:order:approve']);
        $user = User::findOrFail($membership->user_id);

        // No resolveContext() — the chain must fail closed.
        $decision = app(PolicyEvaluationEngine::class)->evaluate(new AuthorizationRequest(
            principal: $user,
            capability: 'sales:order:approve',
            membershipId: (string) $membership->getKey(),
        ), bypassCache: true);

        $this->assertSame(AuthorizationDecision::DECISION_DENY, $decision->decision);
        $this->assertSame(DecidingStage::SecurityConstraint, $decision->decidingStage);
        $this->assertStageReason($decision, DecidingStage::SecurityConstraint, 'tenant_context_unresolved');
    }

    public function test_null_principal_denies_as_security_constraint(): void
    {
        $tenant = $this->makeTenant();
        $membership = $this->makeMembership($tenant);
        $this->grantCapabilities($tenant, $membership, ['sales:order:approve']);

        $decision = $this->decide($tenant, $membership, null, 'sales:order:approve');

        $this->assertSame(AuthorizationDecision::DECISION_DENY, $decision->decision);
        $this->assertSame(DecidingStage::SecurityConstraint, $decision->decidingStage);
        $this->assertStageReason($decision, DecidingStage::SecurityConstraint, 'unauthenticated');
    }

    public function test_suspended_membership_denies_as_security_constraint(): void
    {
        $tenant = $this->makeTenant();
        $membership = $this->makeMembership($tenant, null, ['state' => TenantMembership::STATE_SUSPENDED]);
        $this->grantCapabilities($tenant, $membership, ['sales:order:approve']);
        $user = User::findOrFail($membership->user_id);

        $decision = $this->decide($tenant, $membership, $user, 'sales:order:approve');

        $this->assertSame(AuthorizationDecision::DECISION_DENY, $decision->decision);
        $this->assertSame(DecidingStage::SecurityConstraint, $decision->decidingStage);
        $this->assertStageReason($decision, DecidingStage::SecurityConstraint, 'membership_inactive');
    }

    public function test_suspended_tenant_denies_as_security_constraint(): void
    {
        $tenant = $this->makeTenant();
        $membership = $this->makeMembership($tenant);
        $this->grantCapabilities($tenant, $membership, ['sales:order:approve']);
        $user = User::findOrFail($membership->user_id);

        $tenant->update(['state' => Tenant::STATE_SUSPENDED]);

        $decision = $this->decide($tenant, $membership, $user, 'sales:order:approve');

        $this->assertSame(AuthorizationDecision::DECISION_DENY, $decision->decision);
        $this->assertSame(DecidingStage::SecurityConstraint, $decision->decidingStage);
        $this->assertStageReason($decision, DecidingStage::SecurityConstraint, 'tenant_suspended');
    }

    public function test_membership_from_another_tenant_denies(): void
    {
        $tenantA = $this->makeTenant();
        $tenantB = $this->makeTenant();

        $membershipB = $this->makeMembership($tenantB);
        $userB = User::findOrFail($membershipB->user_id);

        // Evaluate tenant B's membership while tenant A's context is active.
        $decision = $this->decide($tenantA, $membershipB, $userB, 'sales:order:approve');

        $this->assertSame(AuthorizationDecision::DECISION_DENY, $decision->decision);
        $this->assertSame(DecidingStage::SecurityConstraint, $decision->decidingStage);
        $this->assertStageReason($decision, DecidingStage::SecurityConstraint, 'membership_tenant_mismatch');
    }

    public function test_machine_principal_is_denied_sensitive_capabilities(): void
    {
        $tenant = $this->makeTenant();
        $membership = $this->makeMembership($tenant);
        // platform:policy:configure is declared sensitive by the kernel
        // manifest; platform:policy:view is not (SEC-020/SEC-024).
        $this->grantCapabilities($tenant, $membership, ['platform:policy:configure', 'platform:policy:view']);
        $user = User::findOrFail($membership->user_id);

        $sensitive = $this->decide($tenant, $membership, $user, 'platform:policy:configure', kind: AuthorizationRequest::KIND_AGENT);

        $this->assertSame(AuthorizationDecision::DECISION_DENY, $sensitive->decision);
        $this->assertSame(DecidingStage::SecurityConstraint, $sensitive->decidingStage);
        $this->assertStageReason($sensitive, DecidingStage::SecurityConstraint, 'sensitive_capability_forbidden_for_machine');

        $normal = $this->decide($tenant, $membership, $user, 'platform:policy:view', kind: AuthorizationRequest::KIND_AGENT);

        $this->assertSame(AuthorizationDecision::DECISION_ALLOW, $normal->decision);
        $this->assertSame(DecidingStage::AllowGrant, $normal->decidingStage);
    }

    public function test_human_principal_may_exercise_sensitive_capabilities(): void
    {
        $tenant = $this->makeTenant();
        $membership = $this->makeMembership($tenant);
        $this->grantCapabilities($tenant, $membership, ['platform:policy:configure']);
        $user = User::findOrFail($membership->user_id);

        $decision = $this->decide($tenant, $membership, $user, 'platform:policy:configure');

        $this->assertSame(AuthorizationDecision::DECISION_ALLOW, $decision->decision);
        $this->assertSame(DecidingStage::AllowGrant, $decision->decidingStage);
    }

    public function test_malformed_capability_code_denies_as_security_constraint(): void
    {
        $tenant = $this->makeTenant();
        $membership = $this->makeMembership($tenant);
        $this->grantCapabilities($tenant, $membership, ['sales:order:approve']);
        $user = User::findOrFail($membership->user_id);

        $decision = $this->decide($tenant, $membership, $user, 'Not A Capability');

        $this->assertSame(AuthorizationDecision::DECISION_DENY, $decision->decision);
        $this->assertSame(DecidingStage::SecurityConstraint, $decision->decidingStage);
        $this->assertStageReason($decision, DecidingStage::SecurityConstraint, 'capability_malformed');
    }

    public function test_malformed_stored_deny_selector_fails_closed(): void
    {
        $tenant = $this->makeTenant();
        $membership = $this->makeMembership($tenant);
        $this->grantCapabilities($tenant, $membership, ['sales:order:approve']);
        $user = User::findOrFail($membership->user_id);

        // Bypass validation the way corrupted data would: an explicit
        // tenant_id write with an invalid selector payload.
        Policy::create([
            'tenant_id' => (string) $tenant->getKey(),
            'name' => 'corrupted-deny',
            'effect' => 'deny',
            'capability_selector' => ['module' => 'Sales', 'resource' => 'order', 'action' => 'approve'],
            'status' => 'active',
        ]);

        $decision = $this->decide($tenant, $membership, $user, 'sales:order:approve');

        $this->assertSame(AuthorizationDecision::DECISION_DENY, $decision->decision);
        $this->assertSame(DecidingStage::ExplicitDeny, $decision->decidingStage);
        $this->assertStageReason($decision, DecidingStage::ExplicitDeny, 'malformed_deny_policy_fails_closed');
    }

    public function test_platform_default_deny_applies_to_every_tenant(): void
    {
        $tenant = $this->makeTenant();
        $membership = $this->makeMembership($tenant);
        $this->grantCapabilities($tenant, $membership, ['sales:order:approve']);
        $user = User::findOrFail($membership->user_id);

        // Platform default (tenant_id NULL, LD-15) is visible to all tenants.
        $this->clearContext();
        Policy::create([
            'tenant_id' => null,
            'name' => 'platform-wide-deny',
            'effect' => 'deny',
            'capability_selector' => ['module' => 'sales', 'resource' => 'order', 'action' => 'approve'],
            'status' => 'active',
        ]);

        $decision = $this->decide($tenant, $membership, $user, 'sales:order:approve');

        $this->assertSame(AuthorizationDecision::DECISION_DENY, $decision->decision);
        $this->assertSame(DecidingStage::ExplicitDeny, $decision->decidingStage);
    }

    public function test_evaluation_is_deterministic_across_repeated_calls(): void
    {
        $tenant = $this->makeTenant();
        $membership = $this->makeMembership($tenant);
        $this->grantCapabilities($tenant, $membership, ['sales:order:approve']);
        $user = User::findOrFail($membership->user_id);

        $this->activePolicy($tenant, 'allow', [
            'conditions' => [['field' => 'amount', 'operator' => 'lt', 'value' => 1000]],
        ]);

        $request = new AuthorizationRequest(
            principal: $user,
            capability: 'sales:order:approve',
            membershipId: (string) $membership->getKey(),
            attributes: ['amount' => 500],
        );

        $this->resolveContext($tenant, $membership);
        $engine = app(PolicyEvaluationEngine::class);

        $first = $engine->evaluate($request, bypassCache: true);
        $second = $engine->evaluate($request, bypassCache: true);
        $third = $engine->evaluate($request, bypassCache: true);

        $this->clearContext();

        $this->assertSame($first->toArray(), $second->toArray());
        $this->assertSame($second->toArray(), $third->toArray());
    }

    public function test_decision_payload_matches_the_k013_shape(): void
    {
        $tenant = $this->makeTenant();
        $membership = $this->makeMembership($tenant);
        $this->grantCapabilities($tenant, $membership, ['sales:order:approve']);
        $user = User::findOrFail($membership->user_id);

        $decision = $this->decide($tenant, $membership, $user, 'sales:order:approve');

        $this->assertSame(
            ['object', 'decision', 'deciding_stage', 'capability', 'principal_id', 'evaluated_policies', 'stages', 'cached', 'logged'],
            array_keys($decision->toArray()),
        );
        $this->assertSame('authz_decision', $decision->toArray()['object']);
        $this->assertSame((string) $user->getAuthIdentifier(), $decision->principalId);

        // The trace walks the chain in SEC-022 order and ends at the
        // deciding stage.
        $this->assertSame(
            ['explicit_deny', 'security_constraint', 'policy_constraint', 'allow_grant'],
            array_column($decision->stages, 'stage'),
        );
    }

    public function test_ensure_throws_with_the_deciding_stage_as_reason(): void
    {
        $tenant = $this->makeTenant();
        $membership = $this->makeMembership($tenant);
        $user = User::findOrFail($membership->user_id);

        $this->resolveContext($tenant, $membership);

        try {
            app(PolicyEvaluationEngine::class)->ensure(new AuthorizationRequest(
                principal: $user,
                capability: 'sales:order:approve',
                membershipId: (string) $membership->getKey(),
            ));
            $this->fail('Expected AuthorizationDeniedException.');
        } catch (\App\Platform\Authz\Exceptions\AuthorizationDeniedException $exception) {
            $this->assertSame('sales:order:approve', $exception->capability);
            $this->assertSame(DecidingStage::DefaultDeny->value, $exception->reason);
        } finally {
            $this->clearContext();
        }
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function decide(
        Tenant $tenant,
        ?TenantMembership $membership,
        ?User $user,
        string $capability,
        array $attributes = [],
        string $kind = AuthorizationRequest::KIND_USER,
    ): AuthorizationDecision {
        $this->resolveContext($tenant, $membership);

        $decision = app(PolicyEvaluationEngine::class)->evaluate(new AuthorizationRequest(
            principal: $user,
            capability: $capability,
            principalKind: $kind,
            membershipId: $membership !== null ? (string) $membership->getKey() : null,
            attributes: $attributes,
        ), bypassCache: true);

        $this->clearContext();

        return $decision;
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

    private function assertStageReason(AuthorizationDecision $decision, DecidingStage $stage, string $reason): void
    {
        foreach ($decision->stages as $entry) {
            if (($entry['stage'] ?? null) === $stage->value && ($entry['reason'] ?? null) === $reason) {
                return;
            }
        }

        $this->fail(sprintf(
            'Expected stage %s with reason %s in the decision trace: %s',
            $stage->value,
            $reason,
            (string) json_encode($decision->stages, JSON_UNESCAPED_SLASHES),
        ));
    }
}
