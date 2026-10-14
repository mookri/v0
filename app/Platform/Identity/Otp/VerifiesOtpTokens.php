<?php

namespace App\Platform\Identity\Otp;

/**
 * The token-verification authority port: turn a client-presented
 * access token into "which identifier does the provider attest".
 * Implementations encapsulate the vendor entirely — swapping MSG91
 * for another authority is a new adapter + a container rebinding;
 * call sites and the challenge manager never learn the vendor.
 *
 * @throws OtpProviderUnavailableException when the authority is
 *         unreachable or quota-blocked (typed dependency failure —
 *         never a mismatch, never a burn of an attempt)
 */
interface VerifiesOtpTokens
{
    public function verify(string $accessToken): OtpTokenResult;
}
