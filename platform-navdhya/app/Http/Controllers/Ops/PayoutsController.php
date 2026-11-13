<?php

namespace App\Http\Controllers\Ops;

use App\Modules\Finance\Domain\Exceptions\MakerCheckerViolationException;
use App\Modules\Finance\Domain\MakerCheckerPayoutService;
use App\Modules\Finance\Models\AccountingPeriodLock;
use App\Modules\Finance\Models\Payout;
use App\Modules\Vendor\Models\VendorPayout;
use App\Platform\Api\CodedHttpException;
use App\Platform\Tenancy\Models\Tenant;
use App\Platform\Tenancy\TenantScopeGuard;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use RuntimeException;

/**
 * Ops Payouts surface: read-only vendor payout roll-up + dual-custody
 * Maker/Checker payout queue & step-up gated approval (T-AP04-02).
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

        $tenantId = (string) $tenant->getKey();
        $limit = min(max((int) $request->query('limit', 50), 1), 200);

        $rows = VendorPayout::query()
            ->where('tenant_id', $tenantId)
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get();

        $currentPeriod = CarbonImmutable::now('UTC')->format('Y-m');
        $periodLocked = AccountingPeriodLock::query()
            ->where('tenant_id', $tenantId)
            ->where('accounting_period', $currentPeriod)
            ->where('status', AccountingPeriodLock::STATUS_LOCKED)
            ->exists();

        $actorId = (string) ($request->user()?->getAuthIdentifier() ?? '');

        $payoutQueue = Payout::query()
            ->where('tenant_id', $tenantId)
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get()
            ->map(static function (Payout $payout) use ($actorId, $periodLocked): array {
                return [
                    'id' => (string) $payout->getKey(),
                    'object' => 'payout',
                    'beneficiary_id' => $payout->beneficiary_id,
                    'amount_minor' => $payout->amount_minor,
                    'currency' => $payout->currency,
                    'status' => $payout->status,
                    'maker_id' => $payout->maker_id,
                    'checker_id' => $payout->checker_id,
                    'approved_at' => $payout->approved_at?->toJSON(),
                    'created_at' => $payout->created_at?->toJSON(),
                    'checklist' => [
                        'dual_custody_eligible' => $actorId !== '' && $actorId !== (string) $payout->maker_id,
                        'period_open' => ! $periodLocked,
                    ],
                ];
            })
            ->values()
            ->all();

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
            'maker_checker_payouts' => $payoutQueue,
            'meta' => ['request_id' => $this->requestId($request), 'limit' => $limit],
        ]);
    }

    public function initiate(Request $request, MakerCheckerPayoutService $service): JsonResponse
    {
        $validated = $request->validate([
            'tenant_id' => ['required', 'string'],
            'beneficiary_id' => ['required', 'string', 'max:64'],
            'amount_minor' => ['required', 'integer', 'min:1'],
            'currency' => ['required', 'string', 'size:3'],
            'payout_batch_id' => ['nullable', 'string', 'max:26'],
        ]);

        TenantScopeGuard::assertSameTenant($request, (string) $validated['tenant_id']);

        $makerId = (string) ($request->user()?->getAuthIdentifier() ?? '');
        if ($makerId === '') {
            throw new CodedHttpException(401, 'unauthenticated', 'Authenticated maker identity is required.');
        }

        $payout = $service->initiatePayout(
            tenantId: (string) $validated['tenant_id'],
            beneficiaryId: (string) $validated['beneficiary_id'],
            amountMinor: (int) $validated['amount_minor'],
            currency: strtoupper((string) $validated['currency']),
            makerId: $makerId,
            batchId: isset($validated['payout_batch_id']) ? (string) $validated['payout_batch_id'] : null,
        );

        return new JsonResponse([
            'data' => [
                'id' => (string) $payout->getKey(),
                'object' => 'payout',
                'beneficiary_id' => $payout->beneficiary_id,
                'amount_minor' => $payout->amount_minor,
                'currency' => $payout->currency,
                'status' => $payout->status,
                'maker_id' => $payout->maker_id,
            ],
            'meta' => ['request_id' => $this->requestId($request)],
        ], 201);
    }

    public function approve(
        string $payoutId,
        Request $request,
        MakerCheckerPayoutService $service,
    ): JsonResponse {
        $idempotencyKey = trim((string) $request->header('Idempotency-Key', ''));
        if ($idempotencyKey === '') {
            throw new CodedHttpException(400, 'idempotency_key_required', 'Idempotency-Key header is required for payout approval.');
        }

        $validated = $request->validate([
            'tenant_id' => ['required', 'string'],
            'expected_beneficiary_id' => ['nullable', 'string', 'max:64'],
            'instrument_id' => ['nullable', 'string', 'max:26'],
            'checklist_confirmed' => ['required', 'accepted'],
        ]);

        $tenantId = (string) $validated['tenant_id'];
        TenantScopeGuard::assertSameTenant($request, $tenantId);

        $checkerId = (string) ($request->user()?->getAuthIdentifier() ?? '');
        if ($checkerId === '') {
            throw new CodedHttpException(401, 'unauthenticated', 'Authenticated checker identity is required.');
        }

        $existing = Payout::query()
            ->where('tenant_id', $tenantId)
            ->where('id', $payoutId)
            ->first();

        if ($existing === null) {
            throw new CodedHttpException(404, 'not_found', 'Payout not found.');
        }

        if (
            isset($validated['expected_beneficiary_id'])
            && $validated['expected_beneficiary_id'] !== ''
            && (string) $existing->beneficiary_id !== (string) $validated['expected_beneficiary_id']
        ) {
            throw new CodedHttpException(
                409,
                'beneficiary_changed',
                'Payout beneficiary changed since verification checklist was opened; re-verify before approving.',
            );
        }

        $cacheKey = 'ops:payout:approve:'.hash('sha256', "{$tenantId}:{$payoutId}:{$idempotencyKey}");
        $cached = Cache::get($cacheKey);
        if (is_array($cached)) {
            return new JsonResponse($cached, 200);
        }

        try {
            $approved = $service->approvePayout(
                tenantId: $tenantId,
                payoutId: $payoutId,
                checkerId: $checkerId,
                instrumentId: isset($validated['instrument_id']) ? (string) $validated['instrument_id'] : null,
            );
        } catch (MakerCheckerViolationException $e) {
            throw new CodedHttpException(403, 'maker_checker_violation', $e->getMessage());
        } catch (RuntimeException $e) {
            throw new CodedHttpException(409, 'conflict', $e->getMessage());
        }

        $payload = [
            'data' => [
                'id' => (string) $approved->getKey(),
                'object' => 'payout',
                'beneficiary_id' => $approved->beneficiary_id,
                'amount_minor' => $approved->amount_minor,
                'currency' => $approved->currency,
                'status' => $approved->status,
                'maker_id' => $approved->maker_id,
                'checker_id' => $approved->checker_id,
                'approved_at' => $approved->approved_at?->toJSON(),
            ],
            'meta' => [
                'request_id' => $this->requestId($request),
                'idempotency_key' => $idempotencyKey,
            ],
        ];

        Cache::put($cacheKey, $payload, now()->addHours(24));

        return new JsonResponse($payload, 200);
    }
}
