<?php

namespace App\Domain\Verification\Fiscal;

use App\Domain\Verification\Contracts\TaxRegistrationVerifierPort;
use App\Domain\Verification\Enums\EvidenceMode;

/**
 * Default native/manual tax registration verifier (T-TR05-07 / A-3).
 * Official GSP lookups stay locked behind verification_provider_registry approval.
 */
final class DisabledTaxRegistrationVerifier implements TaxRegistrationVerifierPort
{
    public function __construct(private readonly GstinStructuralValidator $validator)
    {
    }

    public function verifyGstin(string $tenantId, string $gstin): array
    {
        $check = $this->validator->validate($gstin);

        return [
            'mode' => EvidenceMode::NATIVE->value,
            'status' => $check['valid'] ? 'active' : 'not_found',
            'legal_name' => null,
            'reason_codes' => $check['reason_codes'],
        ];
    }
}

