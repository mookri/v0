<?php

namespace App\Domain\Verification\Risk;

use App\Domain\Privacy\Consent\ConsentManager;
use App\Domain\Privacy\Enums\ConsentPurpose;
use App\Domain\Verification\Claims\ClaimManager;
use App\Domain\Verification\Enums\ClaimKind;
use App\Domain\Verification\Enums\ConfidenceLevel;
use App\Domain\Verification\Enums\EvidenceMode;
use App\Domain\Verification\Models\VerificationBackgroundCheck;
use RuntimeException;

/**
 * Nuanced antecedent & background check workflow (§9.4 / T-TR07-01, T-TR07-02).
 * Consent-gated, free of binary criminal_record flags, and requires human relevance review
 * plus applicant clarification before any adverse finding can trigger rejection.
 */
final class BackgroundCheckService
{
    public function __construct(
        private readonly ConsentManager $consents,
        private readonly ClaimManager $claims,
    ) {
    }

    public function recordCheck(
        string $tenantId,
        string $subjectType,
        string $subjectId,
        string $identityId,
        string $checkType,
        string $lawfulBasis,
        PoliceAntecedentStatus $legalStatus,
        string $reviewerId,
        ?string $findingsSummary = null,
        ?string $source = null,
        ?string $applicationId = null,
    ): VerificationBackgroundCheck {
        $consentId = null;
        if ($checkType === 'police_antecedents') {
            $this->consents->requireConsent($tenantId, $identityId, ConsentPurpose::ANTECEDENT_CHECK);
        }

        $actionRequired = $legalStatus->requiresHumanRelevanceReview();

        $check = new VerificationBackgroundCheck();
        $check->forceFill([
            'tenant_id' => $tenantId,
            'subject_type' => $subjectType,
            'subject_id' => $subjectId,
            'application_id' => $applicationId,
            'check_type' => $checkType,
            'lawful_basis' => $lawfulBasis,
            'consent_id' => $consentId,
            'source' => $source ?? ($legalStatus === PoliceAntecedentStatus::NOT_AVAILABLE ? 'offline_unavailable' : 'manual_review'),
            'searched_at' => now()->utc(),
            'responded_at' => now()->utc(),
            'findings_summary' => $findingsSummary,
            'legal_status' => $legalStatus->value,
            'reviewer_id' => $reviewerId,
            'confidence' => $legalStatus === PoliceAntecedentStatus::CLEAR ? ConfidenceLevel::MEDIUM->value : ConfidenceLevel::LOW->value,
            'human_relevance_assessed' => false,
            'action_required' => $actionRequired,
        ])->save();

        if ($legalStatus === PoliceAntecedentStatus::CLEAR || $legalStatus === PoliceAntecedentStatus::NOT_AVAILABLE) {
            $claim = $this->claims->grantClaim([
                'tenant_id' => $tenantId,
                'subject_type' => $subjectType,
                'subject_id' => $subjectId,
                'application_id' => $applicationId,
                'claim_kind' => ClaimKind::ANTECEDENT->value,
                'mode' => EvidenceMode::MANUAL->value,
                'confidence' => $legalStatus === PoliceAntecedentStatus::CLEAR ? ConfidenceLevel::MEDIUM->value : ConfidenceLevel::LOW->value,
                'reason_code' => $legalStatus === PoliceAntecedentStatus::NOT_AVAILABLE
                    ? 'ANTECEDENT_NOT_AVAILABLE_LAWFUL'
                    : 'ANTECEDENT_CHECK_CLEAR',
                'note' => $findingsSummary,
                'decided_by' => $reviewerId,
            ]);
            $check->forceFill(['claim_id' => (string) $claim->getKey()])->save();
        }

        return $check->fresh();
    }

    public function recordClarificationAndRelevanceReview(
        string $tenantId,
        string $checkId,
        string $applicantClarification,
        string $reviewerId,
        bool $markRelevanceAssessed = true,
    ): VerificationBackgroundCheck {
        $check = VerificationBackgroundCheck::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->findOrFail($checkId);

        $check->forceFill([
            'applicant_clarification' => $applicantClarification,
            'human_relevance_assessed' => $markRelevanceAssessed,
            'action_required' => false,
            'reviewer_id' => $reviewerId,
        ])->save();

        return $check->fresh();
    }

    public function assertCanRejectBasedOnCheck(string $tenantId, string $checkId): void
    {
        $check = VerificationBackgroundCheck::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->findOrFail($checkId);

        $status = PoliceAntecedentStatus::from($check->legal_status);
        if ($status === PoliceAntecedentStatus::NOT_AVAILABLE || $status === PoliceAntecedentStatus::CLEAR) {
            throw new RuntimeException('Cannot reject application based on clear or unavailable antecedent check.');
        }

        if (! $check->human_relevance_assessed) {
            throw new RuntimeException('Adverse antecedent finding requires human relevance assessment and applicant clarification first.');
        }

        if ($status === PoliceAntecedentStatus::COMPLAINT_FILED && trim((string) $check->findings_summary) === '') {
            throw new RuntimeException('Unverified complaint_filed cannot trigger automatic rejection without documented relevance review.');
        }
    }
}

