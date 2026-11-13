<?php

namespace App\Domain\Verification\Fiscal;

use App\Domain\Verification\Contracts\BankAccountVerifierPort;
use App\Domain\Verification\Enums\EvidenceMode;

/**
 * Default manual-capture bank account verifier (T-TR05-07 / G-BANK-0).
 * Forbids penny_drop_verified status until an approved financial provider exists.
 */
final class ManualBankAccountVerifier implements BankAccountVerifierPort
{
    public function __construct(private readonly IfscStructuralValidator $validator)
    {
    }

    public function verifyAccount(string $tenantId, string $accountNumber, string $ifsc, string $declaredHolderName): array
    {
        $check = $this->validator->validate($ifsc, $accountNumber);

        return [
            'mode' => EvidenceMode::MANUAL->value,
            'status' => $check['valid'] ? 'manual_review' : 'rejected',
            'account_holder_name' => $declaredHolderName,
            'provider_reference' => null,
        ];
    }
}

