<?php

namespace App\Platform\Identity\OAuth\Signing;

/**
 * The private-half signing operation for external custody (KMS/HSM):
 * RSASSA-PKCS1-v1_5 over SHA-256 (RS256, the issuer's current alg) with
 * the private key never leaving its custody boundary. The lcobucci/league
 * adapter that routes token signatures through this port ships with the
 * KMS signer implementation — the port exists now so the custody swap is
 * a binding, not a redesign (mirrors the OTP subsystem's VerifiesOtpTokens
 * port ahead of the provider swap).
 */
interface SignsTokenRemotely
{
    /**
     * Sign the raw JWT signing input (header.payload), returning the
     * binary signature.
     */
    public function sign(string $signingInput): string;
}
