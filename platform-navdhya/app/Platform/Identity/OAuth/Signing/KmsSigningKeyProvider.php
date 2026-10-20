<?php

namespace App\Platform\Identity\OAuth\Signing;

use App\Platform\Identity\Federation\FederationConfigurationException;
use Lcobucci\JWT\Signer;

/**
 * External KMS/HSM custody (AUTH_SIGNING_CUSTODY=kms): the private half
 * never exists in process memory — minting calls the custody's sign
 * endpoint through the SignsTokenRemotely port (AppServiceProvider
 * binds the KMS HTTP signer the moment this custody is configured).
 *
 * Fail-closed usability: the issuer boots under this custody only when
 * its configuration validates (all pieces present, public half a
 * parsable asymmetric key) AND the signer port is bound. Without a
 * bound port nothing can mint under the published key — booting anyway
 * would publish the KMS public key in JWKS while league either fails or
 * (worse) falls back to local key files: minting silently unverifiable
 * tokens. A custody that cannot mint says so; it never pretends.
 */
final class KmsSigningKeyProvider implements SigningKeyProvider
{
    public function __construct(
        private readonly string $publicKey,
        private readonly string $keyResource,
        private readonly string $endpoint,
    ) {
    }

    public function assertUsable(): void
    {
        if ($this->publicKey === '' || $this->keyResource === '' || $this->endpoint === '') {
            throw new FederationConfigurationException(
                'AUTH_SIGNING_CUSTODY=kms requires federation.signing.kms.public_key, federation.signing.kms.key_resource and federation.signing.kms.endpoint'
            );
        }

        $key = @openssl_pkey_get_public($this->publicKey);

        if ($key === false) {
            throw new FederationConfigurationException(
                'federation.signing.kms.public_key is not a parsable asymmetric public key'
            );
        }

        openssl_free_key($key);

        // Config-valid, but the private-half operation must exist too:
        // no bound port means no way to sign under the published key.
        // Refuse the boot (fail closed, never "publish one key and
        // sign with another"). This check is why the port binding is
        // registered BEFORE RuntimeRole's custody validation in
        // AppServiceProvider::register().
        if (! app()->bound(SignsTokenRemotely::class)) {
            throw new FederationConfigurationException(
                'AUTH_SIGNING_CUSTODY=kms is config-valid but the remote signer is not wired to the token mint path yet (no SignsTokenRemotely implementation bound); refusing to boot rather than publish one key and sign with another'
            );
        }
    }

    public function publicKeyPem(): string
    {
        return $this->publicKey;
    }

    public function jwtSigner(): Signer
    {
        return new RemoteSigningSigner(app(SignsTokenRemotely::class));
    }
}
