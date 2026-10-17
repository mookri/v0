<?php

namespace App\Platform\Identity\OAuth\Http\Middleware;

use App\Platform\Identity\Federation\FederationHostGate;
use Closure;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Laravel\Passport\Client;
use Symfony\Component\HttpFoundation\Response;

/**
 * OIDC max_age enforcement at the authorize endpoint (OIDC Core
 * §3.1.2.1, SEC-004 / A-SEC-004-5): "re-authenticate if the current
 * session's auth_time is older than N seconds" — decided HERE, by the
 * issuer, instead of each RP guessing from the ID token after the
 * fact.
 *
 * Placement (AppServiceProvider attaches it innermost on the Passport
 * authorize route, after RequiresPushedAuthorizationRequest): the
 * query bag has already been rewritten from the server-side PAR
 * payload (so the parameters are the ones the client authenticated at
 * PAR time), and error redirects this middleware builds flow back out
 * through AppendsIssuerToAuthorizationResponse, which binds RFC 9207
 * `iss` to them like every other authorization error.
 *
 * Outcomes, fail closed:
 *
 *  - absent, or a non-openid request: untouched (max_age is an OIDC
 *    authentication parameter; pure OAuth sees it as any unknown
 *    parameter — ignored).
 *  - malformed (not a non-negative integer): invalid_request —
 *    validated-redirect error redirect, league-shape 400 otherwise.
 *  - stale or UNPROVABLE auth_time (no ceremony facts — a session
 *    whose freshness cannot be shown is not fresh): interactive
 *    bounce to the unified ceremony with a bounded oidc.reauth
 *    challenge (the login page's signed-in shortcut is suspended until
 *    a fresh ceremony lands, then resumes via url.intended) — or,
 *    under prompt=none, the login_required error straight back to the
 *    RP, because interaction is forbidden.
 *  - fresh: pass, and any pending re-auth challenge is discharged.
 */
final class EnforcesMaxAge
{
    /**
     * How long a parked re-auth challenge stays valid on the session:
     * long enough to complete a ceremony, short enough that an
     * abandoned challenge stops gating the login page's signed-in
     * shortcut (re-authorizing simply re-arms it).
     */
    private const REAUTH_SESSION_SECONDS = 300;

    public function handle(Request $request, Closure $next): Response
    {
        $bag = $request->query();

        if (! array_key_exists('max_age', $bag)) {
            return $next($request);
        }

        // OIDC-only parameter: no openid scope means no auth_time
        // concept, so there is nothing to enforce (and nothing to
        // reject — unknown parameters pass, as league does it).
        $scope = (string) ($bag['scope'] ?? '');

        if (preg_match('/(?:^|\s)openid(?:\s|$)/', $scope) !== 1) {
            return $next($request);
        }

        $maxAge = $bag['max_age'];

        if (! is_string($maxAge) || preg_match('/^(0|[1-9][0-9]*)$/', $maxAge) !== 1) {
            return $this->errorResponse(
                $request,
                'invalid_request',
                'The max_age parameter must be a non-negative integer.'
            );
        }

        // Saturating (int) cast: a bound beyond PHP_INT_MAX behaves as
        // "any session is fresh", which is what an enormous window
        // means — never a wrap to a small or negative bound.
        $maxAgeSeconds = (int) $maxAge;
        $now = time();

        $session = $request->hasSession() ? $request->session() : null;
        $ceremony = $session?->get('oidc.ceremony');
        $authTime = is_array($ceremony) && isset($ceremony['auth_time']) && is_numeric($ceremony['auth_time'])
            ? (int) $ceremony['auth_time']
            : null;

        // Unprovable freshness fails closed: no ceremony facts means
        // no auth_time, and no auth_time cannot satisfy a max_age
        // (the fallback auth_time = now exists for ID-token minting
        // convenience, never for this decision).
        $age = $authTime !== null ? $now - $authTime : null;
        $skew = (int) config('federation.clock_skew_seconds', 60);
        $fresh = $age !== null && $age <= $maxAgeSeconds && $age >= -$skew;

        if ($fresh) {
            // Discharged: a satisfied requirement must not leave the
            // login page stuck in challenge mode from an earlier bounce.
            $session?->forget('oidc.reauth');

            return $next($request);
        }

        $prompt = (string) ($bag['prompt'] ?? '');
        $prompts = preg_split('/\s+/', trim($prompt)) ?: [];

        if (in_array('none', $prompts, true)) {
            // prompt=none forbids interaction: the stale session is
            // reported to the RP as login_required (OIDC Core §3.1.2.1),
            // never bounced to a login page the RP cannot see.
            return $this->errorResponse(
                $request,
                'login_required',
                'Re-authentication is required (max_age exceeded).'
            );
        }

        // Interactive re-authentication on the federation host: park
        // the bounded challenge, pin the resume URL, and hand the
        // browser to the unified ceremony. Both writes are explicit
        // (the framework's guest() bounce performs the same stash) so
        // the test contract and the runtime can never drift.
        if ($session !== null && FederationHostGate::matches($request)) {
            $session->put('oidc.reauth', ['until' => $now + self::REAUTH_SESSION_SECONDS]);
            $session->put('url.intended', $request->fullUrl());

            return redirect()->to(route('federation.login'));
        }

        // Off the federation host, or sessionless: the standard
        // unauthenticated bounce applies (host-gated in the exception
        // renderer — federation hosts land on the unified ceremony,
        // everything else on the default login).
        throw new AuthenticationException();
    }

    /**
     * A protocol error for this request: back to the RP's REGISTERED
     * redirect_uri (state restored, iss bound by the outer middleware)
     * when — and only when — client and URI validate exactly the way
     * league validates them; league-shape 400 JSON otherwise. A bag
     * that fails validation never earns a redirect.
     */
    private function errorResponse(Request $request, string $error, string $description): Response
    {
        $bag = $request->query();
        $clientId = (string) ($bag['client_id'] ?? '');
        $redirectUri = (string) ($bag['redirect_uri'] ?? '');
        $state = $bag['state'] ?? null;

        $client = $clientId !== '' ? Client::query()->find($clientId) : null;
        $validRedirect = $client !== null
            && ! $client->revoked
            && $redirectUri !== ''
            && in_array($redirectUri, $client->redirect_uris ?? [], true);

        if (! $validRedirect) {
            return new JsonResponse([
                'error' => $error,
                'error_description' => $description,
            ], 400);
        }

        $params = [
            'error' => $error,
            'error_description' => $description,
        ];

        if (is_string($state) && $state !== '') {
            $params['state'] = $state;
        }

        $separator = str_contains($redirectUri, '?') ? '&' : '?';

        return redirect()->away($redirectUri.$separator.http_build_query($params));
    }
}
