<?php

namespace App\Domain\Verification\Fiscal;

use App\Domain\Verification\Contracts\VerifiesPayoutOwnership;
use App\Domain\Verification\Enums\ClaimKind;
use App\Domain\Verification\Enums\ClaimStatus;
use App\Domain\Verification\Exceptions\PayoutEligibilityException;
use App\Domain\Verification\Models\VerificationBankInstrument;
use App\Domain\Verification\Models\VerificationClaim;

/**
 * Enforces hard Finance disbursement ownership invariant (§8.3 / T-TR05-06 / G-FISCAL-1).
 */
final class PayoutEligibilityGuard implements VerifiesPayoutOwnership
{
    public function subjectHasVerificationState(string $tenantId, string $subjectId): bool
    {
        return VerificationBankInstrument::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where('subject_id', $subjectId)
            ->exists();
    }

    public function assertEligible(
        string $tenantId,
        string $beneficiaryId,
        ?string $instrumentId = null,
    ): void {
        $query = VerificationBankInstrument::withoutGlobalScopes()
            ->where('tenant_id', $tenantId);

        if ($instrumentId !== null) {
            $instrument = (clone $query)->find($instrumentId);
            if ($instrument === null) {
                throw new PayoutEligibilityException('Target bank instrument not found.', 'payout_instrument_not_found', 404);
            }
            if ((string) $instrument->subject_id !== $beneficiaryId) {
                throw new PayoutEligibilityException('Bank instrument belongs to a different beneficiary.', 'payout_beneficiary_mismatch', 403);
            }
        } else {
            $instrument = (clone $query)
                ->where('subject_id', $beneficiaryId)
                ->orderByDesc('created_at')
                ->first();

            if ($instrument === null) {
                throw new PayoutEligibilityException('No verified payout instrument registered for beneficiary.', 'payout_instrument_missing', 403);
            }
        }

        if ($instrument->discrepancy_frozen_at !== null) {
            throw new PayoutEligibilityException(
                'Payout instrument is frozen pending discrepancy investigation.',
                'payout_instrument_frozen',
                409,
            );
        }

        if ($instrument->cooling_off_until !== null && $instrument->cooling_off_until->isFuture()) {
            throw new PayoutEligibilityException(
                'Payout instrument is inside mandatory cooling-off window.',
                'payout_instrument_cooling_off',
                409,
            );
        }

        if ($instrument->verification_status !== 'verified') {
            throw new PayoutEligibilityException(
                "Bank instrument status [{$instrument->verification_status}] is not eligible for disbursement.",
                'payout_instrument_unverified',
                403,
            );
        }

        if ($instrument->subject_type === 'temple' && $instrument->account_type === 'individual') {
            if ($instrument->approved_by === null || trim((string) $instrument->policy_exception_note) === '') {
                throw new PayoutEligibilityException(
                    'Institutional beneficiary using individual bank account requires countersigned policy exception.',
                    'institutional_individual_account_exception_required',
                    403,
                );
            }
        }

        if ($instrument->claim_id === null) {
            throw new PayoutEligibilityException('Bank instrument lacks linked financial ownership claim.', 'financial_claim_missing', 403);
        }

        $claim = VerificationClaim::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where('claim_kind', ClaimKind::FINANCIAL->value)
            ->find($instrument->claim_id);

        if (
            $claim === null
            || $claim->status !== ClaimStatus::GRANTED->value
            || ($claim->expires_at !== null && $claim->expires_at->isPast())
        ) {
            throw new PayoutEligibilityException('Linked fiscal.payout_ownership claim is inactive or expired.', 'financial_claim_inactive', 403);
        }
    }
}

