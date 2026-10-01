<?php

namespace App\Platform\Identity\Http\Middleware;

use App\Models\User;
use App\Platform\Identity\ActorTokenVerifier;
use App\Platform\Identity\MachinePrincipal;
use App\Platform\Identity\Models\ApiKey;
use Closure;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Single authentication entry point for API surfaces (replaces stacking
 * `auth` with credential-specific middleware — Laravel priority-sorts
 * `auth` before custom aliases, which silently breaks stacked gates).
 *
 * Precedence: session user → frontend HMAC bearer → machine API key.
 * Denies with the same AuthenticationException as `auth` when nothing
 * verifies, so routes declare exactly one of `auth.any` (any credential)
 * instead of stacking. Unknown key prefixes, revoked/expired keys and
 * unlinked HMAC identities all fail closed with identical 401s.
 */
final class AuthenticateAny
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user() !== null) {
            return $next($request);
        }

        $header = $request->header('Authorization', '');
        if (is_string($header) && str_starts_with($header, 'Bearer ')) {
            $token = substr($header, 7);
            if ($this->authenticateHmac($request, $token) || $this->authenticateApiKey($request, $token)) {
                return $next($request);
            }
        }

        throw new AuthenticationException('Unauthenticated.');
    }

    private function authenticateHmac(Request $request, string $token): bool
    {
        $secret = (string) config('identity.hmac_secret', '');
        $actor = ActorTokenVerifier::verify($secret, $token, gmdate('Y-m-d\TH:i:s.v\Z'));

        if ($actor === null) {
            return false;
        }

        $user = User::query()->where('external_id', $actor['user_id'])->first();
        if ($user === null) {
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
}
