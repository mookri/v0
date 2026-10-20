<?php

namespace App\Platform\Identity\OAuth\Backchannel;

use App\Platform\Identity\Federation\FederationConfigurationException;
use App\Platform\Identity\OAuth\IssuerIdentifier;
use App\Platform\Identity\OAuth\Jwk;
use App\Platform\Identity\OAuth\Signing\FileSigningKeyProvider;
use App\Platform\Identity\OAuth\Signing\SigningCustody;
use DateInterval;
use DateTimeImmutable;
use Illuminate\Support\Str;
use Laravel\Passport\Passport;
use Lcobucci\JWT\Encoding\ChainedFormatter;
use Lcobucci\JWT\Encoding\JoseEncoder;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\Token\Builder;

/**
 * backchannel_logout_token mint (OIDC Back-Channel Logout 1.0 §2,
 * SEC-004 / A-SEC-004-5). Claim layout per spec:
 *
 *  - iss/sub/aud, iat, exp (short — 2 min; the token is a transport
 *    credential for ONE POST, re-minted per attempt so retries never
 *    reuse a stale one),
 *  - jti (single-use identity for the RP's replay defence),
 *  - sid + sub BOTH present (the RP matches its stored OP-session id;
 *    sub covers RPs that keyed on subject),
 *  - events = {"http://schemas.openid.net/event/backchannel-logout":
 *    {}} — the claim that distinguishes a logout token from an ID
 *    token (mandatory), and typ: logout+jwt in the header for the same
 *    reason.
 *
 * Signing goes through the SAME custody port every other mint uses
 * (ID tokens: OidcAuthCodeGrant::mintIdToken — byte-equal issuer, kid
 * header from the RFC 7638 thumbprint): file custody signs with the
 * local private half, KMS custody signs through the port with the
 * sentinel key material (private half never in process).
 */
final class BackchannelLogoutTokenMint
{
    public function mint(string $sid, string $sub, string $clientId): string
    {
        $now = new DateTimeImmutable();

        $builder = (new Builder(new JoseEncoder(), ChainedFormatter::withUnixTimestampDates()))
            ->issuedBy(IssuerIdentifier::value())
            ->relatedTo($sub)
            ->permittedFor($clientId)
            ->identifiedBy((string) Str::ulid())
            ->issuedAt($now)
            ->expiresAt($now->add(new DateInterval('PT2M')))
            ->withClaim('sid', $sid)
            ->withClaim('events', [
                'http://schemas.openid.net/event/backchannel-logout' => new \stdClass(),
            ])
            ->withHeader('typ', 'logout+jwt');

        $kid = self::signingKid();

        if ($kid !== null) {
            $builder = $builder->withHeader('kid', $kid);
        }

        $custody = SigningCustody::resolve();

        return $builder
            ->getToken($custody->jwtSigner(), self::keyMaterial($custody))
            ->toString();
    }

    /**
     * The published thumbprint of the CURRENT signing public half —
     * the same derivation OidcAuthCodeGrant::signingKid uses, so the
     * logout token's kid resolves against the published JWKS by
     * construction. Null when the custody has no readable public half
     * (consumers fall back to trying the small key set).
     */
    private static function signingKid(): ?string
    {
        try {
            $pem = SigningCustody::resolve()->publicKeyPem();
        } catch (FederationConfigurationException) {
            return null;
        }

        return Jwk::thumbprintFromPem($pem);
    }

    /**
     * Key material for the custody's signer: the local private half
     * under file custody (inline PASSPORT_PRIVATE_KEY or the
     * oauth-private.key pair at Passport's key path — the exact sources
     * FileSigningKeyProvider::assertUsable accepts), the structural
     * sentinel under remote custody (the port-backed signer ignores it,
     * same pattern as CustodyAccessToken). The instanceof check runs
     * FIRST: under KMS the config slot holds the public PEM sentinel,
     * which must never be offered as signing material.
     *
     * @param  \App\Platform\Identity\OAuth\Signing\SigningKeyProvider  $custody
     */
    private static function keyMaterial($custody): InMemory
    {
        if (! $custody instanceof FileSigningKeyProvider) {
            return InMemory::plainText('unused-remote-custody');
        }

        $inline = (string) config('passport.private_key', '');

        if ($inline !== '') {
            return InMemory::plainText($inline);
        }

        return InMemory::file('file://'.Passport::keyPath('oauth-private.key'));
    }
}
