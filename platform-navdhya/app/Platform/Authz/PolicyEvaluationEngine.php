<?php

namespace App\Platform\Authz;

use App\Platform\Authz\Contracts\Capability;
use App\Platform\Authz\Contracts\CapabilitySelector;
use App\Platform\Authz\Contracts\DecidingStage;
use App\Platform\Authz\Contracts\Effect;
use App\Platform\Authz\Contracts\PolicyStatus;
use App\Platform\Authz\Exceptions\AuthorizationDeniedException;
use App\Platform\Authz\Exceptions\CapabilityParseException;
use App\Platform\Authz\Exceptions\InvalidPolicyPayloadException;
use App\Platform\Authz\Exceptions\SodConflictException;
use App\Platform\Authz\Models\CapabilityMetadata;
use App\Platform\Authz\Models\Policy;
use App\Platform\Authz\Models\Role;
use App\Platform\Authz\Models\RolePermission;
use App\Platform\Modules\ModuleRegistry;
use App\Platform\Tenancy\Models\Tenant;
use App\Platform\Tenancy\Models\TenantMembership;
use App\Platform\Tenancy\TenantContext;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Builder;

/**
 * SEC-022 precedence-chain evaluator (T-0K01-02.2): deterministic,
 * fail-closed evaluation of one capability request:
 *
 *   1. explicit_deny       - a matching active deny policy whose
 *                            conditions are satisfied denies immediately,
 *                            regardless of grant source.
 *   2. security_constraint - unresolved tenant context, unauthenticated
 *                            principal, missing/inactive membership,
 *                            suspended tenant, machine principal on a
 *                            sensitive capability (SEC-024).
 *   3. policy_constraint   - matching active allow policies exist but none
 *                            is satisfied by the request attributes
 *                            (policies narrow grants).
 *   4. allow_grant         - an active-role grant carries the capability
 *                            for the membership.
 *   4.5 sod_conflict        - runtime SoD re-check (SEC-011 §7.3): a
 *                            blocking conflict rule involving the
 *                            exercised permission denies despite the
 *                            grant (rules added after assignment).
 *   5. default_deny        - no explicit allow reached (CON-003).
 *
 * Every decision carries its deciding stage, the evaluated policy ids and
 * the full stage trace consumed by the explain surface (T-0K01-02.3) and,
 * from T-0K01-03, the decision log.
 *
 * Decisions are cached per (tenant, generation, principal kind,
 * membership, capability, evaluation context) with generation-based
 * invalidation (AC-T-0K01-02-3). SEC-024: machine/agent principals are
 * NEVER served from cache — their authorization is re-evaluated on every
 * call.
 */
final class PolicyEvaluationEngine
{
    public function __construct(
        private readonly TenantContext $context,
        private readonly CapabilityParser $parser,
        private readonly ConditionEvaluator $conditions,
        private readonly DecisionCache $cache,
        private readonly ModuleRegistry $registry,
        private readonly SodEvaluator $sod,
    ) {
    }

    public function evaluate(AuthorizationRequest $request, bool $bypassCache = false): AuthorizationDecision
    {
        $membershipId = $request->membershipId ?? $this->context->membershipId();
        $tenantId = $this->context->tenantId();

        // SEC-024: agent/service principals are re-evaluated on every
        // call; cached grants are never reused across calls.
        $cacheable = ! $bypassCache
            && $request->principalKind === AuthorizationRequest::KIND_USER
            && $membershipId !== null
            && $tenantId !== null;

        $cacheContext = [
            'attributes' => $request->attributes,
            'resource' => $request->resource,
            'working_context' => $request->workingContext,
        ];

        if ($cacheable) {
            $cached = $this->cache->get(
                (string) $tenantId,
                $request->principalKind,
                (string) $membershipId,
                $request->capability,
                $cacheContext,
            );

            if ($cached !== null) {
                return $cached;
            }
        }

        $decision = $this->runChain($request, $membershipId);

        if ($cacheable) {
            $this->cache->put(
                (string) $tenantId,
                $request->principalKind,
                (string) $membershipId,
                $request->capability,
                $cacheContext,
                $decision,
            );
        }

        return $decision;
    }

    /**
     * Evaluate and deny with an AuthorizationDeniedException carrying the
     * deciding stage as the stable reason (middleware + contract calls).
     * SoD runtime denials throw SodConflictException so the envelope
     * renders the stable `sod_conflict` code with the blocking rules.
     *
     * @throws AuthorizationDeniedException
     */
    public function ensure(AuthorizationRequest $request): AuthorizationDecision
    {
        $decision = $this->evaluate($request);

        if ($decision->allowed()) {
            return $decision;
        }

        if ($decision->decidingStage === DecidingStage::SodConflict) {
            throw new SodConflictException($request->capability, $this->sodRulesFrom($decision));
        }

        throw new AuthorizationDeniedException(
            capability: $request->capability,
            reason: $decision->decidingStage->value,
        );
    }

    /**
     * @return list<string>
     */
    private function sodRulesFrom(AuthorizationDecision $decision): array
    {
        foreach ($decision->stages as $stage) {
            if (($stage['stage'] ?? null) === DecidingStage::SodConflict->value
                && isset($stage['details']['rules']) && is_array($stage['details']['rules'])) {
                return array_values(array_filter($stage['details']['rules'], 'is_string'));
            }
        }

        return [];
    }

    private function runChain(AuthorizationRequest $request, ?string $membershipId): AuthorizationDecision
    {
        /** @var list<array<string, mixed>> $stages */
        $stages = [];
        $principalId = $request->principalId ?? $this->principalId($request->principal);

        // Stage 0 (pre-chain): capability codes must parse (SEC-020);
        // malformed codes fail closed as a security constraint.
        try {
            $capability = $this->parser->parse($request->capability);
        } catch (CapabilityParseException) {
            $stages[] = $this->stage(DecidingStage::SecurityConstraint, 'deny', 'capability_malformed');

            return $this->decision(AuthorizationDecision::DECISION_DENY, DecidingStage::SecurityConstraint, $request->capability, $principalId, [], $stages);
        }

        [$matching, $malformedDeny] = $this->matchingActivePolicies($capability);

        $evaluated = array_values(array_map(
            static fn (Policy $policy): string => (string) $policy->getKey(),
            $matching,
        ));

        // Stage 1: explicit DENY always wins, regardless of grant source.
        if ($malformedDeny) {
            $stages[] = $this->stage(DecidingStage::ExplicitDeny, 'deny', 'malformed_deny_policy_fails_closed');

            return $this->decision(AuthorizationDecision::DECISION_DENY, DecidingStage::ExplicitDeny, $request->capability, $principalId, $evaluated, $stages);
        }

        $denied = $this->explicitDenyStage($matching, $request, $stages);

        if ($denied !== null) {
            return $this->decision(AuthorizationDecision::DECISION_DENY, DecidingStage::ExplicitDeny, $request->capability, $principalId, $evaluated, $stages);
        }

        // Stage 2: security constraints (fail-closed state checks).
        $securityReason = $this->securityConstraintStage($request, $capability, $membershipId, $stages);

        if ($securityReason !== null) {
            return $this->decision(AuthorizationDecision::DECISION_DENY, DecidingStage::SecurityConstraint, $request->capability, $principalId, $evaluated, $stages);
        }

        // Stage 3: policy constraints — matching allow policies narrow the
        // grant; if none of them is satisfied, the request is denied.
        $constrained = $this->policyConstraintStage($matching, $request, $stages);

        if ($constrained) {
            return $this->decision(AuthorizationDecision::DECISION_DENY, DecidingStage::PolicyConstraint, $request->capability, $principalId, $evaluated, $stages);
        }

        // Stage 4: explicit allow grant (scopes ∩ grants ∩ membership).
        if ($membershipId !== null && $this->grantExists($membershipId, $capability->code)) {
            // Stage 4.5 (SEC-011 §7.3): SoD runtime re-check. Grants alone
            // are insufficient — a conflict rule added after assignment (or
            // a cross-assignment combination) denies here even though the
            // grant exists. Warn-severity trips stay allowed (assignment
            // time owned the warning).
            $sodTenant = $this->context->tenantId();
            $blocks = $sodTenant !== null
                ? $this->sod->blockingForCapability($sodTenant, $membershipId, $capability->code)
                : [];

            if ($blocks !== []) {
                $stages[] = $this->stage(DecidingStage::SodConflict, 'deny', 'sod_conflict', ['rules' => $blocks]);

                return $this->decision(AuthorizationDecision::DECISION_DENY, DecidingStage::SodConflict, $request->capability, $principalId, $evaluated, $stages);
            }

            $stages[] = $this->stage(DecidingStage::AllowGrant, 'allow', 'capability_granted');

            return $this->decision(AuthorizationDecision::DECISION_ALLOW, DecidingStage::AllowGrant, $request->capability, $principalId, $evaluated, $stages);
        }

        $stages[] = $this->stage(DecidingStage::AllowGrant, 'continue', 'no_matching_grant');

        // Stage 5: default deny (fail closed, CON-003).
        $stages[] = $this->stage(DecidingStage::DefaultDeny, 'deny', 'default_deny');

        return $this->decision(AuthorizationDecision::DECISION_DENY, DecidingStage::DefaultDeny, $request->capability, $principalId, $evaluated, $stages);
    }

    /**
     * Active policies whose selector matches the capability, visible to
     * the resolved tenant (tenant rows + platform defaults, LD-15),
     * ordered deterministically by priority DESC then name ASC.
     *
     * @return array{0: list<Policy>, 1: bool} matching policies + whether a
     *                                          deny policy carried a malformed selector
     */
    private function matchingActivePolicies(Capability $capability): array
    {
        if (! $this->context->isResolved()) {
            // Fail-closed: an unresolved context sees no policies; stage 2
            // denies on the unresolved context regardless.
            return [[], false];
        }

        $matching = [];
        $malformedDeny = false;

        $policies = Policy::query()
            ->where('status', PolicyStatus::Active->value)
            ->orderByDesc('priority')
            ->orderBy('name')
            ->get();

        foreach ($policies as $policy) {
            $selectorPayload = $policy->capability_selector;

            try {
                $selector = CapabilitySelector::fromArray(is_array($selectorPayload) ? $selectorPayload : []);
            } catch (InvalidPolicyPayloadException) {
                // A malformed stored selector should have been caught by
                // platform:sync-permissions; if encountered, a deny policy
                // fails closed while an allow policy is skipped.
                if ($policy->effect === Effect::Deny->value) {
                    $malformedDeny = true;
                }

                continue;
            }

            if ($selector->matches($capability->module, $capability->resource, $capability->action)) {
                $matching[] = $policy;
            }
        }

        return [$matching, $malformedDeny];
    }

    /**
     * @param  list<Policy>  $matching
     * @param  list<array<string, mixed>>  $stages
     * @return string|null the denial reason, or null when no deny triggered
     */
    private function explicitDenyStage(array $matching, AuthorizationRequest $request, array &$stages): ?string
    {
        $examined = [];

        foreach ($matching as $policy) {
            if ($policy->effect !== Effect::Deny->value) {
                continue;
            }

            [$satisfied, $failures] = $this->conditions->evaluate($policy->conditions ?? [], $request->attributes);

            $examined[] = [
                'policy_id' => (string) $policy->getKey(),
                'conditions_satisfied' => $satisfied,
                'failures' => $failures,
            ];

            if ($satisfied) {
                $stages[] = $this->stage(DecidingStage::ExplicitDeny, 'deny', 'explicit_deny_policy_matched', [
                    'policy_id' => (string) $policy->getKey(),
                    'policy_name' => (string) $policy->name,
                    'examined' => $examined,
                ]);

                return 'explicit_deny_policy_matched';
            }
        }

        $stages[] = $this->stage(DecidingStage::ExplicitDeny, 'continue', null, ['examined' => $examined]);

        return null;
    }

    /**
     * @param  list<array<string, mixed>>  $stages
     * @return string|null the denial reason, or null when all constraints pass
     */
    private function securityConstraintStage(AuthorizationRequest $request, Capability $capability, ?string $membershipId, array &$stages): ?string
    {
        $reason = null;
        $tenantId = $this->context->tenantId();

        if (! $this->context->isResolved() || $tenantId === null) {
            $reason = 'tenant_context_unresolved';
        } elseif ($request->principal === null) {
            $reason = 'unauthenticated';
        } elseif ($membershipId === null) {
            $reason = 'membership_missing';
        } else {
            $membership = TenantMembership::query()->whereKey($membershipId)->first();

            if ($membership === null) {
                $reason = 'membership_missing';
            } elseif ((string) $membership->tenant_id !== $tenantId) {
                $reason = 'membership_tenant_mismatch';
            } elseif ($membership->state !== TenantMembership::STATE_ACTIVE) {
                $reason = 'membership_inactive';
            } else {
                $tenant = Tenant::query()->whereKey($tenantId)->first();

                if ($tenant === null) {
                    $reason = 'tenant_missing';
                } elseif ($tenant->state !== Tenant::STATE_ACTIVE) {
                    $reason = 'tenant_suspended';
                } elseif ($request->principalKind !== AuthorizationRequest::KIND_USER
                    && $this->isSensitive($capability->code)) {
                    // SEC-024: machine/agent principals MUST NOT exercise
                    // sensitive-flagged capabilities.
                    $reason = 'sensitive_capability_forbidden_for_machine';
                }
            }
        }

        if ($reason !== null) {
            $stages[] = $this->stage(DecidingStage::SecurityConstraint, 'deny', $reason);

            return $reason;
        }

        $stages[] = $this->stage(DecidingStage::SecurityConstraint, 'continue', null, [
            'membership_id' => $membershipId,
            'tenant_id' => $tenantId,
        ]);

        return null;
    }

    /**
     * @param  list<Policy>  $matching
     * @param  list<array<string, mixed>>  $stages
     * @return bool true when a matching allow policy constraint denies
     */
    private function policyConstraintStage(array $matching, AuthorizationRequest $request, array &$stages): bool
    {
        $satisfied = [];
        $unsatisfied = [];

        foreach ($matching as $policy) {
            if ($policy->effect !== Effect::Allow->value) {
                continue;
            }

            [$conditionsMet, $failures] = $this->conditions->evaluate($policy->conditions ?? [], $request->attributes);

            if ($conditionsMet) {
                $satisfied[] = (string) $policy->getKey();
            } else {
                $unsatisfied[] = [
                    'policy_id' => (string) $policy->getKey(),
                    'failures' => $failures,
                ];
            }
        }

        if ($satisfied === [] && $unsatisfied !== []) {
            $stages[] = $this->stage(DecidingStage::PolicyConstraint, 'deny', 'allow_policy_conditions_unsatisfied', [
                'unsatisfied' => $unsatisfied,
            ]);

            return true;
        }

        $stages[] = $this->stage(DecidingStage::PolicyConstraint, 'continue', null, [
            'satisfied' => $satisfied,
            'unsatisfied' => $unsatisfied,
        ]);

        return false;
    }

    private function grantExists(string $membershipId, string $capabilityCode): bool
    {
        return RolePermission::query()
            ->where('capability_code', $capabilityCode)
            ->whereHas('role', function (Builder $role) use ($membershipId): void {
                /** @var Builder<Role> $role */
                $role->where('state', Role::STATE_ACTIVE)
                    ->whereHas('assignments', function (Builder $assignment) use ($membershipId): void {
                        $assignment->where('membership_id', $membershipId);
                    });
            })
            ->exists();
    }

    private function isSensitive(string $capabilityCode): bool
    {
        $metadata = CapabilityMetadata::query()
            ->where('code', $capabilityCode)
            ->first();

        if ($metadata !== null) {
            return (bool) $metadata->sensitive;
        }

        // The per-tenant registry may not be materialized yet (sync
        // pending); fall back to the manifest declaration so machine
        // principals never slip past an unsynced sensitive flag
        // (fail-closed, CON-003).
        $declared = $this->registry->permissions()[$capabilityCode] ?? null;

        return $declared !== null && (bool) $declared['sensitive'];
    }

    private function principalId(?Authenticatable $principal): ?string
    {
        if ($principal === null) {
            return null;
        }

        $id = $principal->getAuthIdentifier();

        return $id === null ? null : (string) $id;
    }

    /**
     * @param  array<string, mixed>  $details
     * @return array<string, mixed>
     */
    private function stage(DecidingStage $stage, string $outcome, ?string $reason, array $details = []): array
    {
        return [
            'stage' => $stage->value,
            'outcome' => $outcome,
            'reason' => $reason,
            'details' => $details,
        ];
    }

    /**
     * @param  list<string>  $evaluatedPolicies
     * @param  list<array<string, mixed>>  $stages
     */
    private function decision(
        string $decision,
        DecidingStage $stage,
        string $capability,
        ?string $principalId,
        array $evaluatedPolicies,
        array $stages,
    ): AuthorizationDecision {
        return new AuthorizationDecision(
            $decision,
            $stage,
            $capability,
            $principalId,
            $evaluatedPolicies,
            $stages,
        );
    }
}
