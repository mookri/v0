<?php

namespace App\Domain\Verification\Fiscal;

use App\Domain\Privacy\Support\IdentityFingerprint;
use App\Domain\Verification\Claims\ClaimManager;
use App\Domain\Verification\Enums\ClaimKind;
use App\Domain\Verification\Enums\ConfidenceLevel;
use App\Domain\Verification\Enums\EvidenceMode;
use App\Domain\Verification\Identity\NameMatcher;
use App\Domain\Verification\Models\VerificationBankInstrument;
use App\Domain\Verification\Models\VerificationTaxRegistration;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;
use InvalidArgumentException;

/**
 * Encrypts fiscal secrets (GSTIN, bank accounts, IFSC) at intake and drives structural
 * tax & bank claim verification (§8 / T-TR05-01..08).
 */
final class FiscalIntakeService
{
    public function __construct(
        private readonly GstinStructuralValidator $gstinValidator,
        private readonly IfscStructuralValidator $ifscValidator,
        private readonly NameMatcher $nameMatcher,
        private readonly ClaimManager $claims,
    ) {
    }

    /**
     * @return array{registration: VerificationTaxRegistration, reason_codes: list<string>}
     */
    public function recordTaxRegistration(
        string $tenantId,
        string $subjectType,
        string $subjectId,
        string $gstStatus,
        ?string $rawGstin,
        ?string $legalName = null,
        ?string $applicationId = null,
        string $actorId = 'system',
        string $registrationOutcome = 'active',
        ?Model $subjectModel = null,
    ): array {
        $allowedGstStatuses = ['registered', 'composition_dealer', 'exempt_below_threshold', 'unregistered_individual'];
        if (! in_array($gstStatus, $allowedGstStatuses, true)) {
            throw new InvalidArgumentException("Unsupported gst_status [{$gstStatus}].");
        }

        $isExempt = in_array($gstStatus, ['exempt_below_threshold', 'unregistered_individual'], true);

        if ($isExempt && ($rawGstin === null || trim($rawGstin) === '')) {
            $reg = new VerificationTaxRegistration();
            $reg->forceFill([
                'tenant_id' => $tenantId,
                'subject_type' => $subjectType,
                'subject_id' => $subjectId,
                'application_id' => $applicationId,
                'gst_status' => $gstStatus,
                'registration_status' => 'exempt',
                'verifier_mode' => EvidenceMode::NATIVE->value,
                'legal_name' => $legalName,
                'verified_by' => $actorId,
                'verified_at' => now()->utc(),
            ])->save();

            $claim = $this->claims->grantClaim([
                'tenant_id' => $tenantId,
                'subject_type' => $subjectType,
                'subject_id' => $subjectId,
                'application_id' => $applicationId,
                'claim_kind' => ClaimKind::TAX->value,
                'mode' => EvidenceMode::NATIVE->value,
                'confidence' => ConfidenceLevel::MEDIUM->value,
                'reason_code' => 'GST_EXEMPT_PATH_DECLARED',
                'decided_by' => $actorId,
                'metadata' => ['gst_status' => $gstStatus],
            ], [], [], $subjectModel);

            $reg->forceFill(['verification_claim_id' => (string) $claim->getKey()])->save();

            return ['registration' => $reg->fresh(), 'reason_codes' => []];
        }

        $check = $this->gstinValidator->validate((string) $rawGstin);
        if (! $check['valid']) {
            throw new InvalidArgumentException('Invalid GSTIN: ' . implode(',', $check['reason_codes']));
        }

        $gstin = $check['normalized'];
        $masked = substr($gstin, 0, 2) . str_repeat('*', 11) . substr($gstin, -2);

        $reg = new VerificationTaxRegistration();
        $reg->forceFill([
            'tenant_id' => $tenantId,
            'subject_type' => $subjectType,
            'subject_id' => $subjectId,
            'application_id' => $applicationId,
            'gst_status' => $gstStatus,
            'gstin_encrypted' => Crypt::encryptString($gstin),
            'gstin_hmac' => IdentityFingerprint::hash('gstin', $gstin),
            'gstin_masked' => $masked,
            'state_code' => $check['state_code'],
            'legal_name' => $legalName,
            'registration_status' => $registrationOutcome,
            'verifier_mode' => EvidenceMode::NATIVE->value,
            'verified_by' => $actorId,
            'verified_at' => now()->utc(),
        ])->save();

        // §8.1 outcome mapping: only 'active' grants tax claim; 'cancelled' held/rejected; 'suspended' => ENHANCED_REVIEW.
        if ($registrationOutcome === 'active') {
            $claim = $this->claims->grantClaim([
                'tenant_id' => $tenantId,
                'subject_type' => $subjectType,
                'subject_id' => $subjectId,
                'application_id' => $applicationId,
                'claim_kind' => ClaimKind::TAX->value,
                'mode' => EvidenceMode::NATIVE->value,
                'confidence' => ConfidenceLevel::MEDIUM->value,
                'reason_code' => 'GSTIN_STRUCTURE_VALIDATED',
                'decided_by' => $actorId,
                'metadata' => ['gstin_masked' => $masked, 'state_code' => $check['state_code']],
            ], [], [], $subjectModel);
            $reg->forceFill(['verification_claim_id' => (string) $claim->getKey()])->save();
        } elseif ($registrationOutcome === 'suspended') {
            $this->claims->grantClaim([
                'tenant_id' => $tenantId,
                'subject_type' => $subjectType,
                'subject_id' => $subjectId,
                'application_id' => $applicationId,
                'claim_kind' => ClaimKind::ENHANCED_REVIEW->value,
                'mode' => EvidenceMode::MANUAL->value,
                'confidence' => ConfidenceLevel::HIGH->value,
                'reason_code' => 'GSTIN_SUSPENDED_HOLD',
                'decided_by' => $actorId,
            ]);
        }

        return ['registration' => $reg->fresh(), 'reason_codes' => $check['reason_codes']];
    }

    /**
     * Encrypt & store bank account + IFSC in verification_bank_instruments with masked tails and Indic name match triage.
     *
     * @return array{instrument: VerificationBankInstrument, name_match_band: string}
     */
    public function recordBankInstrument(
        string $tenantId,
        string $subjectType,
        string $subjectId,
        string $accountNumber,
        string $ifsc,
        string $accountHolderName,
        string $declaredEntityName,
        string $accountType = 'individual',
        ?string $applicationId = null,
        ?string $supersedesId = null,
        ?DateTimeInterface $coolingOffUntil = null,
    ): array {
        $check = $this->ifscValidator->validate($ifsc, $accountNumber);
        if (! $check['valid']) {
            throw new InvalidArgumentException('Invalid bank details: ' . implode(',', $check['reason_codes']));
        }

        $cleanAccount = preg_replace('/\s+/', '', trim($accountNumber)) ?? '';
        $last4 = substr($cleanAccount, -4);
        $cleanIfsc = $check['ifsc'];
        $ifscMasked = substr($cleanIfsc, 0, 5) . str_repeat('•', 6);
        $match = $this->nameMatcher->compare($declaredEntityName, $accountHolderName);

        $instrument = new VerificationBankInstrument();
        $instrument->forceFill([
            'tenant_id' => $tenantId,
            'subject_type' => $subjectType,
            'subject_id' => $subjectId,
            'application_id' => $applicationId,
            'account_fingerprint' => IdentityFingerprint::hash('bank_acct', $cleanIfsc . '|' . $cleanAccount),
            'account_number_encrypted' => Crypt::encryptString($cleanAccount),
            'account_last4' => $last4,
            'ifsc_encrypted' => Crypt::encryptString($cleanIfsc),
            'ifsc_prefix_masked' => $ifscMasked,
            'account_holder_name' => $accountHolderName,
            'account_type' => $accountType,
            'verifier_mode' => EvidenceMode::MANUAL->value,
            'verification_status' => 'pending',
            'name_match_score' => $match->score,
            'supersedes_id' => $supersedesId,
            'cooling_off_until' => $coolingOffUntil,
        ])->save();

        return [
            'instrument' => $instrument->fresh(),
            'name_match_band' => $match->band->value,
        ];
    }

    public function verifyBankInstrumentManually(
        string $tenantId,
        string $instrumentId,
        string $reviewerId,
        ?string $countersignedBy = null,
        ?string $policyExceptionNote = null,
    ): VerificationBankInstrument {
        $instrument = VerificationBankInstrument::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->findOrFail($instrumentId);

        $claim = $this->claims->grantClaim([
            'tenant_id' => $tenantId,
            'subject_type' => $instrument->subject_type,
            'subject_id' => $instrument->subject_id,
            'application_id' => $instrument->application_id,
            'claim_kind' => ClaimKind::FINANCIAL->value,
            'mode' => EvidenceMode::FINANCIAL->value,
            'confidence' => ConfidenceLevel::HIGH->value,
            'reason_code' => 'BANK_OWNERSHIP_REVIEWED',
            'decided_by' => $reviewerId,
            'countersigned_by' => $countersignedBy,
            'metadata' => [
                'instrument_id' => (string) $instrument->getKey(),
                'account_last4' => $instrument->account_last4,
            ],
        ]);

        $instrument->forceFill([
            'verification_status' => 'verified',
            'verifier_mode' => EvidenceMode::FINANCIAL->value,
            'verified_at' => now()->utc(),
            'approved_at' => now()->utc(),
            'approved_by' => $countersignedBy ?? $reviewerId,
            'policy_exception_note' => $policyExceptionNote,
            'claim_id' => (string) $claim->getKey(),
        ])->save();

        return $instrument->fresh();
    }
}

