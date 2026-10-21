<?php

namespace App\Http\Controllers\Ops;

use App\Modules\Booking\Models\Booking;
use App\Platform\Tenancy\TenantScopeGuard;
use App\Platform\Tenancy\Models\Tenant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Cross-module read-only roll-up for the console (W2): platform-wide booking
 * review. Capability `platform:ops:view` gates the read; tenant is resolved by
 * `ResolveTenantContext` (header) and re-validated from the `tenant_id` query.
 */
final class BookingsController
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

        $rows = Booking::query()
            ->where('tenant_id', (string) $tenant->getKey())
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get();

        return new JsonResponse([
            'data' => $rows->map(static function (Booking $booking): array {
                return [
                    'id' => (string) $booking->getKey(),
                    'object' => 'booking',
                    'user_id' => $booking->user_id,
                    'service_sku' => $booking->service_sku,
                    'status' => $booking->status,
                    'scheduled_at' => $booking->scheduled_at?->toJSON(),
                    'total_minor' => $booking->total_minor,
                    'currency' => $booking->currency,
                    'created_at' => $booking->created_at?->toJSON(),
                ];
            })->values()->all(),
            'meta' => ['request_id' => $this->requestId($request), 'limit' => $limit],
        ]);
    }
}
