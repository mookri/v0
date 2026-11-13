<?php

namespace App\Domain\Verification\Issuers;

use App\Domain\Verification\Claims\ClaimManager;
use App\Domain\Verification\Enums\ClaimKind;
use App\Domain\Verification\Enums\ClaimStatus;
use App\Domain\Verification\Enums\ConfidenceLevel;
use App\Domain\Verification\Enums\EvidenceMode;
use App\Domain\Verification\Models\SampradayaAttestation;
use App\Domain\Verification\Models\VerificationClaim;
use App\Domain\Verification\Models\VerificationClaimScope;
use Illuminate\Database\Eloquent\Model;

/**
 * Practical competency rubric assessor & scoped ritual authorization gate (T-TR03-05).
 */
final class CompetencyAssessor
{
    public function __construct(
        private readonly ClaimManager $claims,
    ) {
    }

    /**
     * @param  list<string>  $scopeTags
     * @param  array<string, int>  $rubricScores
     * @return array{attestation: SampradayaAttestation, claim: VerificationClaim|null}
     */
    public function recordAssessment(
        string $tenantId,
        string $subjectType,
        string $subjectId,
        string $assessorIdentityId,
        string $ritualCategory,
        array $scopeTags,
        array $rubricScores,
        string $actorId,
        ?string $sampradaya = null,
        ?string $assessorCredentialId = null,
        ?string $notes = null,
        ?Model $subjectModel = null,
    ): array {
        $overall = $rubricScores === [] ? 0 : (int) round(array_sum($rubricScores) / count($rubricScores));
        $passed = $overall >= 70 && $scopeTags !== [];

        $attestation = new SampradayaAttestation();
        $attestation->forceFill([
            'tenant_id' => $tenantId,
            'subject_type' => $subjectType,
            'subject_id' => $subjectId,
            'sampradaya' => $sampradaya,
            'assessor_identity_id' => $assessorIdentityId,
            'assessor_credential_id' => $assessorCredentialId,
            'ritual_category' => $ritualCategory,
            'scope_tags_json' => array_values($scopeTags),
            'rubric_scores_json' => $rubricScores,
            'overall_score' => $overall,
            'passed' => $passed,
            'notes' => $notes,
            'assessed_at' => now()->utc(),
        ])->save();

        $claim = null;
        if ($passed) {
            $scopes = array_map(
                static fn (string $tag): array => ['scope_type' => 'ritual_category', 'scope_value' => strtolower(trim($tag))],
                $scopeTags,
            );

            $claim = $this->claims->grantClaim(
                [
                    'tenant_id' => $tenantId,
                    'subject_type' => $subjectType,
                    'subject_id' => $subjectId,
                    'claim_kind' => ClaimKind::COMPETENCY->value,
                    'mode' => EvidenceMode::MANUAL->value,
                    'confidence' => $overall >= 85 ? ConfidenceLevel::HIGH->value : ConfidenceLevel::MEDIUM->value,
                    'reason_code' => 'COMPETENCY_RUBRIC_PASSED',
                    'note' => $notes,
                    'decided_by' => $actorId,
                    'metadata' => [
                        'attestation_id' => (string) $attestation->getKey(),
                        'assessor_identity_id' => $assessorIdentityId,
                        'overall_score' => $overall,
                        'ritual_category' => $ritualCategory,
                    ],
                ],
                $scopes,
                [[
                    'evidence_kind' => 'competency_practical_rubric',
                    'source_mode' => EvidenceMode::MANUAL->value,
                    'source_ref' => (string) $attestation->getKey(),
                    'verified_by' => $assessorIdentityId,
                ]],
                $subjectModel,
            );

            $attestation->forceFill(['claim_id' => (string) $claim->getKey()])->save();
        }

        return [
            'attestation' => $attestation->fresh(),
            'claim' => $claim,
        ];
    }

    /**
     * Scoped booking authorization gate: pandit must hold an active competency or qualification
     * claim scoped explicitly to the requested ritual slug.
     */
    public function canPerformRitual(string $tenantId, string $subjectType, string $subjectId, string $ritualSlug): bool
    {
        $normalized = strtolower(trim($ritualSlug));

        $activeClaimIds = VerificationClaim::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where('subject_type', $subjectType)
            ->where('subject_id', $subjectId)
            ->whereIn('claim_kind', [ClaimKind::COMPETENCY->value, ClaimKind::QUALIFICATION->value])
            ->where('status', ClaimStatus::GRANTED->value)
            ->where(function ($q): void {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now()->utc());
            })
            ->pluck('id')
            ->all();

        if ($activeClaimIds === []) {
            return false;
        }

        return VerificationClaimScope::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->whereIn('claim_id', $activeClaimIds)
            ->where('scope_type', 'ritual_category')
            ->where('scope_value', $normalized)
            ->exists();
    }
}

