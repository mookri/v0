<?php

declare(strict_types=1);

namespace App\Modules\Support\Application;

use App\Modules\Support\Domain\Enums\CaseStatus;
use App\Modules\Support\Models\SupportCase;
use App\Modules\Support\Models\SupportCaseAppeal;
use App\Modules\Support\Models\SupportCaseResolution;
use App\Platform\Audit\AuditWriter;
use App\Platform\Tenancy\TenantContext;
use Carbon\CarbonImmutable;

/**
 * Independent Support Resolution Appeal Service (E-TS-08 / T-TS03-04).
 */
final class SupportAppealService
{
    public function __construct(
        private readonly SupportCaseTransitionService $transitions,
        private readonly AuditWriter $audit,
    ) {
    }

    public function fileAppeal(string $caseId, string $appellantId, string $role, string $grounds): SupportCaseAppeal
    {
        $tenantId = (string) app(TenantContext::class)->tenantId();
        SupportCase::query()->where('tenant_id', $tenantId)->where('id', $caseId)->firstOrFail();

        /** @var SupportCaseAppeal */
        return SupportCaseAppeal::query()->create([
            'tenant_id' => $tenantId,
            'case_id' => $caseId,
            'appellant_id' => $appellantId,
            'appellant_role' => $role,
            'grounds' => $grounds,
            'status' => SupportCaseAppeal::STATUS_PENDING,
        ]);
    }

    public function decideAppeal(
        string $appealId,
        string $reviewerId,
        bool $overturn,
        string $notes,
    ): SupportCaseAppeal {
        $tenantId = (string) app(TenantContext::class)->tenantId();
        $now = CarbonImmutable::now('UTC');
        $appeal = SupportCaseAppeal::query()->where('tenant_id', $tenantId)->where('id', $appealId)->firstOrFail();

        $priorDecider = SupportCaseResolution::query()
            ->where('tenant_id', $tenantId)
            ->where('case_id', $appeal->case_id)
            ->where('decided_by_id', $reviewerId)
            ->exists();

        if ($priorDecider) {
            throw new \DomainException('independence_violation: Appeal decider MUST be independent of the prior resolution decider.');
        }

        $appeal->forceFill([
            'status' => $overturn ? SupportCaseAppeal::STATUS_OVERTURNED : SupportCaseAppeal::STATUS_UPHELD,
            'outcome' => $overturn ? 'REOPENED_FOR_NEW_RESOLUTION' : 'ORIGINAL_RESOLUTION_UPHELD',
            'reviewer_id' => $reviewerId,
            'decision_notes' => $notes,
            'decided_at' => $now->toDateTimeString(),
        ])->save();

        if ($overturn) {
            $case = SupportCase::query()->where('tenant_id', $tenantId)->where('id', $appeal->case_id)->first();
            if ($case !== null && in_array((string) $case->status, [CaseStatus::RESOLVED->value, CaseStatus::CLOSED->value], true)) {
                $this->transitions->transition(
                    (string) $case->getKey(),
                    CaseStatus::REOPENED,
                    $reviewerId,
                    'appeals_reviewer',
                    'appeal_overturned',
                    "Appeal {$appealId} overturned prior resolution.",
                );
            }
        }

        $this->audit->record(
            'support.case.reopened',
            ['module' => 'support', 'resource_type' => 'support_case_appeal', 'resource_id' => $appealId],
            null,
            ['case_id' => $appeal->case_id, 'overturn' => $overturn, 'reviewer_id' => $reviewerId]
        );

        return $appeal;
    }
}

