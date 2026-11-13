<?php

namespace App\Domain\Verification\Issuers;

use App\Domain\Privacy\Consent\ConsentManager;
use App\Domain\Privacy\Enums\ConsentPurpose;
use App\Domain\Privacy\Support\IdentityFingerprint;
use App\Domain\Verification\Claims\ClaimManager;
use App\Domain\Verification\Enums\ClaimKind;
use App\Domain\Verification\Enums\ConfidenceLevel;
use App\Domain\Verification\Enums\EvidenceMode;
use App\Domain\Verification\Models\VerificationClaim;
use App\Domain\Verification\Models\VerificationCredential;
use App\Domain\Verification\Models\VerificationCredentialResponse;
use App\Domain\Verification\Models\VerificationIssuer;
use App\Platform\Audit\AuditWriter;
use Illuminate\Database\Eloquent\Model;

/**
 * Manages credential intake, duplicate certificate reuse signals, consent-gated issuer outreach,
 * mode ceiling enforcement, and non-response degradation (§6 / T-TR03-03, T-TR03-04, T-TR03-07).
 */
final class IssuerOutreachService
{
    public function __construct(
        private readonly ConsentManager $consents,
        private readonly ClaimManager $claims,
        private readonly QualificationModeCeilingPolicy $modeCeiling,
        private readonly AuditWriter $audit,
    ) {
    }

    /**
     * @param  list<string>  $subjectScopes
     * @return array{credential: VerificationCredential, duplicate_detected: bool, disclosure: string|null}
     */
    public function registerCredential(
        string $tenantId,
        string $identityId,
        string $subjectType,
        string $subjectId,
        string $issuerCodeOrId,
        string $credentialTypeInput,
        string $certificateRef,
        ?int $awardYear = null,
        ?string $programmeRef = null,
        array $subjectScopes = [],
        ?string $applicationId = null,
    ): array {
        $category = CredentialCategory::fromInput($credentialTypeInput);
        $issuer = $this->resolveIssuer($issuerCodeOrId);

        $hmac = IdentityFingerprint::hash('certificate_ref', $issuer->getKey() . '|' . trim($certificateRef));
        $masked = strlen(trim($certificateRef)) > 4
            ? str_repeat('*', max(4, strlen(trim($certificateRef)) - 4)) . substr(trim($certificateRef), -4)
            : '****';

        // T-TR03-07.2: Cross-account certificate reuse detection.
        $duplicate = VerificationCredential::withoutGlobalScopes()
            ->where('issuer_id', (string) $issuer->getKey())
            ->where('certificate_ref_hmac', $hmac)
            ->where('identity_id', '!=', $identityId)
            ->whereNull('revoked_at')
            ->exists();

        $scopes = $subjectScopes !== [] ? $subjectScopes : $category->unlockedServiceCategories();

        $credential = new VerificationCredential();
        $credential->forceFill([
            'tenant_id' => $tenantId,
            'identity_id' => $identityId,
            'subject_type' => $subjectType,
            'subject_id' => $subjectId,
            'application_id' => $applicationId,
            'issuer_id' => (string) $issuer->getKey(),
            'credential_type' => $category->value,
            'certificate_ref_hmac' => $hmac,
            'certificate_ref_masked' => $masked,
            'award_year' => $awardYear,
            'programme_ref' => $programmeRef,
            'subject_scope_json' => $scopes,
            'mode_attained' => EvidenceMode::MANUAL->value,
        ])->save();

        if ($duplicate) {
            $this->claims->grantClaim([
                'tenant_id' => $tenantId,
                'subject_type' => $subjectType,
                'subject_id' => $subjectId,
                'application_id' => $applicationId,
                'claim_kind' => ClaimKind::ENHANCED_REVIEW->value,
                'mode' => EvidenceMode::MANUAL->value,
                'confidence' => ConfidenceLevel::HIGH->value,
                'reason_code' => 'DUPLICATE_CERTIFICATE_CLAIM',
                'note' => 'Same issuer and certificate reference submitted under a distinct identity account.',
                'decided_by' => 'system:duplicate_cert_detector',
                'metadata' => [
                    'credential_id' => (string) $credential->getKey(),
                    'issuer_id' => (string) $issuer->getKey(),
                ],
            ]);
        }

        return [
            'credential' => $credential->fresh(),
            'duplicate_detected' => $duplicate,
            'disclosure' => $category->scopeDisclosure(),
        ];
    }

    public function initiateOutreach(
        string $tenantId,
        string $credentialId,
        string $actorId,
        int $slaDays = 14,
    ): VerificationCredentialResponse {
        $credential = VerificationCredential::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->findOrFail($credentialId);

        // Privacy boundary: require explicit consent before contacting third-party institution.
        $this->consents->requireConsent(
            $tenantId,
            $credential->identity_id,
            ConsentPurpose::QUALIFICATION_OUTREACH,
        );

        $response = new VerificationCredentialResponse();
        $response->forceFill([
            'tenant_id' => $tenantId,
            'credential_id' => (string) $credential->getKey(),
            'issuer_id' => $credential->issuer_id,
            'outreach_stage' => OutreachStage::REQUEST_SENT->value,
            'requested_at' => now()->utc(),
            'sla_due_at' => now()->utc()->addDays($slaDays),
            'actor_id' => $actorId,
        ])->save();

        $this->audit->record(
            'onboarding.qualification.outreach_sent',
            ['module' => 'onboarding', 'resource_type' => 'verification_credential', 'resource_id' => (string) $credential->getKey()],
            null,
            ['issuer_id' => $credential->issuer_id, 'actor_id' => $actorId],
        );

        return $response->fresh();
    }

    /**
     * @return array{response: VerificationCredentialResponse, claim: VerificationClaim|null}
     */
    public function recordOutreachOutcome(
        string $tenantId,
        string $credentialId,
        OutreachStage $stage,
        string $actorId,
        EvidenceMode $requestedMode = EvidenceMode::MANUAL,
        ?string $responderName = null,
        ?string $responderRole = null,
        ?string $officialResponseRef = null,
        ?string $notes = null,
        ?Model $subjectModel = null,
    ): array {
        $credential = VerificationCredential::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->findOrFail($credentialId);
        $issuer = VerificationIssuer::query()->findOrFail($credential->issuer_id);
        $category = CredentialCategory::from($credential->credential_type);

        $hasConfirmedResponse = $stage === OutreachStage::RESPONSE_RECEIVED
            && $responderName !== null
            && trim($responderName) !== '';

        if ($stage === OutreachStage::RESPONSE_RECEIVED) {
            $this->modeCeiling->assertPermitted($issuer, $requestedMode, $hasConfirmedResponse);
        }

        $response = new VerificationCredentialResponse();
        $response->forceFill([
            'tenant_id' => $tenantId,
            'credential_id' => (string) $credential->getKey(),
            'issuer_id' => (string) $issuer->getKey(),
            'outreach_stage' => $stage->value,
            'responder_name' => $responderName,
            'responder_role' => $responderRole,
            'response_notes' => $notes,
            'responded_at' => now()->utc(),
            'actor_id' => $actorId,
        ])->save();

        $scopes = array_map(
            static fn (string $slug): array => ['scope_type' => 'ritual_category', 'scope_value' => $slug],
            $credential->subject_scope_json ?? $category->unlockedServiceCategories(),
        );

        $claim = null;

        if ($stage === OutreachStage::RESPONSE_RECEIVED) {
            $grade = IssuerEvidenceGrade::from($issuer->evidence_grade);
            $confidence = match (true) {
                $requestedMode === EvidenceMode::OFFICIAL => ConfidenceLevel::HIGH->value,
                $grade === IssuerEvidenceGrade::RESPONDING => ConfidenceLevel::HIGH->value,
                default => ConfidenceLevel::MEDIUM->value,
            };

            $claim = $this->claims->grantClaim(
                [
                    'tenant_id' => $tenantId,
                    'subject_type' => $credential->subject_type,
                    'subject_id' => $credential->subject_id,
                    'application_id' => $credential->application_id,
                    'claim_kind' => ClaimKind::QUALIFICATION->value,
                    'mode' => $requestedMode->value,
                    'confidence' => $confidence,
                    'reason_code' => 'ISSUER_RESPONSE_RECEIVED',
                    'note' => $category->scopeDisclosure() ?? $notes,
                    'decided_by' => $actorId,
                    'metadata' => [
                        'credential_id' => (string) $credential->getKey(),
                        'issuer_code' => $issuer->code,
                        'disclosure' => $category->scopeDisclosure(),
                    ],
                ],
                $scopes,
                [[
                    'evidence_kind' => 'issuer_outreach_response',
                    'source_mode' => $requestedMode->value,
                    'source_ref' => $officialResponseRef ?? (string) $response->getKey(),
                    'verified_by' => $actorId,
                ]],
                $subjectModel,
            );

            $credential->forceFill([
                'mode_attained' => $requestedMode->value,
                'official_response_ref' => $officialResponseRef,
                'claim_id' => (string) $claim->getKey(),
            ])->save();
        } elseif ($stage === OutreachStage::NO_RESPONSE) {
            // §6.5: Offline/non-responding institution MUST fall back cleanly to manual review, never auto-reject.
            $claim = $this->claims->grantClaim(
                [
                    'tenant_id' => $tenantId,
                    'subject_type' => $credential->subject_type,
                    'subject_id' => $credential->subject_id,
                    'application_id' => $credential->application_id,
                    'claim_kind' => ClaimKind::QUALIFICATION->value,
                    'mode' => EvidenceMode::MANUAL->value,
                    'confidence' => ConfidenceLevel::LOW->value,
                    'reason_code' => 'ISSUER_NO_RESPONSE_MANUAL_FALLBACK',
                    'note' => $notes ?? 'Issuer did not respond within outreach SLA; evaluated via manual evidence review without prejudice.',
                    'decided_by' => $actorId,
                    'metadata' => [
                        'credential_id' => (string) $credential->getKey(),
                        'issuer_code' => $issuer->code,
                        'appealable' => true,
                    ],
                ],
                $scopes,
                [[
                    'evidence_kind' => 'manual_qualification_fallback',
                    'source_mode' => EvidenceMode::MANUAL->value,
                    'source_ref' => (string) $response->getKey(),
                    'verified_by' => $actorId,
                ]],
                $subjectModel,
            );

            $credential->forceFill([
                'mode_attained' => EvidenceMode::MANUAL->value,
                'claim_id' => (string) $claim->getKey(),
            ])->save();
        } elseif ($stage === OutreachStage::DENIED) {
            $claim = $this->claims->grantClaim([
                'tenant_id' => $tenantId,
                'subject_type' => $credential->subject_type,
                'subject_id' => $credential->subject_id,
                'application_id' => $credential->application_id,
                'claim_kind' => ClaimKind::ENHANCED_REVIEW->value,
                'mode' => EvidenceMode::MANUAL->value,
                'confidence' => ConfidenceLevel::HIGH->value,
                'reason_code' => 'ISSUER_DENIED_CREDENTIAL',
                'note' => $notes ?? 'Issuer explicitly denied credential authenticity; escalated for enhanced review and appeal.',
                'decided_by' => $actorId,
                'metadata' => [
                    'credential_id' => (string) $credential->getKey(),
                    'issuer_code' => $issuer->code,
                    'appealable' => true,
                ],
            ]);
        }

        return [
            'response' => $response->fresh(),
            'claim' => $claim,
        ];
    }

    private function resolveIssuer(string $issuerCodeOrId): VerificationIssuer
    {
        return VerificationIssuer::query()
            ->where('id', $issuerCodeOrId)
            ->orWhere('code', $issuerCodeOrId)
            ->firstOrFail();
    }
}
