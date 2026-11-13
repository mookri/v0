<?php

namespace App\Domain\Verification\Contracts;

interface VerifiesPayoutOwnership
{
    public function subjectHasVerificationState(string $tenantId, string $subjectId): bool;

    public function assertEligible(
        string $tenantId,
        string $beneficiaryId,
        ?string $instrumentId = null,
    ): void;
}

