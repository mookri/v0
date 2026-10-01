<?php

namespace App\Platform\Authz\Http\Controllers;

use App\Platform\Api\CursorPagination;
use App\Platform\Audit\AuditWriter;
use App\Platform\Authz\Events\SodRuleCreated;
use App\Platform\Authz\Events\SodRuleUpdated;
use App\Platform\Authz\Models\SodRule;
use App\Platform\Tenancy\ConnectionResolver;
use App\Platform\Tenancy\Models\Tenant;
use App\Platform\Tenancy\Models\TenantMembership;
use App\Platform\Tenancy\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * SoD rule administration (SEC-011 §7, tenant rows): tenants add custom
 * conflict rules and override platform defaults by code (downgrade
 * block→warn). Platform rows are immutable here — mutating or deleting
 * one returns 409 pointing at the override flow. Removing a platform
 * block (status disabled over an active platform block) is rejected:
 * operator consent has no issuer yet, so consent is impossible and the
 * request fails closed (422). Every mutation is audit-logged and emits
 * its platform event; rule changes bump decision-cache generations via
 * the SodRule model hooks.
 */
final class SodRuleController
{
    public function __construct(private readonly AuditWriter $audit)
    {
    }

    private function requestId(Request $request): string
    {
        return (string) $request->attributes->get('request_id', '');
    }

    /**
     * @return array{tenant: Tenant, ownsContext: bool, context: TenantContext}|JsonResponse
     */
    private function scopeToTenant(Request $request, string $tenantId): array|JsonResponse
    {
        if ($request->user() instanceof \App\Platform\Identity\MachinePrincipal) {
            return new JsonResponse([
                'error' => ['code' => 'membership_inactive', 'message' => 'No active membership in this tenant.', 'request_id' => $this->requestId($request)],
            ], 403);
        }

        $tenant = Tenant::query()->find($tenantId);
        if ($tenant === null || $tenant->state !== Tenant::STATE_ACTIVE) {
            return new JsonResponse([
                'error' => ['code' => 'not_found', 'message' => 'Tenant unavailable.', 'request_id' => $this->requestId($request)],
            ], 404);
        }

        $membership = TenantMembership::query()
            ->where('tenant_id', (string) $tenant->getKey())
            ->where('user_id', (string) $request->user()->getAuthIdentifier())
            ->where('state', TenantMembership::STATE_ACTIVE)
            ->first();
        if ($membership === null) {
            return new JsonResponse([
                'error' => ['code' => 'membership_inactive', 'message' => 'No active membership in this tenant.', 'request_id' => $this->requestId($request)],
            ], 403);
        }

        $context = app(TenantContext::class);
        $ownsContext = ! $context->isResolved();
        if ($ownsContext) {
            $context->set(
                (string) $tenant->getKey(),
                (string) $membership->getKey(),
                $membership->org_node_id,
                (new ConnectionResolver())->cachedFor($tenant),
                ['user_id' => (string) $request->user()->getAuthIdentifier()]
            );
        }

        return ['tenant' => $tenant, 'ownsContext' => $ownsContext, 'context' => $context];
    }

    public function index(Request $request): JsonResponse
    {
        $tenantId = (string) $request->query('tenant_id', '');
        if ($tenantId === '') {
            return new JsonResponse([
                'error' => ['code' => 'validation_failed', 'message' => 'tenant_id query is required.', 'request_id' => $this->requestId($request)],
            ], 422);
        }

        $scoped = $this->scopeToTenant($request, $tenantId);
        if ($scoped instanceof JsonResponse) {
            return $scoped;
        }

        try {
            [$rules, $page] = CursorPagination::paginate($this->effectiveQuery($tenantId), $request);
        } finally {
            if ($scoped['ownsContext']) {
                $scoped['context']->clear();
            }
        }

        return new JsonResponse([
            'data' => array_map(
                fn (SodRule $rule): array => self::serialize($rule, $tenantId),
                $rules
            ),
            'meta' => ['request_id' => $this->requestId($request), 'page' => $page],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'tenant_id' => ['required', 'string'],
            'code' => ['required', 'string', 'regex:/^sod\.[a-z][a-z0-9_.]*$/', 'max:64'],
            'name' => ['required', 'string', 'max:190'],
            'kind' => ['sometimes', 'string', 'in:conflict'],
            'role_set' => ['nullable', 'array'],
            'role_set.*' => ['string'],
            'permission_set' => ['nullable', 'array'],
            'permission_set.*' => ['string'],
            'severity' => ['sometimes', 'string', 'in:block,warn'],
            'status' => ['sometimes', 'string', 'in:active,disabled'],
        ]);

        $scoped = $this->scopeToTenant($request, $validated['tenant_id']);
        if ($scoped instanceof JsonResponse) {
            return $scoped;
        }
        $tenantId = (string) $scoped['tenant']->getKey();

        try {
            if (SodRule::query()->where('tenant_id', $tenantId)->where('code', $validated['code'])->exists()) {
                return new JsonResponse([
                    'error' => ['code' => 'conflict', 'message' => 'A rule with this code already exists in this tenant.', 'request_id' => $this->requestId($request)],
                ], 409);
            }

            $sets = $this->setsOrComplain($request, $validated);
            if ($sets instanceof JsonResponse) {
                return $sets;
            }

            $status = $validated['status'] ?? SodRule::STATUS_ACTIVE;
            $denied = $this->blockRemovalOrComplain($request, $tenantId, $validated['code'], $status);
            if ($denied instanceof JsonResponse) {
                return $denied;
            }

            $rule = new SodRule();
            $rule->forceFill([
                'tenant_id' => $tenantId,
                'code' => $validated['code'],
                'name' => $validated['name'],
                'kind' => $validated['kind'] ?? SodRule::KIND_CONFLICT,
                'role_set' => $sets['role_set'],
                'permission_set' => $sets['permission_set'],
                'severity' => $validated['severity'] ?? SodRule::SEVERITY_BLOCK,
                'status' => $status,
            ]);
            $rule->save();

            $this->audit->record(
                'sod.rule.created',
                ['module' => 'platform', 'resource_type' => 'sod_rule', 'resource_id' => (string) $rule->getKey()],
                null,
                ['tenant_id' => $tenantId, 'code' => $rule->code]
            );
            event(new SodRuleCreated($rule, (string) $request->user()->getAuthIdentifier()));
        } finally {
            if ($scoped['ownsContext']) {
                $scoped['context']->clear();
            }
        }

        return new JsonResponse([
            'data' => self::serialize($rule, $tenantId),
            'meta' => ['request_id' => $this->requestId($request)],
        ], 201);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $validated = $request->validate([
            'tenant_id' => ['required', 'string'],
            'name' => ['sometimes', 'string', 'max:190'],
            'role_set' => ['sometimes', 'nullable', 'array'],
            'role_set.*' => ['string'],
            'permission_set' => ['sometimes', 'nullable', 'array'],
            'permission_set.*' => ['string'],
            'severity' => ['sometimes', 'string', 'in:block,warn'],
            'status' => ['sometimes', 'string', 'in:active,disabled'],
        ]);

        $scoped = $this->scopeToTenant($request, $validated['tenant_id']);
        if ($scoped instanceof JsonResponse) {
            return $scoped;
        }
        $tenantId = (string) $scoped['tenant']->getKey();

        try {
            $rule = SodRule::query()->where('id', $id)->where('tenant_id', $tenantId)->first();

            if ($rule === null) {
                $platform = SodRule::query()->where('id', $id)->whereNull('tenant_id')->first();
                if ($platform !== null) {
                    return new JsonResponse([
                        'error' => ['code' => 'conflict', 'message' => 'Platform defaults are immutable; override by creating a tenant rule with the same code.', 'request_id' => $this->requestId($request)],
                    ], 409);
                }

                return new JsonResponse([
                    'error' => ['code' => 'not_found', 'message' => 'Rule not found.', 'request_id' => $this->requestId($request)],
                ], 404);
            }

            $before = [
                'name' => $rule->name, 'role_set' => $rule->role_set, 'permission_set' => $rule->permission_set,
                'severity' => $rule->severity, 'status' => $rule->status,
            ];

            $changes = array_intersect_key($validated, ['name' => true, 'role_set' => true, 'permission_set' => true, 'severity' => true, 'status' => true]);
            $candidateSets = [
                'role_set' => array_key_exists('role_set', $changes) ? $changes['role_set'] : $rule->role_set,
                'permission_set' => array_key_exists('permission_set', $changes) ? $changes['permission_set'] : $rule->permission_set,
            ];
            if (($candidateSets['role_set'] ?? []) === [] && ($candidateSets['permission_set'] ?? []) === []) {
                return new JsonResponse([
                    'error' => [
                        'code' => 'validation_failed',
                        'message' => 'A rule needs at least one non-empty set.',
                        'details' => ['fields' => ['role_set' => ['empty_rule'], 'permission_set' => ['empty_rule']]],
                        'request_id' => $this->requestId($request),
                    ],
                ], 422);
            }

            $denied = $this->blockRemovalOrComplain($request, $tenantId, $rule->code, $changes['status'] ?? $rule->status);
            if ($denied instanceof JsonResponse) {
                return $denied;
            }

            $rule->forceFill($changes)->save();

            $this->audit->record(
                'sod.rule.updated',
                ['module' => 'platform', 'resource_type' => 'sod_rule', 'resource_id' => (string) $rule->getKey()],
                $before,
                ['tenant_id' => $tenantId, 'code' => $rule->code]
            );
            event(new SodRuleUpdated($rule->fresh(), (string) $request->user()->getAuthIdentifier()));
        } finally {
            if ($scoped['ownsContext']) {
                $scoped['context']->clear();
            }
        }

        return new JsonResponse([
            'data' => self::serialize($rule->fresh(), $tenantId),
            'meta' => ['request_id' => $this->requestId($request)],
        ]);
    }

    public function destroy(Request $request, string $id): JsonResponse
    {
        $validated = $request->validate(['tenant_id' => ['required', 'string']]);

        $scoped = $this->scopeToTenant($request, $validated['tenant_id']);
        if ($scoped instanceof JsonResponse) {
            return $scoped;
        }
        $tenantId = (string) $scoped['tenant']->getKey();

        try {
            $rule = SodRule::query()->where('id', $id)->where('tenant_id', $tenantId)->first();

            if ($rule === null) {
                $platform = SodRule::query()->where('id', $id)->whereNull('tenant_id')->first();
                if ($platform !== null) {
                    return new JsonResponse([
                        'error' => ['code' => 'conflict', 'message' => 'Platform defaults cannot be deleted; disable via tenant override where permitted.', 'request_id' => $this->requestId($request)],
                    ], 409);
                }

                return new JsonResponse([
                    'error' => ['code' => 'not_found', 'message' => 'Rule not found.', 'request_id' => $this->requestId($request)],
                ], 404);
            }

            $rule->delete();

            $this->audit->record(
                'sod.rule.deleted',
                ['module' => 'platform', 'resource_type' => 'sod_rule', 'resource_id' => $id],
                null,
                ['tenant_id' => $tenantId, 'code' => $rule->code]
            );
        } finally {
            if ($scoped['ownsContext']) {
                $scoped['context']->clear();
            }
        }

        // Deleting an override resurfaces the platform default (if any) —
        // the evaluator merges by code on every call, nothing to rebuild.
        return new JsonResponse([
            'data' => ['object' => 'sod_rule_deletion', 'deleted' => true],
            'meta' => ['request_id' => $this->requestId($request)],
        ]);
    }

    /**
     * Platform defaults + tenant rows; the evaluator merges by code on
     * every call, so the list shows both (clients see which rows their
     * tenant overrides via matching codes). Ordering is the platform
     * cursor default (newest first) — CursorPagination owns row order,
     * never per-endpoint orderBy.
     *
     * @return \Illuminate\Database\Eloquent\Builder<SodRule>
     */
    private function effectiveQuery(string $tenantId): \Illuminate\Database\Eloquent\Builder
    {
        return SodRule::query()
            ->where('kind', SodRule::KIND_CONFLICT)
            ->where(function ($query) use ($tenantId): void {
                $query->whereNull('tenant_id')->orWhere('tenant_id', $tenantId);
            });
    }

    /**
     * @return array<string, mixed>
     */
    private static function serialize(SodRule $rule, string $tenantId): array
    {
        return [
            'id' => (string) $rule->getKey(),
            'object' => 'sod_rule',
            'code' => $rule->code,
            'name' => $rule->name,
            'kind' => $rule->kind,
            'role_set' => $rule->role_set ?? [],
            'permission_set' => $rule->permission_set ?? [],
            'severity' => $rule->severity,
            'status' => $rule->status,
            'source' => $rule->tenant_id === null ? 'platform' : 'tenant',
            'overrides_platform' => $rule->tenant_id !== null && $rule->tenant_id === $tenantId,
        ];
    }

    /**
     * @param array<string, mixed> $validated
     * @return array{role_set: array<string>|null, permission_set: array<string>|null}|JsonResponse
     */
    private function setsOrComplain(Request $request, array $validated): array|JsonResponse
    {
        $roleSet = $validated['role_set'] ?? null;
        $permissionSet = $validated['permission_set'] ?? null;

        if (($roleSet ?? []) === [] && ($permissionSet ?? []) === []) {
            return new JsonResponse([
                'error' => [
                    'code' => 'validation_failed',
                    'message' => 'A rule needs at least one non-empty set.',
                    'details' => ['fields' => ['role_set' => ['empty_rule'], 'permission_set' => ['empty_rule']]],
                    'request_id' => $this->requestId($request),
                ],
            ], 422);
        }

        return ['role_set' => $roleSet, 'permission_set' => $permissionSet];
    }

    /**
     * Fail-closed block removal: disabling a code with an active platform
     * block is rejected — operator consent has no issuer yet, so consent
     * is impossible and the request cannot proceed (SEC-011 §7.1).
     * Downgrades (block→warn) and custom-rule lifecycle pass through.
     */
    private function blockRemovalOrComplain(Request $request, string $tenantId, string $code, string $status): ?JsonResponse
    {
        if ($status !== SodRule::STATUS_DISABLED) {
            return null;
        }

        $platformBlock = SodRule::query()
            ->whereNull('tenant_id')
            ->where('code', $code)
            ->where('kind', SodRule::KIND_CONFLICT)
            ->where('severity', SodRule::SEVERITY_BLOCK)
            ->where('status', SodRule::STATUS_ACTIVE)
            ->exists();

        if (! $platformBlock) {
            return null;
        }

        return new JsonResponse([
            'error' => [
                'code' => 'validation_failed',
                'message' => 'Platform blocks cannot be removed without platform operator consent.',
                'details' => ['fields' => ['status' => ['operator_consent_required']]],
                'request_id' => $this->requestId($request),
            ],
        ], 422);
    }
}
