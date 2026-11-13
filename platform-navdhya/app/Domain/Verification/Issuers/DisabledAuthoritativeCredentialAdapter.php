<?php

namespace App\Domain\Verification\Issuers;

use App\Domain\Privacy\Consent\ConsentManager;
use App\Domain\Privacy\Enums\ConsentPurpose;
use App\Domain\Privacy\Providers\ProviderResidencyGate;
use App\Domain\Verification\Contracts\AuthoritativeCredentialPort;

/**
 * Default adapter locked behind approved provider registration + explicit consent (T-TR03-08).
 */
final class DisabledAuthoritativeCredentialAdapter implements AuthoritativeCredentialPort
{
    public function __construct(
        private readonly ProviderResidencyGate $residencyGate,
        private readonly ConsentManager $consents,
    ) {
    }

    public function verifyFromRegistry(string $tenantId, string $identityId, string $issuerCode, string $certificateRef): array
    {
        $this->residencyGate->assertApproved('nad_digilocker');
        $this->consents->requireConsent($tenantId, $identityId, ConsentPurpose::QUALIFICATION_OUTREACH);

        return [
            'verified' => false,
            'official_ref' => null,
            'reason' => 'No live NAD/DigiLocker integration active; fallback to manual issuer outreach.',
        ];
    }
}

