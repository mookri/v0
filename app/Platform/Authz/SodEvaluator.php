<?php

namespace App\Platform\Authz;

use App\Platform\Authz\Models\SodRule;

/**
 * Segregation-of-duties conflict evaluator (SEC-011 §7.1–7.2).
 *
 * Pure evaluation — no writes, no HTTP. The caller (role grant today, the
 * policy engine's runtime stage later) supplies the *resulting* role and
 * capability sets; this returns the blocking and warning rule codes.
 *
 * Rule resolution: platform defaults (tenant_id NULL) plus tenant rows;
 * a tenant row with the same code replaces the platform row wholesale
 * (downgrade block→warn, or deactivation). Only `conflict` rules are
 * evaluated here — `approval_chain` rules belong to the workflow engine.
 * A rule triggers when ANY non-empty set is fully held (all listed roles,
 * or all listed permissions).
 */
final class SodEvaluator
{
    /**
     * @param list<string> $resultingRoleCodes
     * @param list<string> $resultingCapabilities
     * @return array{blocks: list<string>, warnings: list<string>}
     */
    public function evaluate(string $tenantId, array $resultingRoleCodes, array $resultingCapabilities): array
    {
        $blocks = [];
        $warnings = [];

        foreach ($this->activeConflictRules($tenantId) as $rule) {
            if ($rule->code === 'sod.role_grant_self') {
                continue;
            }

            if (! $this->triggered($rule, $resultingRoleCodes, $resultingCapabilities)) {
                continue;
            }

            if ($rule->severity === SodRule::SEVERITY_WARN) {
                $warnings[] = $rule->code;
            } else {
                $blocks[] = $rule->code;
            }
        }

        return ['blocks' => $blocks, 'warnings' => $warnings];
    }

    /**
     * Effective severity of the self-grant rule for a tenant: 'block',
     * 'warn', or null when no active row applies. Self-grant needs issuer
     * vs target identity (not permission sets), so the grant endpoint
     * consults this separately.
     */
    public function selfGrantSeverity(string $tenantId): ?string
    {
        $rule = $this->activeConflictRules($tenantId)['sod.role_grant_self'] ?? null;

        return $rule?->severity;
    }

    /**
     * @return array<string, SodRule> keyed by code, tenant override wins
     */
    private function activeConflictRules(string $tenantId): array
    {
        $rows = SodRule::query()
            ->where('kind', SodRule::KIND_CONFLICT)
            ->where('status', SodRule::STATUS_ACTIVE)
            ->where(function ($query) use ($tenantId): void {
                $query->whereNull('tenant_id')->orWhere('tenant_id', $tenantId);
            })
            ->get();

        $byCode = [];
        foreach ($rows as $rule) {
            // Order-independent merge: a tenant row always replaces the
            // platform row with the same code; platform rows never replace
            // each other or a tenant row.
            if (! isset($byCode[$rule->code]) || $rule->tenant_id !== null) {
                $byCode[$rule->code] = $rule;
            }
        }

        return $byCode;
    }

    /**
     * @param list<string> $roleCodes
     * @param list<string> $capabilities
     */
    private function triggered(SodRule $rule, array $roleCodes, array $capabilities): bool
    {
        $roles = array_values($rule->role_set ?? []);
        if ($roles !== [] && array_diff($roles, $roleCodes) === []) {
            return true;
        }

        $permissions = array_values($rule->permission_set ?? []);
        if ($permissions !== [] && array_diff($permissions, $capabilities) === []) {
            return true;
        }

        return false;
    }
}
