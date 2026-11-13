<?php

namespace App\Domain\Verification\Contracts;

interface BankAccountVerifierPort
{
    /**
     * @return array{mode: string, status: string, account_holder_name: string|null, provider_reference: string|null}
     */
    public function verifyAccount(string $tenantId, string $accountNumber, string $ifsc, string $declaredHolderName): array;
}

