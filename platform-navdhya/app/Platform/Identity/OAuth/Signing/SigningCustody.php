<?php

namespace App\Platform\Identity\OAuth\Signing;

use App\Platform\Identity\Federation\FederationConfigurationException;

/**
 * AUTH_SIGNING_CUSTODY: resolves the configured signing-key custody to
 * its provider. Unknown custodies fail closed — a typo must refuse the
 * boot (same posture as RuntimeRole::assertValid), never silently fall
 * back to local key files: custody is a security boundary, not a
 * default.
 */
final class SigningCustody
{
    public const FILE = 'file';

    public const KMS = 'kms';

    /**
     * @throws FederationConfigurationException on an unknown custody value
     */
    public static function resolve(): SigningKeyProvider
    {
        $custody = (string) config('federation.signing.custody', self::FILE);

        return match ($custody) {
            self::FILE => new FileSigningKeyProvider(),
            self::KMS => new KmsSigningKeyProvider(
                publicKey: (string) config('federation.signing.kms.public_key', ''),
                keyResource: (string) config('federation.signing.kms.key_resource', ''),
                endpoint: (string) config('federation.signing.kms.endpoint', ''),
            ),
            default => throw new FederationConfigurationException(
                "AUTH_SIGNING_CUSTODY must be '".self::FILE."' or '".self::KMS."', got '{$custody}'"
            ),
        };
    }

    /**
     * Single-source materialization (D3 custody): Passport/league
     * resolve their own key slots from config("passport.*_key") (the
     * ResourceServer + BearerTokenValidator verification side, the
     * AuthorizationServer CryptKey). Under remote custody those slots
     * must point at the custody's public half, so the custody provider
     * — the one source of truth — writes them at boot.
     *
     * The private slot receives the SAME public PEM as a structural
     * sentinel: league's CryptKey validates form only (a public PEM
     * passes its RSA/EC check), every mint site is intercepted before
     * signing (CustodyAccessToken::toString, the ID-token grant), and
     * anything that slips through fails LOUDLY — openssl_sign rejects a
     * public key — instead of silently signing under a key consumers
     * don't have. No real private half ever exists in this process.
     *
     * File custody is untouched: Passport's own config/file resolution
     * already stands, byte-identical to pre-custody behavior.
     */
    public static function materializePassportConfig(): void
    {
        $provider = self::resolve();

        if ($provider instanceof FileSigningKeyProvider) {
            return;
        }

        $pem = $provider->publicKeyPem();

        config([
            'passport.public_key' => $pem,
            'passport.private_key' => $pem,
        ]);
    }
}
