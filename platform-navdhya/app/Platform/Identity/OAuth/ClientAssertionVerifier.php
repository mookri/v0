<?php

namespace App\Platform\Identity\OAuth;

use DateInterval;
use DateTimeImmutable;
use DateTimeInterface;
use Illuminate\Contracts\Cache\Repository;
use Lcobucci\JWT\JwtFacade;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\Signer\Rsa\Sha256;
use Lcobucci\JWT\UnencryptedToken;
use Lcobucci\JWT\Validation\Constraint\IssuedBy;
use Lcobucci\JWT\Validation\Constraint\LooseValidAt;
use Lcobucci\JWT\Validation\Constraint\RelatedTo;
use Lcobucci\JWT\Validation\Constraint\SignedWith;
use Psr\Clock\ClockInterface;

/**
 * RFC 7523 §2 client_assertion verification (ADR-0020 D3): a BFF
 * authenticates to the token endpoint by presenting a short-lived JWT
 * it signed with its own private key. Every check fails closed — a
 * failed check is indistinguishable from "no credential", never a
 * downgrade to the shared secret:
 *
 *  - **Key scoping (blast radius):** the `kid` header resolves keys
 *    ONLY within the client being authenticated, so one BFF's key can
 *    never authenticate another client even if the kid were known
 *    (D3 required test: compromised BFF key ⇒ issuing for a different
 *    client stays impossible).
 *  - **Signature:** fixed RS256 (`SignedWith` pins header `alg`), so
 *    alg=none / HS256-with-public-key confusions die at the algorithm
 *    check. Nothing is parsed from the token before the signature
 *    verifies (kid excepted — it only drives the scoped key lookup).
 *  - **Claims:** iss = sub = the authenticated client; `aud` must name
 *    this deployment's token endpoint (no unregistered audiences);
 *    `exp`/`iat` mandatory with a bounded lifetime
 *    (`federation.client_assertion_max_ttl_seconds`) plus the standard
 *    clock-skew leeway.
 *  - **Replay:** `jti` is single-use for the remainder of its own
 *    validity (cache `add`). Store down ⇒ assertion authentication
 *    unavailable — replay protection is a security check and is never
 *    bypassed (D4 failure matrix: the accelerator is not the
 *    authority).
 *
 * Key administration (register/revoke) is attributable and audited via
 * the auth_events chain; this class only reads.
 */
final class ClientAssertionVerifier
{
    public const ASSERTION_TYPE = 'urn:ietf:params:oauth:client-assertion-type:jwt-bearer';

    /**
     * Injected (not the facade) so an outage can be scoped to this
     * component in tests: a dead store must fail assertion auth closed
     * without taking sessions or throttling down with it.
     */
    public function __construct(private readonly Repository $cache)
    {
    }

    public function verify(string $assertion, string $expectedClientId): bool
    {
        if ($expectedClientId === '') {
            return false;
        }

        $kid = self::headerKid($assertion);

        if ($kid === null) {
            return false;
        }

        // D3 scoping: registered against that client ONLY — the lookup
        // predicate itself is the blast-radius boundary.
        $key = ClientAuthKey::query()
            ->where('client_id', $expectedClientId)
            ->where('kid', $kid)
            ->whereNull('revoked_at')
            ->first();

        if ($key === null) {
            return false;
        }

        $leewaySeconds = max(0, (int) config('federation.clock_skew_seconds', 60));
        $leeway = new DateInterval('PT'.$leewaySeconds.'S');

        try {
            $token = (new JwtFacade())->parse(
                $assertion,
                new SignedWith(new Sha256(), InMemory::plainText((string) $key->public_key)),
                new LooseValidAt(self::clock(), $leeway),
                new IssuedBy($expectedClientId),
                new RelatedTo($expectedClientId),
            );
        } catch (\Throwable) {
            // Bad signature, alg mismatch, expired/nbf, iss/sub
            // mismatch — one fail-closed door for all of them.
            return false;
        }

        return $this->claimsAccepted($token, $expectedClientId, $leewaySeconds);
    }

    private function claimsAccepted(UnencryptedToken $token, string $clientId, int $leewaySeconds): bool
    {
        $claims = $token->claims();

        $exp = self::timestamp($claims->get('exp'));
        $iat = self::timestamp($claims->get('iat'));
        $jti = $claims->get('jti');

        if ($exp === null || $iat === null || ! is_string($jti) || $jti === '') {
            return false;
        }

        $maxTtl = max(1, (int) config('federation.client_assertion_max_ttl_seconds', 300));

        // Bounded lifetime: an assertion is a transport credential, not
        // a session — exp-iat may never exceed the configured maximum,
        // so the replay window a leaked assertion opens is bounded too.
        if ($exp - $iat > $maxTtl + $leewaySeconds) {
            return false;
        }

        if (! $this->audienceAccepted($claims->get('aud'))) {
            return false;
        }

        $now = time();
        $window = max(1, min($exp - $now, $maxTtl));

        try {
            // First use wins for the remainder of validity; a second
            // use (replay) — or a store that cannot answer — fails.
            if (! $this->cache->add(
                'authz:client-assertion-jti:'.$clientId.':'.$jti,
                1,
                $window
            )) {
                return false;
            }
        } catch (\Throwable) {
            return false;
        }

        return true;
    }

    private function audienceAccepted(mixed $aud): bool
    {
        $values = is_array($aud) ? $aud : ($aud === null || $aud === '' ? [] : [$aud]);

        if ($values === []) {
            return false;
        }

        foreach ($this->acceptedAudiences() as $accepted) {
            foreach ($values as $value) {
                if (is_string($value) && hash_equals($accepted, $value)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * The token endpoint, under every origin this deployment answers
     * for (app url + federation issuer when asserted). Nothing else.
     *
     * @return list<string>
     */
    private function acceptedAudiences(): array
    {
        $audiences = [];

        $appUrl = rtrim((string) config('app.url', ''), '/');

        if ($appUrl !== '') {
            $audiences[] = $appUrl.'/oauth/token';
        }

        $issuer = rtrim((string) config('federation.issuer', ''), '/');

        if ($issuer !== '' && ! in_array($issuer.'/oauth/token', $audiences, true)) {
            $audiences[] = $issuer.'/oauth/token';
        }

        return $audiences;
    }

    /**
     * Unverified header read — used ONLY to choose which client-scoped
     * key to verify against; no trust decision happens before the
     * signature check.
     */
    private static function headerKid(string $jwt): ?string
    {
        $segments = explode('.', $jwt);

        if (count($segments) !== 3) {
            return null;
        }

        $headerJson = base64_decode(strtr($segments[0], '-_', '+/'), true);

        if ($headerJson === false) {
            return null;
        }

        $header = json_decode($headerJson, true);
        $kid = is_array($header) ? ($header['kid'] ?? null) : null;

        return is_string($kid) && $kid !== '' && strlen($kid) <= 64 ? $kid : null;
    }

    private static function timestamp(mixed $value): ?int
    {
        if ($value instanceof DateTimeInterface) {
            return $value->getTimestamp();
        }

        if (is_int($value)) {
            return $value;
        }

        return is_numeric($value) ? (int) $value : null;
    }

    /**
     * Real-time clock (lcobucci bundles none): assertions are validated
     * against wall time; tests encode skew in the claims themselves.
     */
    private static function clock(): ClockInterface
    {
        return new class () implements ClockInterface {
            public function now(): DateTimeImmutable
            {
                return new DateTimeImmutable();
            }
        };
    }
}
