<?php

namespace App\Http\Controllers\Ops;

use App\Modules\Booking\Models\Booking;
use App\Modules\Pandit\Models\PanditAssignment;
use App\Platform\Tenancy\Models\Tenant;
use App\Platform\Tenancy\TenantScopeGuard;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Ops Temple Dispatcher console surface (T-AP04-04): live view of
 * confirmed bookings, active IN_PROGRESS ceremonies, and pending assignments.
 */
final class DispatchController
{
    private function requestId(Request $request): string
    {
        return (string) $request->attributes->get('request_id', '');
    }

    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate(['tenant_id' => ['required', 'string']]);
        $tenantId = (string) $validated['tenant_id'];

        TenantScopeGuard::assertSameTenant($request, $tenantId);

        $tenant = Tenant::query()->find($tenantId);
        if ($tenant === null || $tenant->state !== Tenant::STATE_ACTIVE) {
            return new JsonResponse([
                'error' => ['code' => 'not_found', 'message' => 'Tenant unavailable.', 'request_id' => $this->requestId($request)],
            ], 404);
        }

        $assignments = PanditAssignment::query()
            ->where('tenant_id', $tenantId)
            ->whereIn('status', [
                PanditAssignment::STATUS_PENDING,
                PanditAssignment::STATUS_CONFIRMED,
                PanditAssignment::STATUS_IN_PROGRESS,
            ])
            ->orderByDesc('assigned_at')
            ->limit(100)
            ->get()
            ->map(static fn (PanditAssignment $a): array => [
                'id' => (string) $a->getKey(),
                'booking_id' => $a->booking_id,
                'pandit_id' => $a->pandit_id,
                'status' => $a->status,
                'assigned_at' => $a->assigned_at?->toJSON(),
                'confirmed_at' => $a->confirmed_at?->toJSON(),
                'service_started_at' => $a->service_started_at?->toJSON(),
            ])
            ->values()
            ->all();

        $assignedBookingIds = PanditAssignment::query()
            ->where('tenant_id', $tenantId)
            ->pluck('booking_id')
            ->all();

        $unassigned = Booking::query()
            ->where('tenant_id', $tenantId)
            ->where('status', 'CONFIRMED')
            ->when($assignedBookingIds !== [], static fn ($q) => $q->whereNotIn('id', $assignedBookingIds))
            ->orderByDesc('created_at')
            ->limit(50)
            ->get()
            ->map(static fn (Booking $b): array => [
                'id' => (string) $b->getKey(),
                'status' => $b->status,
                'slot_starts_at' => $b->slot_starts_at?->toJSON(),
                'total_minor' => $b->total_minor,
                'currency' => $b->currency,
            ])
            ->values()
            ->all();

        return new JsonResponse([
            'data' => [
                'active_assignments' => $assignments,
                'unassigned_bookings' => $unassigned,
            ],
            'meta' => ['request_id' => $this->requestId($request)],
        ]);
    }
}
