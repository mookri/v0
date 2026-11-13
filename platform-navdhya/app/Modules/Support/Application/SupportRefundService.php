<?php

declare(strict_types=1);

namespace App\Modules\Support\Application;

use App\Modules\Support\Domain\Enums\CaseStatus;
use App\Modules\Support\Domain\Enums\RemedyType;
use App\Modules\Support\Models\SupportCase;
use App\Modules\Support\Models\SupportRefundRequest;
use App\Platform\Audit\AuditWriter;
use App\Platform\Outbox\Outbox;
use App\Platform\Tenancy\TenantContext;
use Carbon\CarbonImmutable;

/**
 * Support Refund Request Workflow & Finance Contract Boundary (E-TS-08 / TS-03).
 *
 * Never imports Finance models directly; emits outbox `support.refund.requested`
 * after enforcing no-forced-credit guard (`422 forced_credit_forbidden`).
 */
final class SupportRefundService
{
    public function __construct(
        private readonly SupportCaseTransitionService $transitions,
        private readonly AuditWriter $audit,
        private readonly Outbox $outbox,
    ) {
    }

    public function requestRefund(
        string $caseId,
        string $bookingId,
        int $amountMinor,
        string $basis,
        string $proposedType,
        string $remedyInstrument,
        string $requestedById,
        ?CarbonImmutable $devoteeFreelyConsentedAt = null,
        string $currency = 'INR',
    ): SupportRefundRequest {
        if ($amountMinor <= 0) {
            throw new \InvalidArgumentException('Refund amount_minor must be positive.');
        }

        $instrument = strtoupper($remedyInstrument);
        if ($instrument === RemedyType::VOLUNTARY_CREDIT->value && $devoteeFreelyConsentedAt === null) {
            throw new \InvalidArgumentException(
                'forced_credit_forbidden: Refund eligibility cannot be settled via store credit without explicit devotee consent.'
            );
        }

        $tenantId = (string) app(TenantContext::class)->tenantId();
        $now = CarbonImmutable::now('UTC');
        $case = SupportCase::query()->where('tenant_id', $tenantId)->where('id', $caseId)->firstOrFail();

        return $case->getConnection()->transaction(function () use (
            $tenantId,
            $case,
            $caseId,
            $bookingId,
            $amountMinor,
            $currency,
            $basis,
            $proposedType,
            $instrument,
            $requestedById,
            $devoteeFreelyConsentedAt,
            $now
        ): SupportRefundRequest {
            /** @var SupportRefundRequest $req */
            $req = SupportRefundRequest::query()->create([
                'tenant_id' => $tenantId,
                'case_id' => $caseId,
                'booking_id' => $bookingId,
                'amount_minor' => $amountMinor,
                'currency' => strtoupper($currency),
                'basis' => $basis,
                'proposed_type' => strtoupper($proposedType),
                'remedy_instrument' => $instrument,
                'devotee_freely_consented_at' => $devoteeFreelyConsentedAt?->toDateTimeString(),
                'requested_by_id' => $requestedById,
                'state' => SupportRefundRequest::STATE_REQUESTED,
                'sla_due_at' => $now->addDays(5)->toDateTimeString(),
            ]);

            if (in_array((string) $case->status, [CaseStatus::UNDER_INVESTIGATION->value, CaseStatus::RESOLUTION_PROPOSED->value], true)) {
                $this->transitions->transition(
                    $caseId,
                    CaseStatus::REFUND_PENDING,
                    $requestedById,
                    'staff',
                    'refund_requested',
                    "Refund request created ({$instrument}, {$amountMinor} {$currency})",
                );
            }

            $this->audit->record(
                'support.refund.requested',
                ['module' => 'support', 'resource_type' => 'support_refund_request', 'resource_id' => (string) $req->getKey()],
                null,
                ['case_id' => $caseId, 'booking_id' => $bookingId, 'amount_minor' => $amountMinor, 'instrument' => $instrument]
            );

            $this->outbox->append(
                $tenantId,
                'support',
                (string) $req->getKey(),
                'support.refund.requested',
                [
                    'refund_request_id' => (string) $req->getKey(),
                    'case_id' => $caseId,
                    'booking_id' => $bookingId,
                    'amount_minor' => $amountMinor,
                    'currency' => strtoupper($currency),
                    'remedy_instrument' => $instrument,
                ]
            );

            return $req;
        });
    }

    public function approveAndRecordFinanceExecution(
        string $refundId,
        string $approverId,
        string $financeReference,
    ): SupportRefundRequest {
        $tenantId = (string) app(TenantContext::class)->tenantId();
        $now = CarbonImmutable::now('UTC');

        $req = SupportRefundRequest::query()->where('tenant_id', $tenantId)->where('id', $refundId)->firstOrFail();

        return $req->getConnection()->transaction(function () use ($req, $approverId, $financeReference, $now): SupportRefundRequest {
            $req->forceFill([
                'state' => SupportRefundRequest::STATE_EXECUTED,
                'approved_by_id' => $approverId,
                'finance_execution_reference' => $financeReference,
                'executed_at' => $now->toDateTimeString(),
            ])->save();

            $case = SupportCase::query()->where('id', $req->case_id)->first();
            if ($case !== null && (string) $case->status === CaseStatus::REFUND_PENDING->value) {
                $this->transitions->transition(
                    (string) $case->getKey(),
                    CaseStatus::REFUND_COMPLETED,
                    $approverId,
                    'staff',
                    'refund_executed',
                    "Finance execution reference: {$financeReference}",
                );
            }

            return $req;
        });
    }
}

