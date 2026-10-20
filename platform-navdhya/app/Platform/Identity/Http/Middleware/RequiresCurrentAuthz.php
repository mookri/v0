<?php

namespace App\Platform\Identity\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * D2 critical-operation gate (ADR-0020): privilege changes, security-
 * setting changes and other critical operations may NOT rely on cached
 * revocation status — the decision re-reads authoritative state at the
 * enforcement point. Ordinary calls accept ≤30 s stale status
 * (RevocationStatusCache); this middleware forces a live check for the
 * credential in hand before the operation proceeds.
 *
 * Passport bearer callers: the token row, client and user are re-read
 * live; a revoked token/client or inactive user denies with the stable
 * 401 even if the ordinary path would still accept the cached verdict.
 * Session and HMAC callers already hit live state per request (session
 * store / actor_sessions row) and pass through untouched.
 */
final class RequiresCurrentAuthz
{
    public function handle(Request $request, Closure $next): Response
    {
        $actor = $request->attributes->get('actor');

        if (is_array($actor) && ($actor['via'] ?? null) === 'passport') {
            $jti = (string) ($actor['session_id'] ?? '');

            // Authoritative read: bypass the D2 cache deliberately —
            // this IS the critical-op path.
            $stored = \Laravel\Passport\Token::query()
                ->whereKey($jti)
                ->where('revoked', false)
                ->first();

            $user = Auth::user();

            $clientLive = $stored !== null && \Laravel\Passport\Client::query()
                ->whereKey((string) $stored->getAttribute('client_id'))
                ->where('revoked', false)
                ->exists();

            if ($stored === null
                || ! $clientLive
                || $user === null
                || ! $user->isActive()) {
                auth()->guard()->forgetUser();

                throw new \Illuminate\Auth\AuthenticationException('Unauthenticated.');
            }
        }

        return $next($request);
    }
}
