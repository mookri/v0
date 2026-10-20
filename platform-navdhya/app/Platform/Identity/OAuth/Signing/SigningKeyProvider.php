<?php

namespace App\Platform\Identity\OAuth\Signing;

use App\Platform\Identity\Federation\FederationConfigurationException;
use Lcobucci\JWT\Signer;

/**
 * Token-signing key custody (ADR-0020 D3, custody extension): where the
 * issuer's signing material lives. The default custody is the local key
 * file pair Passport resolves today; an external KMS/HSM custody (the
 * private half never existing in process memory) plugs in behind the
 * same three obligations:
 *
 *  - assertUsable(): fail-closed boot validation — custody that cannot
 *    mint must refuse the boot, never come up and degrade at request
 *    time (same posture as RuntimeRole/FederationMode).
 *  - publicKeyPem(): the public half JWKS publishes — custody-agnostic
 *    consumers keep verifying without knowing where signing happens.
 *  - jwtSigner(): the lcobucci signer every mint path signs with —
 *    stock local RS256 for the file custody (byte-exact pre-custody
 *    behavior), the port-backed remote signer when the private half
 *    lives outside this process.
 *
 * The private-half operation (remote signing through the KMS endpoint)
 * rides the SignsTokenRemotely port: KMS custody is only usable once a
 * port implementation is bound — the issuer refuses the boot otherwise
 * (see KmsSigningKeyProvider), never publishing a key it cannot sign
 * under.
 */
interface SigningKeyProvider
{
    /**
     * @throws FederationConfigurationException when this custody cannot mint
     */
    public function assertUsable(): void;

    /**
     * @throws FederationConfigurationException when no public half can be resolved
     */
    public function publicKeyPem(): string;

    /**
     * The signer minted tokens (ID tokens, access tokens under remote
     * custody) are signed with. Algorithm stays RS256 across custodies
     * — consumers never see an algorithm change, only where signing
     * happens.
     */
    public function jwtSigner(): Signer;
}
