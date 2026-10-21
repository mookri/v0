<?php

namespace App\Http\Controllers\Ops;

use App\Modules\Vendor\Models\VendorPayout;
use App\Platform\Tenancy\TenantScopeGuard;
use App\Platform\Tenancy\Models\Tenant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Cross-module read-only roll-up for the console (W2): platform-wide payout
 * review. VendorPayout is tenant_shared and BelongsToTenant, so rows are
 * scoped to the configured tenant. Capability `platform:ops:view` gates the
 * read; tenant is resolved by `ResolveTenantContext` (header) and re-validated
 * from the `tenant_id` query.
 */
final class PayoutsController
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

        $limit = min(max((int) $request->query('limit', 50), 1), 200);

        $rows = VendorPayout::query()
            ->where('tenant_id', (string) $tenant->getKey())
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get();

        return new JsonResponse([
            'data' => $rows->map(static function (VendorPayout $payout): array {
                return [
                    'id' => (string) $payout->getKey(),
                    'object' => 'vendor_payout',
                    'vendor_id' => $payout->vendor_id,
                    'period' => $payout->period,
                    'status' => $payout->status,
                    'amount_minor' => $payout->amount_minor,
                    'currency' => $payout->currency,
                    'reference' => $payout->reference,
                    'created_at' => $payout->created_at?->toJSON(),
                ];
            })->values()->all(),
            'meta' => ['request_id' => $this->requestId($request), 'limit' => $limit],
        ]);
    }
}
