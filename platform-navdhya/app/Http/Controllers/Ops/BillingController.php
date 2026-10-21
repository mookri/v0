<?php

namespace App\Http\Controllers\Ops;

use App\Platform\Tenancy\TenantScopeGuard;
use App\Platform\Tenancy\Models\Tenant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Read-only billing surface (W2): the configured tenant's plan and currency.
 * There is no invoice store in the kernel yet, so `invoices` is deliberately
 * an empty list with a `note` rather than a fabricated total. Capability
 * `platform:billing:view` gates the read; tenant resolution matches the rest
 * of the platform surfaces.
 */
final class BillingController
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

        return new JsonResponse([
            'data' => [
                'plan_code' => $tenant->plan_code,
                'status' => $tenant->status,
                'base_currency' => $tenant->base_currency,
                'invoices' => [],
            ],
            'meta' => [
                'request_id' => $this->requestId($request),
                'note' => 'Invoice history is not yet wired; only the active plan is reported.',
            ],
        ]);
    }
}
