<?php

namespace App\Platform\Identity\OAuth\Http\Middleware;

use App\Platform\Identity\Federation\AuthEventRecorder;
use App\Platform\Identity\OAuth\PushedAuthorizationRequest;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * RFC 9126 enforcement at the browser-facing authorize endpoint:
 * clients whose profile mandates PAR (`require_par`, the N2 cohort)
 * are rejected unless they present a valid single-use `request_uri`;
 * clients that do present one get their stored parameters replayed
 * over the query bag (stored params are authoritative — the query
 * carries only the capability).
 *
 * Consumption is deliberately asymmetric so the federation login
 * bounce survives (ratified 9a decision): a guest GET validates and
 * merges but does NOT consume — the login redirect stashes the full
 * URL (with request_uri) as intended, and the authenticated resume
 * re-enters here and consumes atomically. Wrong client, expiry or a
 * replayed request_uri fail closed with 400 league-shape JSON —
 * never a redirect, because the redirect target would come from an
 * unverified source. Denials hash-chain into auth_events (D5).
 */
final class RequiresPushedAuthorizationRequest
{
    public function __construct(private readonly AuthEventRecorder $audit)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $requestUri = trim((string) $request->query('request_uri', ''));

        if ($requestUri === '') {
            return $this->enforceMandate($request, $next);
        }

        return $this->redeem($request, $next, $requestUri);
    }

    /**
     * No capability presented: a mandate client is rejected outright;
     * opt-in / non-mandate clients flow through untouched.
     *
     * The denial is delivered per RFC 6749 §4.1.2.1: back to the
     * registered redirect_uri as `error=invalid_request` (the outer
     * AppendsIssuer middleware then appends `iss`, D2) — but ONLY when
     * the client is live and the presented redirect_uri exactly
     * matches one it registered. Anything less verified answers a 400
     * league-shape JSON body: an unverified redirect target is an
     * open redirect, never a delivery mechanism.
     */
    private function enforceMandate(Request $request, Closure $next): Response
    {
        $clientId = trim((string) $request->query('client_id', ''));

        if ($clientId === '') {
            return $next($request);
        }

        $profile = \App\Platform\Identity\OAuth\OAuthClientProfile::query()
            ->where('client_id', $clientId)
            ->first();

        if ($profile === null || ! (bool) $profile->require_par) {
            return $next($request);
        }

        $this->deny('par_required', $clientId);

        $description = 'This client must push its authorization request before redirecting a user agent.';

        $client = \Laravel\Passport\Client::query()
            ->whereKey($clientId)
            ->first();

        $redirectUri = trim((string) $request->query('redirect_uri', ''));

        if ($client === null
            || (bool) $client->getAttribute('revoked')
            || $redirectUri === ''
            || ! in_array($redirectUri, array_values((array) $client->getAttribute('redirect_uris')), true)) {
            return self::leagueError('invalid_request', $description);
        }

        $params = [
            'error' => 'invalid_request',
            'error_description' => $description,
        ];

        $state = trim((string) $request->query('state', ''));

        if ($state !== '') {
            $params['state'] = $state;
        }

        return new \Symfony\Component\HttpFoundation\RedirectResponse(
            $redirectUri.(str_contains($redirectUri, '?') ? '&' : '?').http_build_query($params)
        );
    }

    /**
     * A request_uri was presented: resolve, validate, merge stored
     * params, and (only for an authenticated user agent) consume the
     * capability atomically.
     */
    private function redeem(Request $request, Closure $next, string $requestUri): Response
    {
        $extra = array_diff(
            array_keys($request->query->all()),
            ['client_id', 'request_uri']
        );

        if ($extra !== []) {
            $this->deny('par_invalid_request', (string) $request->query('client_id', ''));

            return self::leagueError('invalid_request', 'Only client_id and request_uri may accompany a pushed authorization request.');
        }

        $row = PushedAuthorizationRequest::resolve($requestUri);

        if ($row === null) {
            $this->deny('par_unknown', (string) $request->query('client_id', ''));

            return self::leagueError('invalid_request', 'The request_uri is unknown or malformed.');
        }

        if ($row->isExpired()) {
            $this->deny('par_expired', (string) $row->client_id);

            return self::leagueError('invalid_request', 'The request_uri has expired.');
        }

        // The capability names exactly one client: a mismatched
        // client_id is a steal attempt, not a retry.
        $presentedClientId = trim((string) $request->query('client_id', ''));

        if ($presentedClientId !== '' && ! hash_equals((string) $row->client_id, $presentedClientId)) {
            $this->deny('par_client_mismatch', $presentedClientId);

            return self::leagueError('invalid_request', 'The request_uri was issued to a different client.');
        }

        // Guests do not consume: the federation login bounce stashes
        // this URL as intended and resumes with the same capability.
        if (Auth::guard('web')->check()) {
            if (! $row->claim()) {
                $this->deny('par_replayed', (string) $row->client_id);

                return self::leagueError('invalid_request', 'The request_uri has already been used.');
            }
        }

        // Stored params are authoritative: the query bag is replaced,
        // not merged — league's authorize validation then runs on
        // exactly what the push validated.
        $stored = (array) $row->params;
        $stored['client_id'] = (string) $row->client_id;
        $request->query->replace($stored);

        return $next($request);
    }

    private function deny(string $reason, string $clientId): void
    {
        $this->audit->record([
            'event_type' => 'authorization.par_denied',
            'decision' => 'deny',
            'actor_client_id' => $clientId !== '' ? $clientId : null,
            'grant_type' => 'authorization_code',
            'source_audience' => 'federation',
            'risk_tier' => 'T0',
            'reason_code' => $reason,
        ]);
    }

    private static function leagueError(string $error, string $description): Response
    {
        return new \Illuminate\Http\JsonResponse([
            'error' => $error,
            'error_description' => $description,
            'message' => $description,
        ], 400);
    }
}
