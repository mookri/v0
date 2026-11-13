<?php

namespace App\Domain\Verification\Issuers;

use App\Domain\Verification\Enums\EvidenceMode;
use App\Domain\Verification\Exceptions\ModeCeilingViolationException;
use App\Domain\Verification\Models\VerificationIssuer;

/**
 * Enforces structural claim mode ceilings based on issuer evidence grade (§6.1 / T-TR03-04).
 */
final class QualificationModeCeilingPolicy
{
    public function maxModeFor(IssuerEvidenceGrade $grade, bool $hasConfirmedResponse): EvidenceMode
    {
        if ($grade === IssuerEvidenceGrade::AUTHORITATIVE && $hasConfirmedResponse) {
            return EvidenceMode::OFFICIAL;
        }

        return EvidenceMode::MANUAL;
    }

    public function assertPermitted(
        VerificationIssuer $issuer,
        EvidenceMode $requestedMode,
        bool $hasConfirmedResponse,
    ): void {
        if ($requestedMode === EvidenceMode::FINANCIAL) {
            throw new ModeCeilingViolationException('Qualification claims cannot attain financial evidence mode.');
        }

        $grade = IssuerEvidenceGrade::from($issuer->evidence_grade);
        $maxMode = $this->maxModeFor($grade, $hasConfirmedResponse);

        if ($requestedMode === EvidenceMode::OFFICIAL && $maxMode !== EvidenceMode::OFFICIAL) {
            throw new ModeCeilingViolationException(
                "Issuer [{$issuer->code}] (grade={$grade->value}, confirmed={$hasConfirmedResponse}) cannot attain official mode.",
            );
        }
    }
}

