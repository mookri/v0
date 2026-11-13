<?php

namespace App\Domain\Verification\Institutions;

use App\Domain\Privacy\Support\IdentityFingerprint;
use App\Domain\Verification\Claims\ClaimManager;
use App\Domain\Verification\Enums\ClaimKind;
use App\Domain\Verification\Enums\ClaimStatus;
use App\Domain\Verification\Enums\ConfidenceLevel;
use App\Domain\Verification\Enums\EvidenceMode;
use App\Domain\Verification\Exceptions\TrusteeTakeoverBlockedException;
use App\Domain\Verification\Models\VerificationAuthorityGrant;
use App\Domain\Verification\Models\VerificationClaim;
use App\Domain\Verification\Models\VerificationJurisdiction;
use App\Platform\Audit\AuditWriter;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Model;

/**
 * Separates institutional.existence from institutional.authority, enforces §7.2 takeover guard,
 * jurisdiction checklists, conflicting-trustee locks, duplicate detection, and 48h mandate cooling-off.
 */
final class TempleAuthorityService
{
    public function __construct(
        private readonly ClaimManager $claims,
        private readonly EntityRelationshipGraphService $graph,
        private readonly AuditWriter $audit,
    ) {
    }

    /**
     * Evaluate institutional existence (trust deed / statutory order / unregistered corroboration).
     * Never certifies applicant representation authority (§7.2).
     *
     * @param  array<string, mixed>  $checklistEvidence
     */
    public function verifyExistence(
        string $tenantId,
        string $entityId,
        InstitutionalLegalForm $legalForm,
        string $jurisdictionCode,
        string $reviewerId,
        array $checklistEvidence,
        ?string $registrationNumber = null,
        ?string $normalizedName = null,
        ?string $applicationId = null,
        ?Model $subjectModel = null,
    ): array {
        $jurisdiction = VerificationJurisdiction::query()->where('code', $jurisdictionCode)->first();
        $requiredKeys = $jurisdiction?->checklist_json ?? ($legalForm->requiresEnhancedUnregisteredChecklist()
            ? ['society_certificate_or_community_letter', 'electricity_or_land_title_proof', 'multi_witness_corroboration']
            : []);

        $missing = [];
        foreach ($requiredKeys as $key) {
            if (! array_key_exists($key, $checklistEvidence) || $checklistEvidence[$key] === null || $checklistEvidence[$key] === '') {
                $missing[] = $key;
            }
        }

        if ($missing !== []) {
            throw new TrusteeTakeoverBlockedException(
                'Missing jurisdiction verification checklist items: ' . implode(', ', $missing),
                'jurisdiction_checklist_incomplete',
                422,
            );
        }

        $fingerprint = IdentityFingerprint::hash(
            'institution_reg',
            strtolower(trim($jurisdictionCode . '|' . ($registrationNumber ?? $normalizedName ?? $entityId))),
        );

        $duplicateEntityIds = VerificationAuthorityGrant::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where('registration_fingerprint_hmac', $fingerprint)
            ->where('entity_id', '!=', $entityId)
            ->pluck('entity_id')
            ->unique()
            ->values()
            ->all();

        // Jurisdiction checks are strictly manual (zero automated scraping per §2.2).
        $claim = $this->claims->grantClaim(
            [
                'tenant_id' => $tenantId,
                'subject_type' => 'temple',
                'subject_id' => $entityId,
                'application_id' => $applicationId,
                'claim_kind' => ClaimKind::INSTITUTIONAL_EXISTENCE->value,
                'mode' => EvidenceMode::MANUAL->value,
                'confidence' => $legalForm->requiresEnhancedUnregisteredChecklist()
                    ? ConfidenceLevel::MEDIUM->value
                    : ConfidenceLevel::HIGH->value,
                'reason_code' => $legalForm->requiresEnhancedUnregisteredChecklist()
                    ? 'UNREGISTERED_INSTITUTION_MANUAL_CORROBORATED'
                    : 'INSTITUTION_EXISTENCE_JURISDICTION_CHECKED',
                'decided_by' => $reviewerId,
                'metadata' => [
                    'legal_form' => $legalForm->value,
                    'jurisdiction_code' => $jurisdictionCode,
                    'registration_fingerprint_hmac' => $fingerprint,
                    'checklist' => $checklistEvidence,
                ],
            ],
            [],
            [[
                'evidence_kind' => 'jurisdiction_manual_checklist',
                'source_mode' => EvidenceMode::MANUAL->value,
                'source_ref' => $jurisdictionCode,
                'verified_by' => $reviewerId,
                'metadata' => $checklistEvidence,
            ]],
            $subjectModel,
        );

        if ($duplicateEntityIds !== []) {
            $this->claims->grantClaim([
                'tenant_id' => $tenantId,
                'subject_type' => 'temple',
                'subject_id' => $entityId,
                'application_id' => $applicationId,
                'claim_kind' => ClaimKind::ENHANCED_REVIEW->value,
                'mode' => EvidenceMode::MANUAL->value,
                'confidence' => ConfidenceLevel::HIGH->value,
                'reason_code' => 'DUPLICATE_INSTITUTION_REGISTRATION',
                'note' => 'Registration fingerprint matches existing institutional record(s).',
                'decided_by' => 'system:institution_dedupe',
                'metadata' => ['conflicting_entity_ids' => $duplicateEntityIds],
            ]);
        }

        return [
            'existence_claim' => $claim,
            'duplicate_detected' => $duplicateEntityIds !== [],
            'registration_fingerprint_hmac' => $fingerprint,
        ];
    }

    /**
     * Evaluate representative/trustee authority for an institution. Detects competing trustee claims
     * and blocks first-writer-wins takeover (§7.3).
     *
     * @return array{conflict_detected: bool, grant: VerificationAuthorityGrant|null, authority_claim: VerificationClaim}
     */
    public function verifyAuthority(
        string $tenantId,
        string $entityId,
        string $personIdentityId,
        string $roleTitle,
        AuthorityScope $scope,
        string $resolutionRef,
        string $resolutionDate,
        string $reviewerId,
        ?DateTimeInterface $effectiveTo = null,
        ?string $legalForm = null,
        ?string $jurisdictionCode = null,
        ?string $registrationFingerprintHmac = null,
        ?string $applicationId = null,
    ): array {
        // Conflicting-trustee claim detection (§7.3).
        $competing = VerificationAuthorityGrant::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where('entity_id', $entityId)
            ->whereNull('revoked_at')
            ->where('person_identity_id', '!=', $personIdentityId)
            ->where(function ($q): void {
                $q->whereNull('effective_to')->orWhere('effective_to', '>', now()->utc());
            })
            ->whereIn('authority_scope', [
                AuthorityScope::FULL_REPRESENTATION->value,
                AuthorityScope::BANK_MANDATE->value,
                AuthorityScope::ONBOARDING->value,
            ])
            ->exists();

        if ($competing) {
            $conflictClaim = $this->claims->grantClaim([
                'tenant_id' => $tenantId,
                'subject_type' => 'temple',
                'subject_id' => $entityId,
                'application_id' => $applicationId,
                'claim_kind' => ClaimKind::ENHANCED_REVIEW->value,
                'mode' => EvidenceMode::MANUAL->value,
                'confidence' => ConfidenceLevel::HIGH->value,
                'reason_code' => 'CONFLICTING_TRUSTEE_CLAIM',
                'note' => 'Competing trustee/representative authority submitted against active institutional mandate.',
                'decided_by' => $reviewerId,
                'metadata' => [
                    'challenger_identity_id' => $personIdentityId,
                    'role_title' => $roleTitle,
                ],
            ]);

            $this->audit->record(
                'onboarding.institution.trustee_conflict',
                ['module' => 'onboarding', 'resource_type' => 'temple', 'resource_id' => $entityId],
                null,
                ['challenger_identity_id' => $personIdentityId, 'actor_id' => $reviewerId],
            );

            return [
                'conflict_detected' => true,
                'grant' => null,
                'authority_claim' => $conflictClaim,
            ];
        }

        $expiresAt = $effectiveTo ?? now()->utc()->addYear();

        $claim = $this->claims->grantClaim(
            [
                'tenant_id' => $tenantId,
                'subject_type' => 'temple',
                'subject_id' => $entityId,
                'application_id' => $applicationId,
                'claim_kind' => ClaimKind::INSTITUTIONAL_AUTHORITY->value,
                'mode' => EvidenceMode::MANUAL->value,
                'confidence' => ConfidenceLevel::HIGH->value,
                'reason_code' => 'BOARD_RESOLUTION_AUTHORITY_GRANTED',
                'decided_by' => $reviewerId,
                'expires_at' => $expiresAt,
                'metadata' => [
                    'person_identity_id' => $personIdentityId,
                    'role_title' => $roleTitle,
                    'authority_scope' => $scope->value,
                    'resolution_ref' => $resolutionRef,
                ],
            ],
            [['scope_type' => 'authority_scope', 'scope_value' => $scope->value]],
            [[
                'evidence_kind' => 'board_resolution',
                'source_mode' => EvidenceMode::MANUAL->value,
                'source_ref' => $resolutionRef,
                'verified_by' => $reviewerId,
            ]],
        );

        $grant = new VerificationAuthorityGrant();
        $grant->forceFill([
            'tenant_id' => $tenantId,
            'entity_id' => $entityId,
            'person_identity_id' => $personIdentityId,
            'role_title' => $roleTitle,
            'authority_scope' => $scope->value,
            'legal_form' => $legalForm,
            'jurisdiction_code' => $jurisdictionCode,
            'registration_fingerprint_hmac' => $registrationFingerprintHmac,
            'resolution_ref' => $resolutionRef,
            'resolution_date' => $resolutionDate,
            'effective_from' => now()->utc(),
            'effective_to' => $expiresAt,
            'claim_id' => (string) $claim->getKey(),
        ])->save();

        $this->graph->recordEdge(
            tenantId: $tenantId,
            sourceType: 'temple',
            sourceId: $entityId,
            edgeType: 'has_authority_grant',
            targetType: 'identity',
            targetId: $personIdentityId,
            claimId: (string) $claim->getKey(),
            evidenceId: $resolutionRef,
            effectiveFrom: now()->utc(),
            effectiveTo: $expiresAt,
        );

        return [
            'conflict_detected' => false,
            'grant' => $grant->fresh(),
            'authority_claim' => $claim,
        ];
    }

    /**
     * Mandatory §7.2 Trustee-Takeover Invariant: requires BOTH verified institutional.existence
     * AND verified unexpired institutional.authority for the applicant.
     */
    public function assertCanApproveInstitution(string $tenantId, string $entityId, string $applicantIdentityId): void
    {
        $hasExistence = VerificationClaim::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where('subject_type', 'temple')
            ->where('subject_id', $entityId)
            ->where('claim_kind', ClaimKind::INSTITUTIONAL_EXISTENCE->value)
            ->where('status', ClaimStatus::GRANTED->value)
            ->exists();

        if (! $hasExistence) {
            throw new TrusteeTakeoverBlockedException('Verified institutional.existence claim is required before institution approval.');
        }

        $hasAuthority = VerificationAuthorityGrant::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where('entity_id', $entityId)
            ->where('person_identity_id', $applicantIdentityId)
            ->whereNull('revoked_at')
            ->where(function ($q): void {
                $q->whereNull('effective_to')->orWhere('effective_to', '>', now()->utc());
            })
            ->exists();

        $hasAuthorityClaim = VerificationClaim::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where('subject_type', 'temple')
            ->where('subject_id', $entityId)
            ->where('claim_kind', ClaimKind::INSTITUTIONAL_AUTHORITY->value)
            ->where('status', ClaimStatus::GRANTED->value)
            ->where(function ($q): void {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now()->utc());
            })
            ->exists();

        if (! $hasAuthority || ! $hasAuthorityClaim) {
            throw new TrusteeTakeoverBlockedException(
                'Applicant lacks active verified institutional.authority; trustee takeover blocked (§7.2).',
                'institutional_authority_required',
                403,
            );
        }
    }

    public function assertCanAttachPayoutInstrument(string $tenantId, string $entityId, string $applicantIdentityId): void
    {
        $this->assertCanApproveInstitution($tenantId, $entityId, $applicantIdentityId);

        $bankGrant = VerificationAuthorityGrant::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where('entity_id', $entityId)
            ->where('person_identity_id', $applicantIdentityId)
            ->whereNull('revoked_at')
            ->whereIn('authority_scope', [
                AuthorityScope::BANK_MANDATE->value,
                AuthorityScope::FULL_REPRESENTATION->value,
            ])
            ->where(function ($q): void {
                $q->whereNull('effective_to')->orWhere('effective_to', '>', now()->utc());
            })
            ->exists();

        if (! $bankGrant) {
            throw new TrusteeTakeoverBlockedException(
                'Authority scope does not permit bank_mandate attachment.',
                'bank_mandate_authority_required',
                403,
            );
        }
    }

    /**
     * Authorizes institutional bank mandate modification with dual reviewer + second-trustee co-sign
     * and returns mandatory 48h cooling-off expiry timestamp (T-TR04-07).
     *
     * @return array{authorized: true, cooling_off_until: string}
     */
    public function authorizeInstitutionalBankMandate(
        string $tenantId,
        string $entityId,
        string $applicantIdentityId,
        string $coSignTrusteeId,
        string $reviewerId,
        string $countersignerId,
        int $coolingOffHours = 48,
    ): array {
        $this->assertCanAttachPayoutInstrument($tenantId, $entityId, $applicantIdentityId);

        if ($coSignTrusteeId === '' || $coSignTrusteeId === $applicantIdentityId) {
            throw new TrusteeTakeoverBlockedException(
                'Institutional payout mandate requires a distinct second-trustee co-sign.',
                'second_trustee_cosign_required',
                422,
            );
        }

        if ($countersignerId === '' || $countersignerId === $reviewerId) {
            throw new TrusteeTakeoverBlockedException(
                'Institutional payout mandate requires dual-reviewer countersignature.',
                'dual_reviewer_required',
                422,
            );
        }

        $coolingOffUntil = now()->utc()->addHours($coolingOffHours);

        $this->audit->record(
            'onboarding.institution.bank_mandate_authorized',
            ['module' => 'onboarding', 'resource_type' => 'temple', 'resource_id' => $entityId],
            null,
            [
                'applicant_identity_id' => $applicantIdentityId,
                'co_sign_trustee_id' => $coSignTrusteeId,
                'reviewer_id' => $reviewerId,
                'countersigner_id' => $countersignerId,
                'cooling_off_until' => $coolingOffUntil->toIso8601String(),
            ],
        );

        return [
            'authorized' => true,
            'cooling_off_until' => $coolingOffUntil->toIso8601String(),
        ];
    }
}
