<?php

namespace App\Http\Controllers\Ops;

use App\Platform\Tenancy\Models\Tenant;
use App\Platform\Tenancy\Models\TenantMembership;
use App\Platform\Authz\Models\Role;
use App\Platform\Audit\AuditLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Cross-module read-only roll-up for the console (W2): a single aggregate
 * the SuperAdmin overview can render without five parallel dangling calls.
 * Capability `platform:ops:view` gates the read; the tenant is resolved by
 * `ResolveTenantContext` (header) and re-validated from the `tenant_id` query.
 */
final class StatsController
{
    private function requestId(Request $request): string
    {
        return (string) $request->attributes->get('request_id', '');
    }

    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate(['tenant_id' => ['required', 'string']]);

        $tenant = Tenant::query()->find($validated['tenant_id']);
        if ($tenant === null || $tenant->state !== Tenant::STATE_ACTIVE) {
            return new JsonResponse([
                'error' => ['code' => 'not_found', 'message' => 'Tenant unavailable.', 'request_id' => $this->requestId($request)],
            ], 404);
        }

        $tenantId = (string) $tenant->getKey();

        $memberships = TenantMembership::query()->where('tenant_id', $tenantId);
        $totalUsers = (clone $memberships)->count();
        $activeUsers = (clone $memberships)->where('state', TenantMembership::STATE_ACTIVE)->count();

        $totalRoles = Role::query()->count();

        $auditRows = AuditLog::query()->where('tenant_id', $tenantId);
        $totalAudit = (clone $auditRows)->count();
        $deniedToday = (clone $auditRows)
            ->where('action', 'authz.denied')
            ->where('occurred_at', '>=', now()->startOfDay())
            ->count();

        return new JsonResponse([
            'data' => [
                'users' => ['total' => $totalUsers, 'active' => $activeUsers],
                'roles' => ['total' => $totalRoles],
                'audit' => ['total' => $totalAudit, 'denied_today' => $deniedToday],
                'tenant' => ['code' => $tenant->code, 'name' => $tenant->name, 'state' => $tenant->state],
            ],
            'meta' => ['request_id' => $this->requestId($request)],
        ]);
    }
}
