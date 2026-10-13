<?php

namespace App\Modules\Customers\Contracts;

use App\Modules\Customers\Auth\FirebaseIdTokenRejectedException;
use App\Modules\Customers\Auth\FirebaseIdTokenUnavailableException;

/**
 * Phone-proof hand-off for the Firebase login flow: the client SDK has
 * already sent the SMS and confirmed the OTP with Firebase; this seam
 * turns the resulting ID token into the three claims the exchange is
 * allowed to trust (the verified phone, the sign-in provider, and
 * auth_time). Production binds the kreait-backed verifier; tests rebind
 * this contract with a scripted fake, so no test ever needs a real
 * service account or network call.
 */
interface VerifiesPhoneIdTokens
{
    /**
     * The token itself is a bearer credential and must never be echoed
     * back out of this seam — only the extracted claims return.
     *
     * @return array{phone: string, provider: string, auth_time: int}
     *
     * @throws FirebaseIdTokenRejectedException  when Firebase does not vouch for the token (invalid, expired, revoked, foreign project)
     * @throws FirebaseIdTokenUnavailableException  when the verifier cannot run (credentials missing/broken, Firebase unreachable)
     */
    public function verify(string $idToken): array;
}
