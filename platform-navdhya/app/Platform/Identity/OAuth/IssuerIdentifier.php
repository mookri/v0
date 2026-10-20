<?php

namespace App\Platform\Identity\OAuth;

/**
 * The single issuer-identifier derivation (ADR-0020 federation.issuer:
 * prod auth.navdhya.com, dev auth.localhost). Discovery, the RFC 9207
 * `iss` response parameter and ID-token minting MUST agree byte for
 * byte — a mismatch would make BFF callbacks reject valid responses
 * (or, worse, accept a response bound to a different issuer), so the
 * derivation lives in exactly one place.
 */
final class IssuerIdentifier
{
    public static function value(): string
    {
        $issuer = rtrim((string) config('federation.issuer'), '/');

        return $issuer !== '' ? $issuer : rtrim((string) config('app.url'), '/');
    }
}
