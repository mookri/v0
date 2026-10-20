<?php

namespace App\Modules\Pandit\Http\Controllers;

use App\Modules\Pandit\Models\PanditAssignment;
use App\Modules\Pandit\Models\PanditProfile;
use App\Platform\Tenancy\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Pandit earnings controller (self-serve, read-only v1).
 */
final class PanditEarningsController
{
    private function requestId(Request $request): string
    {
        return (string) $request->attributes->get('request_id', '');
    }

    // SELF: Get own earnings summary
    public function own(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user instanceof \App\Models\User) {
            return new JsonResponse([
                'error' => ['code' => 'unauthenticated', 'message' => 'Unauthenticated.', 'request_id' => $this->requestId($request)],
            ], 401);
        }

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

        $completed = PanditAssignment::query()
            ->where('tenant_id', $tenantId)
            ->where('pandit_id', (string) $profile->getKey())
            ->where('status', PanditAssignment::STATUS_COMPLETED)
            ->get();

        $pending = PanditAssignment::query()
            ->where('tenant_id', $tenantId)
            ->where('pandit_id', (string) $profile->getKey())
            ->whereIn('status', [PanditAssignment::STATUS_PENDING, PanditAssignment::STATUS_CONFIRMED])
            ->get();

        $totalEarnings = (int) $completed->sum('compensation_minor');
        $pendingEarnings = (int) $pending->sum('compensation_minor');

        return new JsonResponse([
            'data' => [
                'pandit_profile_id' => (string) $profile->getKey(),
                'total_earnings_minor' => $totalEarnings,
                'total_earnings_formatted' => $this->formatCurrency($totalEarnings),
                'completed_assignments_count' => $completed->count(),
                'pending_earnings_minor' => $pendingEarnings,
                'pending_earnings_formatted' => $this->formatCurrency($pendingEarnings),
                'pending_assignments_count' => $pending->count(),
            ],
            'meta' => ['request_id' => $this->requestId($request)],
        ]);
    }

    private function formatCurrency(int $minor): string
    {
        return number_format($minor / 100, 2, '.', ',');
    }
}
