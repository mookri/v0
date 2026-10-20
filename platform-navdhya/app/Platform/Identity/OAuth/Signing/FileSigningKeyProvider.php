<?php

namespace App\Platform\Identity\OAuth\Signing;

use App\Platform\Identity\Federation\FederationConfigurationException;
use Laravel\Passport\Passport;
use Lcobucci\JWT\Signer;
use Lcobucci\JWT\Signer\Rsa\Sha256;

/**
 * The current, default custody (ADR-0020 D3): an inline
 * PASSPORT_PRIVATE_KEY or the oauth-private.key/oauth-public.key pair at
 * Passport's key path. Exactly the availability semantics RuntimeRole
 * booted with before custody existed — moved here so the boot check and
 * the JWKS publication resolve the same source behind one port.
 */
final class FileSigningKeyProvider implements SigningKeyProvider
{
    public function assertUsable(): void
    {
        if ((string) config('passport.private_key', '') !== '') {
            return;
        }

        if (is_readable(Passport::keyPath('oauth-private.key'))) {
            return;
        }

        throw new FederationConfigurationException(
            'file signing custody requires an inline passport.private_key or a readable oauth-private.key at the Passport key path'
        );
    }

    public function publicKeyPem(): string
    {
        $inline = (string) config('passport.public_key', '');

        if ($inline !== '') {
            return $inline;
        }

        $path = Passport::keyPath('oauth-public.key');

        if (is_readable($path)) {
            return (string) file_get_contents($path);
        }

        throw new FederationConfigurationException(
            'file signing custody requires an inline passport.public_key or a readable oauth-public.key at the Passport key path'
        );
    }

    public function jwtSigner(): Signer
    {
        // Stock local RS256 — the exact signer league's access-token
        // trait and the pre-custody ID-token mint used, so the file
        // custody's signed output stays byte-identical.
        return new Sha256();
    }

    /**
     * The private half's PEM, same source resolution as assertUsable
     * (inline first, key path second) — consumed by session-stamped
     * access-token mints (A-SEC-004-6), which must sign locally
     * through the custody's signer without reaching into league's
     * private key plumbing.
     */
    public function privateKeyPem(): string
    {
        $inline = (string) config('passport.private_key', '');

        if ($inline !== '') {
            return $inline;
        }

        $path = Passport::keyPath('oauth-private.key');

        if (is_readable($path)) {
            return (string) file_get_contents($path);
        }

        throw new FederationConfigurationException(
            'file signing custody requires an inline passport.private_key or a readable oauth-private.key at the Passport key path'
        );
    }
}
