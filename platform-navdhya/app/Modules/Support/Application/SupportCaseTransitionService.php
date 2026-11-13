<?php

declare(strict_types=1);

namespace App\Modules\Support\Application;

use App\Modules\Support\Domain\CaseLifecycle;
use App\Modules\Support\Domain\Enums\CaseStatus;
use App\Modules\Support\Models\SupportCase;
use App\Modules\Support\Models\SupportCaseResolution;
use App\Modules\Support\Models\SupportCaseStatusHistory;
use App\Modules\Support\Models\SupportGrievance;
use App\Platform\Audit\AuditWriter;
use App\Platform\Outbox\Outbox;
use App\Platform\Tenancy\TenantContext;
use Carbon\CarbonImmutable;
use Illuminate\Support\Str;

/**
 * Authoritative SupportCase state-transition writer (TS-03).
 *
 * Guarantees:
 *  - Transition validated via CaseLifecycle (backed by Domain\Trust\Workflow)
 *  - Every transition writes support_case_status_history + AuditWriter
 *  - RESOLVED / CLOSED fail closed unless SupportCaseResolution exists
 */
final class SupportCaseTransitionService
{
    public function __construct(
        private readonly AuditWriter $audit,
        private readonly Outbox $outbox,
    ) {
    }

    public function transition(
        string $caseId,
        CaseStatus $targetStatus,
        string $actorId,
        string $actorType,
        string $action,
        string $reason,
        string $policyVersion = 'v1',
        ?string $correlationId = null,
    ): SupportCase {
        if (trim($reason) === '') {
            throw new \InvalidArgumentException('Transition reason cannot be empty.');
        }

        $tenantId = (string) app(TenantContext::class)->tenantId();
        $now = CarbonImmutable::now('UTC');
        $corr = $correlationId ?? (string) Str::ulid();

        return (new SupportCase())->getConnection()->transaction(function () use (
            $tenantId,
            $caseId,
            $targetStatus,
            $actorId,
            $actorType,
            $action,
            $reason,
            $policyVersion,
            $corr,
            $now
        ): SupportCase {
            $locked = SupportCase::query()
                ->where('tenant_id', $tenantId)
                ->where('id', $caseId)
                ->lockForUpdate()
                ->firstOrFail();

            $from = $locked->status;
            CaseLifecycle::assertCanTransition($from, $targetStatus);

            if (CaseLifecycle::requiresResolutionRecord($targetStatus)) {
                $hasResolution = SupportCaseResolution::query()
                    ->where('tenant_id', $tenantId)
                    ->where('case_id', $caseId)
                    ->exists();

                if (! $hasResolution) {
                    throw new \DomainException('Cannot resolve or close a support case without a recorded SupportCaseResolution row.');
                }
            }

            if ($targetStatus === CaseStatus::CLOSED) {
                $formalGrievance = SupportGrievance::query()
                    ->where('tenant_id', $tenantId)
                    ->where('case_id', $caseId)
                    ->where('formal_flag', true)
                    ->first();

                if ($formalGrievance !== null && trim((string) $formalGrievance->officer_decision) === '') {
                    throw new \DomainException('grievance_decision_required: Formal grievances cannot be closed without a recorded Grievance Officer decision.');
                }
            }

            $updates = [
                'status' => $targetStatus->value,
                'version' => (int) $locked->version + 1,
            ];

            if ($targetStatus === CaseStatus::ACKNOWLEDGED && $locked->acknowledged_at === null) {
                $updates['acknowledged_at'] = $now->toDateTimeString();
            }
            if ($targetStatus === CaseStatus::RESOLVED) {
                $updates['resolved_at'] = $now->toDateTimeString();
            }
            if ($targetStatus === CaseStatus::CLOSED) {
                $updates['closed_at'] = $now->toDateTimeString();
            }
            if ($targetStatus === CaseStatus::REOPENED) {
                $updates['reopen_count'] = (int) $locked->reopen_count + 1;
                $updates['closed_at'] = null;
            }

            $locked->forceFill($updates)->save();

            $history = new SupportCaseStatusHistory();
            $history->forceFill([
                'tenant_id' => $tenantId,
                'case_id' => $caseId,
                'actor_id' => $actorId,
                'actor_type' => $actorType,
                'action' => $action,
                'previous_state' => $from,
                'new_state' => $targetStatus->value,
                'reason' => $reason,
                'policy_version' => $policyVersion,
                'correlation_id' => $corr,
                'occurred_at' => $now->toDateTimeString(),
            ]);
            $history->save();

            $this->audit->record(
                'support.case.transitioned',
                ['module' => 'support', 'resource_type' => 'support_case', 'resource_id' => $caseId],
                ['status' => $from],
                [
                    'status' => $targetStatus->value,
                    'actor_id' => $actorId,
                    'reason' => $reason,
                    'correlation_id' => $corr,
                ]
            );

            $eventType = match ($targetStatus) {
                CaseStatus::ACKNOWLEDGED => 'support.case.acknowledged',
                CaseStatus::RESOLVED => 'support.case.resolved',
                CaseStatus::REOPENED => 'support.case.reopened',
                default => null,
            };

            if ($eventType !== null) {
                $this->outbox->append(
                    $tenantId,
                    'support',
                    $caseId,
                    $eventType,
                    [
                        'case_id' => $caseId,
                        'case_reference' => $locked->case_reference,
                        'status' => $targetStatus->value,
                        'actor_id' => $actorId,
                    ]
                );
            }

            return $locked;
        });
    }
}

