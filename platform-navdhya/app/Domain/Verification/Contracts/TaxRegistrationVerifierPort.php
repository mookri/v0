<?php

namespace App\Domain\Verification\Contracts;

interface TaxRegistrationVerifierPort
{
    /**
     * @return array{mode: string, status: string, legal_name: string|null, reason_codes: list<string>}
     */
    public function verifyGstin(string $tenantId, string $gstin): array;
}

