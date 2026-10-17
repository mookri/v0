<?php

namespace App\Platform\Identity\OAuth;

use App\Platform\Identity\OAuth\Signing\FileSigningKeyProvider;
use App\Platform\Identity\OAuth\Signing\SigningCustody;
use DateTimeImmutable;
use Lcobucci\JWT\Encoding\ChainedFormatter;
use Lcobucci\JWT\Encoding\JoseEncoder;
use Lcobucci\JWT\Signer;
use Lcobucci\JWT\Signer\Key;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\Token\Builder;
use Laravel\Passport\Bridge\AccessToken;

/**
 * Custody-aware access-token mint (ADR-0020 D3 custody extension).
 * Registered for every deployment via Passport::useAccessTokenEntity —
 * league's `BearerTokenResponse` emits `$accessToken->toString()`, so
 * overriding that ONE method is the complete interception point for
 * every grant (auth code, refresh, client credentials, token
 * exchange): no grant, response type or vendor path can mint a token
 * around it.
 *
 *  - File custody (default), unstamped: league's stock local mint,
 *    byte-exact — parent::toString() with the private key league handed
 *    the entity through setPrivateKey. Zero format drift for existing
 *    consumers.
 *  - Remote custody (KMS/HSM), unstamped: the private half never enters
 *    this process. The claim layout is mirrored body-for-body from
 *    league 9.4.1 AccessTokenTrait::convertToJWT (keep in sync — the
 *    same fork discipline as OidcAuthCodeGrant) and the token is signed
 *    through the custody's port-backed signer. The Key argument is the
 *    materialized sentinel (the custody's public PEM), which the
 *    remote signer ignores.
 *  - Session-stamped mints (A-SEC-004-6, both custodies): one claim
 *    layout — the league mirror plus the `sid` claim of the OP session
 *    the grant stamped via withSessionId — differing only in signer
 *    (stock RS256 over the private key, or the custody's remote signer
 *    over the sentinel). The same sid is persisted on the token ROW by
 *    SessionBoundAccessTokenRepository, so a logout_token's `sid`, the
 *    revoked row's `sid` and this claim can never disagree.
 *  - DPoP-stamped mints (A-SEC-004-9, RFC 9449): the grant stamps the
 *    proof's JWK thumbprint via withDpopJkt and the JWT carries it as
 *    `cnf.dpop_jkt` (sender-constraint: a stolen token is worthless
 *    without the proof key). The same jkt lands on the ROW in the same
 *    persist INSERT — cnf claim, row column and the enforced key are
 *    one value by construction, like sid. A DPoP-stamped mint always
 *    takes the stamped layout even without a session.
 */
final class CustodyAccessToken extends AccessToken
{
    private ?string $sessionSid = null;

    private ?string $dpopJkt = null;

    /**
     * Stamp the OP session id before persist/mint (A-SEC-004-6).
     */
    public function withSessionId(string $sid): void
    {
        $this->sessionSid = $sid !== '' ? $sid : null;
    }

    public function sessionId(): ?string
    {
        return $this->sessionSid;
    }

    /**
     * Stamp the DPoP binding (RFC 7638 thumbprint) before persist/mint
     * (A-SEC-004-9).
     */
    public function withDpopJkt(string $jkt): void
    {
        $this->dpopJkt = $jkt !== '' ? $jkt : null;
    }

    public function dpopJkt(): ?string
    {
        return $this->dpopJkt;
    }

    public function toString(): string
    {
        $custody = SigningCustody::resolve();

        if ($this->sessionSid === null && $this->dpopJkt === null) {
            // Unstamped: byte-exact pre-existing behavior per custody.
            return $custody instanceof FileSigningKeyProvider
                ? parent::toString()
                : $this->buildJwt(
                    $custody->jwtSigner(),
                    InMemory::plainText('unused-remote-custody')
                );
        }

        // Stamped (session and/or DPoP): the custody's signer signs the
        // mirrored layout (+ sid, + cnf); file custody signs with the
        // custody's own private PEM (never league's plumbing), remote
        // custody ignores the sentinel like the unstamped mirror does.
        return $this->buildJwt(
            $custody->jwtSigner(),
            $custody instanceof FileSigningKeyProvider
                ? InMemory::plainText($custody->privateKeyPem())
                : InMemory::plainText('unused-remote-custody')
        );
    }

    /**
     * League 9.4.1 AccessTokenTrait::convertToJWT mirror + `sid` when
     * session-stamped + `cnf.dpop_jkt` when DPoP-stamped (keep in
     * sync). Unstamped mints stay byte-equal to league's claim set —
     * the extra claims appear only on stamped tokens, on both
     * custodies alike.
     */
    private function buildJwt(Signer $signer, Key $key): string
    {
        $builder = (new Builder(new JoseEncoder(), ChainedFormatter::withUnixTimestampDates()))
            ->permittedFor($this->getClient()->getIdentifier())
            ->identifiedBy($this->getIdentifier())
            ->issuedAt(new DateTimeImmutable())
            ->canOnlyBeUsedAfter(new DateTimeImmutable())
            ->expiresAt($this->getExpiryDateTime())
            ->relatedTo($this->getUserIdentifier() ?? $this->getClient()->getIdentifier())
            ->withClaim('scopes', $this->getScopes());

        if ($this->sessionSid !== null) {
            $builder = $builder->withClaim('sid', $this->sessionSid);
        }

        if ($this->dpopJkt !== null) {
            // RFC 9449 §4.3: the confirmation claim binds the token to
            // the proof key — the resource server refuses it beside any
            // other key (AuthenticateAny).
            $builder = $builder->withClaim('cnf', ['dpop_jkt' => $this->dpopJkt]);
        }

        return $builder
            ->getToken($signer, $key)
            ->toString();
    }
}
