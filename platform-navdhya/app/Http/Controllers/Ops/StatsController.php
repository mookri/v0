<?php

namespace App\Http\Controllers\Ops;

use App\Platform\Tenancy\TenantScopeGuard;
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

        TenantScopeGuard::assertSameTenant($request, (string) $validated['tenant_id']);

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

        $asOf = now('UTC')->toIso8601String();
        $activeRatePct = $totalUsers > 0 ? round(($activeUsers / $totalUsers) * 100, 1) : 0.0;

        return new JsonResponse([
            'data' => [
                'users' => ['total' => $totalUsers, 'active' => $activeUsers],
                'roles' => ['total' => $totalRoles],
                'audit' => ['total' => $totalAudit, 'denied_today' => $deniedToday],
                'tenant' => ['code' => $tenant->code, 'name' => $tenant->name, 'state' => $tenant->state],
                'metrics' => [
                    [
                        'key' => 'active_memberships',
                        'label' => 'Active Members',
                        'value' => $activeUsers,
                        'unit' => 'members',
                        'numerator' => 'TenantMembership(state=active)',
                        'denominator' => 'TenantMembership(all)',
                        'formula' => "count(active) = {$activeUsers} / {$totalUsers} ({$activeRatePct}%)",
                        'as_of' => $asOf,
                    ],
                    [
                        'key' => 'configured_roles',
                        'label' => 'Configured Roles',
                        'value' => $totalRoles,
                        'unit' => 'roles',
                        'numerator' => 'Role(tenant)',
                        'denominator' => 'N/A',
                        'formula' => 'count(roles.id)',
                        'as_of' => $asOf,
                    ],
                    [
                        'key' => 'audit_events_total',
                        'label' => 'Audit Log Entries',
                        'value' => $totalAudit,
                        'unit' => 'events',
                        'numerator' => 'AuditLog(tenant)',
                        'denominator' => 'N/A',
                        'formula' => 'count(audit_logs.id)',
                        'as_of' => $asOf,
                    ],
                    [
                        'key' => 'authz_denials_today',
                        'label' => 'Authz Denials Today',
                        'value' => $deniedToday,
                        'unit' => 'denials',
                        'numerator' => "AuditLog(action='authz.denied', occurred_at >= startOfDay)",
                        'denominator' => 'AuditLog(today)',
                        'formula' => 'count(authz.denied since 00:00 UTC)',
                        'as_of' => $asOf,
                    ],
                ],
            ],
            'meta' => ['request_id' => $this->requestId($request)],
        ]);
    }
}
