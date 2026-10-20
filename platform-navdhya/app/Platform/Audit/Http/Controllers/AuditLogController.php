<?php

namespace App\Platform\Audit\Http\Controllers;

use App\Platform\Audit\AuditLog;
use App\Platform\Tenancy\Models\Tenant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Read-only audit-log console surface (W2, plan slice):
 * `GET /api/v1/platform/audit/logs` returns the most recent audit rows for
 * the requesting tenant, newest first. Every row is already hash-chained
 * (prev_hash/hash) and written in the same transaction as the domain change,
 * so this endpoint only reads — it never mutates the chain.
 *
 * Capability `platform:audit:view` gates the read; the tenant is resolved by
 * `ResolveTenantContext` (header) and re-validated from the `tenant_id`
 * query, matching `UserAdminController`'s wire contract.
 */
final class AuditLogController
{
    public function __construct()
    {
    }

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

        $limit = min(max((int) $request->query('limit', 50), 1), 200);

        $rows = AuditLog::query()
            ->where('tenant_id', (string) $tenant->getKey())
            ->orderByDesc('occurred_at')
            ->limit($limit)
            ->get();

        return new JsonResponse([
            'data' => $rows->map(static function (AuditLog $log): array {
                return [
                    'id' => (string) $log->getKey(),
                    'object' => 'audit_log',
                    'tenant_id' => (string) $log->tenant_id,
                    'actor' => $log->actor,
                    'action' => $log->action,
                    'resource' => $log->resource,
                    'request_id' => $log->request_id,
                    'occurred_at' => $log->occurred_at?->toJSON(),
                ];
            })->values()->all(),
            'meta' => ['request_id' => $this->requestId($request), 'limit' => $limit],
        ]);
    }
}
