<?php

namespace App\Http\Controllers\Ops;

use App\Modules\Finance\Models\AccountingPeriodLock;
use App\Modules\Finance\Models\LedgerJournal;
use App\Modules\Payment\Domain\PaymentReconciliationService;
use App\Modules\Payment\Models\PaymentIntent;
use App\Platform\Tenancy\Models\Tenant;
use App\Platform\Tenancy\TenantScopeGuard;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Ops Reconciliation surface (T-AP04-03): exposes PAYMENT_UNKNOWN intents,
 * ledger period locks, and recent posted/reversed journals.
 */
final class ReconciliationController
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

        $uncertain = PaymentIntent::query()
            ->where('tenant_id', $tenantId)
            ->where('status', PaymentIntent::STATUS_PAYMENT_UNKNOWN)
            ->orderByDesc('created_at')
            ->limit(50)
            ->get()
            ->map(static fn (PaymentIntent $intent): array => [
                'id' => (string) $intent->getKey(),
                'booking_id' => $intent->booking_id,
                'amount_minor' => $intent->amount_minor,
                'currency' => $intent->currency,
                'status' => $intent->status,
                'reconcile_attempts' => $intent->reconcile_attempts,
                'last_reconcile_error' => $intent->last_reconcile_error,
                'created_at' => $intent->created_at?->toJSON(),
            ])
            ->values()
            ->all();

        $periods = AccountingPeriodLock::query()
            ->where('tenant_id', $tenantId)
            ->orderByDesc('accounting_period')
            ->limit(24)
            ->get()
            ->map(static fn (AccountingPeriodLock $lock): array => [
                'accounting_period' => $lock->accounting_period,
                'status' => $lock->status,
                'locked_by' => $lock->locked_by,
                'locked_at' => $lock->locked_at?->toJSON(),
                'lock_reason' => $lock->lock_reason,
            ])
            ->values()
            ->all();

        $journals = LedgerJournal::query()
            ->where('tenant_id', $tenantId)
            ->orderByDesc('posted_at')
            ->limit(25)
            ->get()
            ->map(static fn (LedgerJournal $j): array => [
                'id' => (string) $j->getKey(),
                'journal_number' => $j->journal_number,
                'reference_type' => $j->reference_type,
                'reference_id' => $j->reference_id,
                'accounting_period' => $j->accounting_period,
                'status' => $j->status,
                'reverses_journal_id' => $j->reverses_journal_id,
                'reversed_by_journal_id' => $j->reversed_by_journal_id,
                'posted_at' => $j->posted_at->toJSON(),
            ])
            ->values()
            ->all();

        return new JsonResponse([
            'data' => [
                'uncertain_intents' => $uncertain,
                'period_locks' => $periods,
                'recent_journals' => $journals,
            ],
            'meta' => ['request_id' => $this->requestId($request)],
        ]);
    }

    public function retry(
        string $intentId,
        Request $request,
        PaymentReconciliationService $reconciler,
    ): JsonResponse {
        $validated = $request->validate(['tenant_id' => ['required', 'string']]);
        $tenantId = (string) $validated['tenant_id'];

        TenantScopeGuard::assertSameTenant($request, $tenantId);

        $intent = PaymentIntent::query()
            ->where('tenant_id', $tenantId)
            ->where('id', $intentId)
            ->firstOrFail();

        $outcome = $reconciler->reconcileOne($intent);

        return new JsonResponse([
            'data' => [
                'id' => (string) $intent->getKey(),
                'outcome' => $outcome,
                'status' => $intent->fresh()?->status,
                'reconcile_attempts' => $intent->fresh()?->reconcile_attempts,
            ],
            'meta' => ['request_id' => $this->requestId($request)],
        ]);
    }
}
