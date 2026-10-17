<?php

namespace App\Platform\Identity\Http\Middleware;

use App\Models\User;
use App\Platform\Api\CodedHttpException;
use App\Platform\Identity\ActorTokenVerifier;
use App\Platform\Identity\MachinePrincipal;
use App\Platform\Identity\Models\ApiKey;
use App\Platform\Identity\OAuth\Dpop\DpopProof;
use App\Platform\Identity\OAuth\Dpop\DpopProofInvalidException;
use App\Platform\Identity\PassportJwtVerifier;
use Closure;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Single authentication entry point for API surfaces (replaces stacking
 * `auth` with credential-specific middleware — Laravel priority-sorts
 * `auth` before custom aliases, which silently breaks stacked gates).
 *
 * Precedence by shape: session user → Passport JWT (3 segments) →
 * frontend HMAC bearer → machine API key. Denies with the same
 * AuthenticationException as `auth` when nothing verifies, so routes
 * declare exactly one of `auth.any` (any credential) instead of
 * stacking. Unknown key prefixes, revoked/expired keys and unlinked
 * identities all fail closed with identical 401s.
 */
final class AuthenticateAny
{
    public function handle(Request $request, Closure $next): Response
    {
        // Session-shape credential (9c): a resolved session user must
        // ALSO be lifecycle-active — suspension/deactivation kills
        // session rows at the source, but the enforcement point never
        // trusts that alone (a direct status flip, a swept-row race or
        // a non-database session driver must deny too). Non-User
        // principals pass through untouched.
        $sessionUser = $request->user();

        if ($sessionUser !== null && (! $sessionUser instanceof User || $sessionUser->isActive())) {
            return $next($request);
        }

        $header = $request->header('Authorization', '');
        if (is_string($header) && str_starts_with($header, 'Bearer ')) {
            $token = substr($header, 7);

            // JWT-shaped bearers go to Passport verification only (P3b
            // dual-accept); everything else keeps the HMAC/key path.
            if (substr_count($token, '.') === 2) {
                if ($this->authenticatePassport($request, $token)) {
                    return $next($request);
                }
            } elseif ($this->authenticateHmac($request, $token) || $this->authenticateApiKey($request, $token)) {
                return $next($request);
            }
        }

        throw new AuthenticationException('Unauthenticated.');
    }

    /**
     * Passport access-token acceptance (P3b dual-accept window): RS256
     * signature against our public key, expiry enforced, revocation
     * checked under the D2 freshness contract (RevocationStatusCache —
     * ≤30 s stale for ordinary calls, invalidated instantly by local
     * revocations), audience client must exist unrevoked. Tenant
     * authority stays with live membership (ResolveTenantContext) — the
     * token carries no tenant claim yet (custom claims land P4).
     */
    private function authenticatePassport(Request $request, string $token): bool
    {
        $claims = PassportJwtVerifier::verify($token, time());

        if ($claims === null) {
            return false;
        }

        $revocation = app(\App\Platform\Identity\Federation\RevocationStatusCache::class);

        $verdict = $revocation->validToken((string) $claims['jti']);

        if ($verdict === null || $verdict['client_id'] !== $claims['aud']) {
            return false;
        }

        if (! $revocation->clientActive($claims['aud'])) {
            return false;
        }

        // A-SEC-004-9 (RFC 9449 §5): a DPoP-bound access token is only
        // acceptable beside a live proof from ITS key — htm/htu of
        // THIS request, ath over the presented token, single-use jti,
        // and the proof's thumbprint equal to the token's cnf binding.
        // Unbound tokens keep flowing (gradual adoption): the binding
        // on the token decides, never the caller's word. The RFC
        // challenge travels on the denial (WWW-Authenticate, algs).
        $boundJkt = $claims['cnf']['dpop_jkt'] ?? null;

        if (is_string($boundJkt) && $boundJkt !== '') {
            try {
                $presentedJkt = DpopProof::validate(
                    $request->header('DPoP', ''),
                    $request->method(),
                    $request->url(),
                    $token
                );
            } catch (DpopProofInvalidException $e) {
                throw self::dpopDenial($e->getMessage());
            }

            if (! hash_equals($boundJkt, $presentedJkt)) {
                throw self::dpopDenial('The DPoP proof key does not match the access token binding.');
            }
        }

        $user = User::query()->find($claims['sub']);

        if ($user === null || ! $user->isActive()) {
            return false;
        }

        $request->attributes->set('actor', [
            'user_id' => (string) $user->getAuthIdentifier(),
            'session_id' => $claims['jti'],
            'roles' => [],
            'scopes' => $claims['scopes'],
            'via' => 'passport',
        ]);
        auth()->guard()->setUser($user);

        return true;
    }

    private function authenticateHmac(Request $request, string $token): bool
    {
        $secret = (string) config('identity.hmac_secret', '');
        $actor = ActorTokenVerifier::verify($secret, $token, gmdate('Y-m-d\TH:i:s.v\Z'));

        if ($actor === null) {
            return false;
        }

        $user = User::query()->where('external_id', $actor['user_id'])->first();
        if ($user === null || ! $user->isActive()) {
            return false;
        }

        $request->attributes->set('actor', $actor);
        auth()->guard()->setUser($user);

        return true;
    }

    private function authenticateApiKey(Request $request, string $token): bool
    {
        if (! str_starts_with($token, 'sk_') || strlen($token) < 20) {
            return false;
        }

        $prefix = substr($token, 0, 15);
        $key = ApiKey::query()->where('prefix', $prefix)->first();

        if ($key === null || ! $key->isUsable()) {
            return false;
        }

        if (! hash_equals($key->secret_hash, hash('sha256', $token))) {
            return false;
        }

        // System user for machine credentials: a MachinePrincipal carrying
        // the key's tenant + scopes (never a User row impersonation).
        $principal = new MachinePrincipal(
            (string) $key->getKey(),
            $key->tenant_id,
            $key->scopes ?? [],
            $key->rate_tier
        );
        $request->attributes->set('api_key', [
            'id' => (string) $key->getKey(),
            'tenant_id' => $key->tenant_id,
            'scopes' => $key->scopes ?? [],
            'rate_tier' => $key->rate_tier,
        ]);
        $request->setUserResolver(static fn () => $principal);
        auth()->guard()->setUser($principal);

        // Best-effort usage stamp (never blocks auth on write failure).
        try {
            $key->forceFill(['last_used_at' => now()->toDateTimeString()])->save();
        } catch (\Throwable) {
        }

        return true;
    }

    /**
     * RFC 9449 §5 resource denial: 401 with the envelope code
     * `invalid_dpop_proof` and the mandatory WWW-Authenticate DPoP
     * challenge (error + the algorithms this server accepts). Shaped
     * here so every guarded surface (userinfo, /api/v1) answers it
     * identically.
     */
    private static function dpopDenial(string $reason): CodedHttpException
    {
        return new CodedHttpException(
            401,
            'invalid_dpop_proof',
            $reason,
            ['WWW-Authenticate' => sprintf('DPoP error="invalid_dpop_proof", algs="%s"', DpopProof::ALGORITHMS)]
        );
    }
}
