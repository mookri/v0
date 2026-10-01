<?php

namespace App\Platform\Authz\Http\Controllers;

use App\Platform\Authz\Models\MembershipRoleAssignment;
use App\Platform\Authz\Models\Role;
use App\Platform\Authz\Models\RolePermission;
use App\Platform\Authz\Models\SodRule;
use App\Platform\Authz\Events\SodConflictBlocked;
use App\Platform\Authz\Events\SodWarning;
use App\Platform\Authz\SodEvaluator;
use App\Platform\Audit\AuditWriter;
use App\Platform\Tenancy\ConnectionResolver;
use App\Platform\Tenancy\Models\Tenant;
use App\Platform\Tenancy\Models\TenantMembership;
use App\Platform\Tenancy\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Role + assignment administration (platform-global surfaces, tenant
 * data underneath): roles are tenant-scoped rows, so every mutation
 * resolves the target tenant's context explicitly (body tenant_id +
 * issuer membership check) instead of relying on ambient resolution.
 * Grants pass assignment-time SoD conflict evaluation (SEC-011 §7.2):
 * blocking rules reject with `sod_conflict`, warn rules proceed with a
 * `platform.sod.warning` event; every evaluation is audit-logged.
 */
final class RoleController
{
    public function __construct(
        private readonly AuditWriter $audit,
        private readonly SodEvaluator $sod,
    ) {
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
            $roles = Role::query()->orderBy('code')->get();
        } finally {
            if ($scoped['ownsContext']) {
                $scoped['context']->clear();
            }
        }

        return new JsonResponse([
            'data' => $roles->map(static fn (Role $role): array => [
                'id' => (string) $role->getKey(),
                'object' => 'role',
                'code' => $role->code,
                'name' => $role->name,
                'state' => $role->state,
            ])->all(),
            'meta' => ['request_id' => $this->requestId($request)],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'tenant_id' => ['required', 'string'],
            'code' => ['required', 'string', 'regex:/^[a-z][a-z0-9_]*$/', 'max:64'],
            'name' => ['required', 'string', 'max:190'],
        ]);

        $scoped = $this->scopeToTenant($request, $validated['tenant_id']);
        if ($scoped instanceof JsonResponse) {
            return $scoped;
        }

        try {
            if (Role::query()->where('code', $validated['code'])->exists()) {
                return new JsonResponse([
                    'error' => ['code' => 'conflict', 'message' => 'Role code already exists in this tenant.', 'request_id' => $this->requestId($request)],
                ], 409);
            }

            $role = new Role();
            $role->forceFill([
                'code' => $validated['code'],
                'name' => $validated['name'],
                'state' => Role::STATE_ACTIVE,
            ]);
            $role->save();
        } finally {
            if ($scoped['ownsContext']) {
                $scoped['context']->clear();
            }
        }

        return new JsonResponse([
            'data' => [
                'id' => (string) $role->getKey(),
                'object' => 'role',
                'code' => $role->code,
                'name' => $role->name,
                'state' => $role->state,
            ],
            'meta' => ['request_id' => $this->requestId($request)],
        ], 201);
    }

    public function grant(Request $request, string $membershipId): JsonResponse
    {
        $validated = $request->validate([
            'tenant_id' => ['required', 'string'],
            'role_code' => ['required', 'string'],
        ]);

        $scoped = $this->scopeToTenant($request, $validated['tenant_id']);
        if ($scoped instanceof JsonResponse) {
            return $scoped;
        }

        try {
            $membership = TenantMembership::query()->find($membershipId);
            if ($membership === null) {
                return new JsonResponse([
                    'error' => ['code' => 'not_found', 'message' => 'Membership not found.', 'request_id' => $this->requestId($request)],
                ], 404);
            }

            $role = Role::query()->where('code', $validated['role_code'])->first();
            if ($role === null || $role->state !== Role::STATE_ACTIVE) {
                return new JsonResponse([
                    'error' => ['code' => 'validation_failed', 'message' => 'Unknown or disabled role.', 'request_id' => $this->requestId($request)],
                ], 422);
            }

            $membershipKey = (string) $membership->getKey();
            $existing = MembershipRoleAssignment::query()
                ->where('membership_id', $membershipKey)
                ->where('role_id', (string) $role->getKey())
                ->first();

            // Idempotent re-grant changes nothing, so it skips SoD
            // evaluation (rules added after assignment are the runtime
            // stage's job, SEC-011 §7.3).
            if ($existing === null) {
                $denied = $this->checkSoD($request, (string) $scoped['tenant']->getKey(), $membership, $role);
                if ($denied instanceof JsonResponse) {
                    return $denied;
                }

                $existing = MembershipRoleAssignment::query()->create([
                    'membership_id' => $membershipKey,
                    'role_id' => (string) $role->getKey(),
                ]);
            }

            $assignment = $existing;
        } finally {
            if ($scoped['ownsContext']) {
                $scoped['context']->clear();
            }
        }

        return new JsonResponse([
            'data' => [
                'id' => (string) $assignment->getKey(),
                'object' => 'role_assignment',
                'membership_id' => (string) $membership->getKey(),
                'role_code' => $role->code,
            ],
            'meta' => ['request_id' => $this->requestId($request)],
        ], 201);
    }

    /**
     * Assignment-time SoD evaluation (SEC-011 §7.2): computes the
     * membership's resulting active role + capability sets with the
     * candidate grant applied, evaluates conflict rules, audit-logs every
     * outcome, emits warn/block events, and returns a 403 `sod_conflict`
     * envelope when a blocking rule trips (null = grant may proceed).
     */
    private function checkSoD(Request $request, string $tenantId, TenantMembership $membership, Role $role): ?JsonResponse
    {
        $membershipKey = (string) $membership->getKey();
        $actorId = (string) $request->user()->getAuthIdentifier();

        $roleIds = MembershipRoleAssignment::query()
            ->where('membership_id', $membershipKey)
            ->pluck('role_id')->all();
        $roleIds[] = (string) $role->getKey();
        $roleIds = array_values(array_unique($roleIds));

        $roles = Role::query()->whereIn('id', $roleIds)->where('state', Role::STATE_ACTIVE)->get();
        $roleCodes = $roles->map(static fn (Role $assigned): string => $assigned->code)->all();
        $capabilities = RolePermission::query()
            ->whereIn('role_id', $roles->map(static fn (Role $assigned): string => (string) $assigned->getKey())->all())
            ->distinct()->pluck('capability_code')->all();

        $verdict = $this->sod->evaluate($tenantId, $roleCodes, $capabilities);
        $blocks = $verdict['blocks'];
        $warnings = $verdict['warnings'];

        // Self-grant (sod.role_grant_self): the issuer IS the target member.
        // Needs identity comparison, not permission sets, so it is checked
        // here against the rule's effective severity.
        $selfSeverity = ((string) $membership->user_id === $actorId)
            ? $this->sod->selfGrantSeverity($tenantId)
            : null;
        if ($selfSeverity === SodRule::SEVERITY_WARN) {
            $warnings[] = 'sod.role_grant_self';
        } elseif ($selfSeverity !== null) {
            $blocks[] = 'sod.role_grant_self';
        }

        $this->audit->record(
            'sod.evaluated',
            ['module' => 'platform', 'resource_type' => 'role_assignment', 'resource_id' => $membershipKey],
            null,
            ['membership_id' => $membershipKey, 'role_code' => $role->code, 'blocks' => $blocks, 'warnings' => $warnings]
        );

        if ($warnings !== []) {
            event(new SodWarning($tenantId, $membershipKey, $role->code, $warnings, $actorId));
        }

        if ($blocks !== []) {
            event(new SodConflictBlocked($tenantId, $membershipKey, $role->code, $blocks, $actorId));

            return new JsonResponse([
                'error' => [
                    'code' => 'sod_conflict',
                    'message' => 'Role grant violates separation-of-duties rules.',
                    'details' => ['rules' => $blocks],
                    'request_id' => $this->requestId($request),
                ],
            ], 403);
        }

        return null;
    }

    public function revoke(Request $request, string $membershipId, string $assignmentId): JsonResponse
    {
        $validated = $request->validate(['tenant_id' => ['required', 'string']]);

        $scoped = $this->scopeToTenant($request, $validated['tenant_id']);
        if ($scoped instanceof JsonResponse) {
            return $scoped;
        }

        try {
            $assignment = MembershipRoleAssignment::query()
                ->where('id', $assignmentId)
                ->first();
            if ($assignment === null || (string) $assignment->membership_id !== $membershipId) {
                return new JsonResponse([
                    'error' => ['code' => 'not_found', 'message' => 'Assignment not found.', 'request_id' => $this->requestId($request)],
                ], 404);
            }
            $assignment->delete();
        } finally {
            if ($scoped['ownsContext']) {
                $scoped['context']->clear();
            }
        }

        return new JsonResponse([
            'data' => ['object' => 'role_assignment_revocation', 'revoked' => true],
            'meta' => ['request_id' => $this->requestId($request)],
        ]);
    }
}
