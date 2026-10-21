<?php

namespace App\Http\Controllers\Ops;

use App\Platform\Audit\AuditLog;
use App\Platform\Identity\Models\MfaStepUp;
use App\Platform\Tenancy\TenantScopeGuard;
use App\Platform\Tenancy\Models\Tenant;
use App\Platform\Tenancy\Models\TenantMembership;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Read-only security overview for the console (W2): MFA adoption (platform-wide,
 * since MFA proofs are user-keyed and not tenant-scoped), authz denial counts
 * for the configured tenant, and total audit volume. Capability
 * `platform:ops:view` gates the read; tenant is resolved by
 * `ResolveTenantContext` (header) and re-validated from the `tenant_id` query.
 */
final class SecurityController
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

        $totalUsers = TenantMembership::query()->where('tenant_id', $tenantId)->count();
        // MFA proofs are user-keyed (central), so this is platform-wide rather
        // than tenant-scoped; reported as-is rather than fabricated per tenant.
        $mfaEnrolled = MfaStepUp::query()->distinct('user_id')->count('user_id');

        $auditRows = AuditLog::query()->where('tenant_id', $tenantId);
        $deniedToday = (clone $auditRows)->where('action', 'authz.denied')->where('occurred_at', '>=', now()->startOfDay())->count();
        $deniedTotal = (clone $auditRows)->where('action', 'authz.denied')->count();

        return new JsonResponse([
            'data' => [
                'mfa' => ['enrolled' => $mfaEnrolled, 'total_users' => $totalUsers],
                'authz' => ['denied_today' => $deniedToday, 'denied_total' => $deniedTotal],
                'audit' => ['total' => (clone $auditRows)->count()],
            ],
            'meta' => ['request_id' => $this->requestId($request)],
        ]);
    }
}
