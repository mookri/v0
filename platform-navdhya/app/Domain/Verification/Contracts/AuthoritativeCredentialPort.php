<?php

namespace App\Domain\Verification\Contracts;

/**
 * Optional NAD / DigiLocker authoritative credential verification port (T-TR03-08).
 */
interface AuthoritativeCredentialPort
{
    /**
     * @return array{verified: bool, official_ref: string|null, reason: string}
     */
    public function verifyFromRegistry(string $tenantId, string $identityId, string $issuerCode, string $certificateRef): array;
}

