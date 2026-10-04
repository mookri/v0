<?php

namespace App\Modules\Pandit\Http\Controllers;

use App\Modules\Booking\Contracts\ReadsBookings;
use App\Modules\Pandit\Models\PanditAssignment;
use App\Modules\Pandit\Models\PanditProfile;
use App\Platform\Tenancy\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Pandit review controller (self-serve): reviews are read through the
 * Booking module's ReadsBookings port — booking rows are never imported.
 */
final class PanditReviewController
{
    public function __construct(private readonly ReadsBookings $bookings)
    {
    }

    private function requestId(Request $request): string
    {
        return (string) $request->attributes->get('request_id', '');
    }

    // SELF: Get own reviews (from bookings assigned to this pandit)
    public function ownIndex(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user instanceof \App\Models\User) {
            return new JsonResponse([
                'error' => ['code' => 'unauthenticated', 'message' => 'Unauthenticated.', 'request_id' => $this->requestId($request)],
            ], 401);
        }

        $request->validate([
            'limit' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ]);
        $limit = min((int) $request->query('limit', 100), 100);

        $tenantId = (string) app(TenantContext::class)->tenantId();
        $profile = PanditProfile::query()
            ->where('tenant_id', $tenantId)
            ->where('user_id', $user->getAuthIdentifier())
            ->first();

        if ($profile === null) {
            return new JsonResponse([
                'error' => ['code' => 'PANDIT_NOT_FOUND', 'message' => 'Pandit profile not found.', 'request_id' => $this->requestId($request)],
            ], 404);
        }

        // Reviews only count for bookings assigned to this pandit
        $assignedBookingIds = PanditAssignment::query()
            ->where('tenant_id', $tenantId)
            ->where('pandit_id', (string) $profile->getKey())
            ->pluck('booking_id')
            ->all();

        $reviews = $this->bookings->reviewsForBookings($assignedBookingIds, $limit);

        return new JsonResponse([
            'data' => $reviews,
            'meta' => ['request_id' => $this->requestId($request)],
        ]);
    }
}
