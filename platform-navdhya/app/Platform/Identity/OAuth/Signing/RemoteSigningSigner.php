<?php

namespace App\Platform\Identity\OAuth\Signing;

use App\Platform\Identity\Federation\AuthEventRecorder;
use Lcobucci\JWT\Signer;
use Lcobucci\JWT\Signer\Key;
use Lcobucci\JWT\Signer\Rsa\Sha256;
use Throwable;

/**
 * lcobucci adapter over the SignsTokenRemotely port: `sign()` routes
 * the raw JWT signing input (header.payload) to the custody — the Key
 * argument is stock callers passing deployment key material and is NOT
 * the source of the private half under remote custody. `verify()`
 * stays local: the public half is custody-agnostic (the same JWKS PEM
 * consumers verify with), so verification never costs a remote call.
 *
 * A signing failure is a D5-auditable event (a dead KMS endpoint is an
 * outage — the chain is where ops sees it first), recorded before the
 * failure propagates; a broken audit store never masks the original
 * signing error.
 *
 * The algorithm id stays RS256 — identical to the file custody — so
 * consumers see no algorithm change, only where signing happens.
 */
final class RemoteSigningSigner implements Signer
{
    private readonly Sha256 $localVerifier;

    public function __construct(private readonly SignsTokenRemotely $signer)
    {
        $this->localVerifier = new Sha256();
    }

    public function algorithmId(): string
    {
        return 'RS256';
    }

    public function sign(string $payload, Key $key): string
    {
        try {
            return $this->signer->sign($payload);
        } catch (Throwable $failure) {
            try {
                app(AuthEventRecorder::class)->record([
                    'event_type' => 'token.signing_failed',
                    'decision' => 'error',
                    'reason_code' => 'remote_signer_failed',
                ]);
            } catch (Throwable) {
                // Intentionally swallowed: the signing failure below is
                // the error that must reach the caller intact.
            }

            throw $failure;
        }
    }

    public function verify(string $expected, string $payload, Key $key): bool
    {
        return $this->localVerifier->verify($expected, $payload, $key);
    }
}
