<?php

declare(strict_types=1);

namespace App\Modules\Support\Application;

use App\Modules\Support\Domain\Enums\CaseStatus;
use App\Modules\Support\Domain\Enums\RemedyType;
use App\Modules\Support\Models\SupportCase;
use App\Modules\Support\Models\SupportCaseResolution;
use App\Platform\Tenancy\TenantContext;
use Carbon\CarbonImmutable;

/**
 * Records a formal resolution and transitions the case to RESOLVED (and optionally CLOSED),
 * enforcing TS-03 & T-TS08-03 (no forced store credit without consent).
 */
final class ResolveSupportCaseUseCase
{
    public function __construct(
        private readonly SupportCaseTransitionService $transitions,
    ) {
    }

    /**
     * @param  array<string, mixed>|null  $dossier
     */
    public function resolve(
        string $caseId,
        RemedyType $remedyType,
        string $decisionSummary,
        string $deciderId,
        bool $monetaryEligible = false,
        ?CarbonImmutable $devoteeCreditConsentAt = null,
        ?array $dossier = null,
        bool $closeImmediately = false,
    ): SupportCase {
        if ($remedyType === RemedyType::VOLUNTARY_CREDIT && $monetaryEligible && $devoteeCreditConsentAt === null) {
            throw new \InvalidArgumentException(
                'forced_credit_forbidden: Monetary refund eligibility cannot be replaced by store credit without explicit free devotee consent.'
            );
        }

        $tenantId = (string) app(TenantContext::class)->tenantId();

        return (new SupportCase())->getConnection()->transaction(function () use (
            $tenantId,
            $caseId,
            $remedyType,
            $decisionSummary,
            $deciderId,
            $devoteeCreditConsentAt,
            $dossier,
            $closeImmediately
        ): SupportCase {
            $lockedCase = SupportCase::query()->where('tenant_id', $tenantId)->where('id', $caseId)->firstOrFail();
            if (
                $lockedCase->visibility_scope === \App\Domain\Trust\Parties\PartyEntitlement::SCOPE_RESTRICTED_SAFETY
                && in_array($remedyType, [
                    RemedyType::VOLUNTARY_CREDIT,
                    RemedyType::RESCHEDULE,
                    RemedyType::REPLACEMENT,
                    RemedyType::REPERFORMANCE,
                    RemedyType::EXPLANATION_ONLY,
                ], true)
            ) {
                throw new \InvalidArgumentException(
                    'safety_no_mediation_forbidden: Safety and harassment reports cannot be mediated away with informal apology, reschedule, or store credit.'
                );
            }
            SupportCaseResolution::query()->create([
                'tenant_id' => $tenantId,
                'case_id' => $caseId,
                'remedy_type' => $remedyType->value,
                'decision_summary' => $decisionSummary,
                'decided_by_id' => $deciderId,
                'proposed_by_id' => $deciderId,
                'accepted_by_id' => $devoteeCreditConsentAt !== null ? 'devotee_consented' : null,
                'consent_captured_at' => $devoteeCreditConsentAt?->toDateTimeString(),
                'dossier_json' => $dossier,
                'policy_version' => 'v1',
            ]);

            $case = $this->transitions->transition(
                $caseId,
                CaseStatus::RESOLVED,
                $deciderId,
                'staff',
                'record_resolution',
                $decisionSummary,
            );

            if ($closeImmediately) {
                return $this->transitions->transition(
                    $caseId,
                    CaseStatus::CLOSED,
                    $deciderId,
                    'staff',
                    'close_with_resolution',
                    'Closed following recorded resolution: '.$remedyType->value,
                );
            }

            return $case;
        });
    }
}

