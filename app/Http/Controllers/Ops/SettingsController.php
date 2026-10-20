<?php

namespace App\Http\Controllers\Ops;

use App\Platform\Tenancy\Models\Tenant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Read-only tenant configuration surface (W2): code, name, timezone, locale,
 * plan and status. Capability `platform:config:manage` gates the read; the
 * tenant is resolved by `ResolveTenantContext` (header) and re-validated from
 * the `tenant_id` query, matching `UserAdminController`'s wire contract.
 */
final class SettingsController
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

        return new JsonResponse([
            'data' => [
                'code' => $tenant->code,
                'name' => $tenant->name,
                'state' => $tenant->state,
                'status' => $tenant->status,
                'plan_code' => $tenant->plan_code,
                'base_currency' => $tenant->base_currency,
                'timezone' => $tenant->timezone,
                'locale' => $tenant->locale,
            ],
            'meta' => ['request_id' => $this->requestId($request)],
        ]);
    }
}
