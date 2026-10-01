<?php

namespace App\Platform\Authz\Http\Controllers;

use App\Platform\Authz\Models\MembershipRoleAssignment;
use App\Platform\Authz\Models\Role;
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
 * SoD rules land with the sod_rules table (spec'd, unbuilt) — grants
 * are direct until then, fully audited via the decision trail.
 */
final class RoleController
{
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

            $assignment = MembershipRoleAssignment::query()->firstOrCreate([
                'membership_id' => (string) $membership->getKey(),
                'role_id' => (string) $role->getKey(),
            ]);
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
