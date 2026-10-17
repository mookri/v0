<?php

namespace App\Platform\Identity\OAuth\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Laravel\Passport\Client;
use Symfony\Component\HttpFoundation\Response;

/**
 * OIDC prompt VALIDATION at the authorize endpoint (OIDC Core
 * §3.1.2.1, A-SEC-004-7). Passport's AuthorizationController already
 * implements the semantics of none/login/consent natively (silent
 * approve / login_required / consent_required, forced logout +
 * resume flag, forced consent) — what it does NOT do is validate:
 * it silently collapses `prompt=none <other>` to `none` where the
 * spec says invalid_request MUST, and silently ignores unknown
 * values, turning a typo'd or unsupported interaction request into
 * an ordinary flow (silent semantic loss — the exact failure class
 * max_age enforcement was built to kill at this endpoint).
 *
 * Placement (AppServiceProvider attaches it between
 * RequiresPushedAuthorizationRequest and EnforcesMaxAge): after the
 * PAR bag rewrite (the parameters validated are the ones the client
 * authenticated at PAR time), before any max_age/prompt interaction
 * (a malformed prompt is a rejection, never a re-auth trigger —
 * same doctrine as malformed max_age), inside
 * AppendsIssuerToAuthorizationResponse so its error redirects carry
 * RFC 9207 `iss` on the way out like every other authorize error.
 *
 * Validation applies UNCONDITIONALLY — unlike max_age (gated to
 * openid scope because auth_time is an OIDC-only concept), prompt's
 * semantics need no OIDC claims: a no-interaction or re-auth flag on
 * ANY authorization request is enforceable, and Passport applies its
 * native prompt handling regardless of scope too. Silently honoring
 * prompt=none while refusing to validate it per-scope would be the
 * worst of both.
 *
 * Outcomes, fail closed:
 *
 *  - absent or empty: untouched.
 *  - non-string (array injection, `prompt[]=…`): invalid_request.
 *  - `none` combined with any other value: invalid_request
 *    (OIDC Core's MUST).
 *  - unknown value: invalid_request. The defined set implemented
 *    here is none|login|consent; `select_account` is deliberately
 *    not implemented and is therefore deliberately REJECTED rather
 *    than silently downgraded to a plain flow.
 *
 * Error redirects are built the way league validates them — back to
 * the registered redirect_uri (state restored, `iss` bound by the
 * outer middleware) when client and URI validate exactly, league-
 * shape 400 JSON otherwise: an unverified redirect target never
 * earns a delivery mechanism.
 */
final class EnforcesPrompt
{
    /** The defined prompt values this issuer implements. */
    private const ALLOWED = ['none', 'login', 'consent'];

    public function handle(Request $request, Closure $next): Response
    {
        $all = $request->query->all();

        if (! array_key_exists('prompt', $all)) {
            return $next($request);
        }

        $raw = $all['prompt'];

        // `prompt=` arrives as null: the framework's
        // ConvertEmptyStringsToNull already normalized the empty
        // string — an empty prompt is the absent parameter.
        if ($raw === null || $raw === '') {
            return $next($request);
        }

        if (! is_string($raw)) {
            return $this->errorResponse(
                $request,
                'The prompt parameter must be a single space-delimited string.'
            );
        }

        // Whitespace-only tokens are the absent parameter, exactly as
        // Passport's own parser filters them.
        $tokens = array_values(array_filter(
            preg_split('/\s+/', trim($raw)) ?: [],
            static fn (string $token): bool => $token !== ''
        ));

        if ($tokens === []) {
            return $next($request);
        }

        if (count($tokens) > 1 && in_array('none', $tokens, true)) {
            return $this->errorResponse(
                $request,
                'The none prompt value must not be combined with any other value.'
            );
        }

        $unknown = array_values(array_diff($tokens, self::ALLOWED));

        if ($unknown !== []) {
            return $this->errorResponse(
                $request,
                'Unsupported prompt value(s): '.implode(', ', $unknown).'.'
            );
        }

        return $next($request);
    }

    /**
     * A protocol error for this request: back to the RP's REGISTERED
     * redirect_uri (state restored, iss bound by the outer middleware)
     * when — and only when — client and URI validate exactly the way
     * league validates them; league-shape 400 JSON otherwise. A bag
     * that fails validation never earns a redirect.
     */
    private function errorResponse(Request $request, string $description): Response
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
                'error' => 'invalid_request',
                'error_description' => $description,
            ], 400);
        }

        $params = [
            'error' => 'invalid_request',
            'error_description' => $description,
        ];

        if (is_string($state) && $state !== '') {
            $params['state'] = $state;
        }

        $separator = str_contains($redirectUri, '?') ? '&' : '?';

        return redirect()->away($redirectUri.$separator.http_build_query($params));
    }
}
