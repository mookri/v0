<?php

namespace App\Modules\Temple\Http\Controllers;

use App\Modules\Temple\Contracts\ProvidesTempleDashboard;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Temple dashboard controller (partner self-serve).
 */
final class TempleDashboardController
{
    public function __construct(private readonly ProvidesTempleDashboard $dashboard)
    {
    }

    private function requestId(Request $request): string
    {
        return (string) $request->attributes->get('request_id', '');
    }

    // PARTNER: Dashboard overview
    public function overview(Request $request, string $templeId): JsonResponse
    {
        $data = $this->dashboard->overview($templeId);

        return new JsonResponse([
            'data' => $data,
            'meta' => ['request_id' => $this->requestId($request)],
        ]);
    }

    // PARTNER: Dashboard pujas
    public function pujas(Request $request, string $templeId): JsonResponse
    {
        $request->validate([
            'limit' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'status' => ['sometimes', 'string'],
            'puja_id' => ['sometimes', 'string'],
        ]);

        $filters = $request->only(['status', 'puja_id']);
        $limit = min((int) $request->query('limit', 20), 100);
        $cursor = is_string($request->query('cursor')) ? $request->query('cursor') : null;

        $result = $this->dashboard->pujas($templeId, $filters, $limit, $cursor);

        return new JsonResponse([
            'data' => $result['items'],
            'meta' => ['request_id' => $this->requestId($request), 'page' => $result['page']],
        ]);
    }

    // PARTNER: Dashboard bookings
    public function bookings(Request $request, string $templeId): JsonResponse
    {
        $request->validate([
            'limit' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'status' => ['sometimes', 'string'],
            'date_from' => ['sometimes', 'date'],
            'date_to' => ['sometimes', 'date'],
        ]);

        $filters = $request->only(['status', 'date_from', 'date_to']);
        $limit = min((int) $request->query('limit', 20), 100);
        $cursor = is_string($request->query('cursor')) ? $request->query('cursor') : null;

        $result = $this->dashboard->bookings($templeId, $filters, $limit, $cursor);

        return new JsonResponse([
            'data' => $result['items'],
            'meta' => ['request_id' => $this->requestId($request), 'page' => $result['page']],
        ]);
    }

    // PARTNER: Dashboard calendar
    public function calendar(Request $request, string $templeId): JsonResponse
    {
        $request->validate([
            'year' => ['required', 'integer', 'min:2020', 'max:2030'],
            'month' => ['required', 'integer', 'min:1', 'max:12'],
        ]);

        $data = $this->dashboard->calendar($templeId, (int) $request->query('year'), (int) $request->query('month'));

        return new JsonResponse([
            'data' => $data['items'],
            'meta' => ['request_id' => $this->requestId($request)],
        ]);
    }

    // PARTNER: Dashboard analytics
    public function analytics(Request $request, string $templeId): JsonResponse
    {
        $request->validate([
            'months' => ['sometimes', 'integer', 'min:1', 'max:24'],
        ]);

        $months = min((int) $request->query('months', 12), 24);

        $data = $this->dashboard->analytics($templeId, $months);

        return new JsonResponse([
            'data' => $data,
            'meta' => ['request_id' => $this->requestId($request)],
        ]);
    }

    // PARTNER: Dashboard settings
    public function settings(Request $request, string $templeId): JsonResponse
    {
        $data = $this->dashboard->settings($templeId);

        return new JsonResponse([
            'data' => $data,
            'meta' => ['request_id' => $this->requestId($request)],
        ]);
    }

    // PARTNER: Council Approvals (C3, WP6)
    public function councilApprovals(Request $request, string $templeId): JsonResponse
    {
        $tenantId = (string) app(\App\Platform\Tenancy\TenantContext::class)->tenantId();
        $state = (string) $request->query('state', 'PENDING');

        $query = \App\Modules\Temple\Models\TempleBookingApproval::query()
            ->where('tenant_id', $tenantId)
            ->where('temple_id', $templeId);

        if ($state !== 'ALL') {
            $query->where('state', $state);
        }

        $approvals = $query->orderByDesc('created_at')->get();

        return new JsonResponse([
            'data' => $approvals,
            'meta' => ['request_id' => $this->requestId($request)],
        ]);
    }

    public function decideCouncilApproval(Request $request, string $templeId, string $approvalId): JsonResponse
    {
        $request->validate([
            'decision' => ['required', 'string', 'in:APPROVED,DECLINED'],
            'reason' => ['sometimes', 'nullable', 'string', 'max:500'],
        ]);

        $tenantId = (string) app(\App\Platform\Tenancy\TenantContext::class)->tenantId();
        $actorUserId = (int) ($request->user()?->getKey() ?? 0);
        $decision = (string) $request->input('decision');
        $reason = $request->input('reason');

        /** @var \App\Modules\Temple\Contracts\ManagesTempleApprovals $approvalsManager */
        $approvalsManager = app(\App\Modules\Temple\Contracts\ManagesTempleApprovals::class);
        $result = $approvalsManager->recordDecision($tenantId, $approvalId, $actorUserId, $decision, $reason);

        return new JsonResponse([
            'data' => $result,
            'meta' => ['request_id' => $this->requestId($request)],
        ]);
    }
}
