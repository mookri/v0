<?php

namespace App\Platform\Identity\OAuth;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Per-client grant enforcement (SEC-002 §2.3): the token endpoint only
 * serves grants in the client's profile `allowed_grants`. Runs before
 * league validation, so disallowed grants fail without touching codes
 * or secrets. Errors use league's shape ({error, error_description,
 * message}) — this is protocol surface, not the kernel envelope.
 * Clients without a profile are rejected (profiles are mandatory —
 * every client is created with one).
 */
final class CheckOAuthGrant
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->method() !== 'POST') {
            return $next($request);
        }

        $grant = (string) $request->input('grant_type', '');
        $clientId = $this->clientId($request);

        if ($grant === '' || $clientId === '') {
            // No client identifier could be resolved from the request.
            // League authenticates the client itself and rejects the
            // exchange when the credentials are missing or wrong, so
            // there is nothing for the profile gate to protect here.
            // Resolving the client the same way league does (body
            // parameter, HTTP Basic username as fallback) is what stops
            // a `client_secret_basic` caller from skipping the gate.
            return $next($request);
        }

        $profile = OAuthClientProfile::query()->where('client_id', $clientId)->first();

        if ($profile === null) {
            return $this->deny('invalid_client', 'Unknown client.');
        }

        if (! in_array($grant, $profile->allowed_grants ?? [], true)) {
            return $this->deny(
                'unsupported_grant',
                sprintf("Grant '%s' is not allowed for this client.", $grant)
            );
        }

        // A-SEC-004-9 (RFC 9449): require_dpop mandates DPoP-bound
        // tokens. The interactive grants satisfy it in-league (proof
        // validated and bound at mint via EnforcesDpopProof); machine
        // grants have no DPoP binding seam yet, so a mandated client
        // is denied here — fail closed, never silently exempt. The
        // description says which mandate caused the denial (allowed_
        // grants above owns the plainer "not allowed" wording).
        if (! in_array($grant, ['authorization_code', 'refresh_token'], true)
            && (bool) $profile->require_dpop) {
            return $this->deny(
                'unsupported_grant',
                sprintf(
                    "Grant '%s' is not allowed for this client: require_dpop mandates DPoP-bound tokens and machine grants cannot carry a DPoP binding.",
                    $grant
                )
            );
        }

        return $next($request);
    }

    /**
     * Resolve the OAuth client identifier the same way league does: the
     * body parameter wins, the HTTP Basic username is the fallback
     * (RFC 6749 §2.3.1 forbids repeating client_id in the body when
     * Basic is used). Without this the whole per-client profile gate
     * was skipped for `client_secret_basic` callers.
     */
    private function clientId(Request $request): string
    {
        $clientId = trim((string) $request->input('client_id', ''));

        if ($clientId !== '') {
            return $clientId;
        }

        $header = (string) $request->header('Authorization', '');

        if (stripos($header, 'Basic ') !== 0) {
            return '';
        }

        $decoded = base64_decode(substr($header, 6), true);

        if ($decoded === false || ! str_contains($decoded, ':')) {
            return '';
        }

        [$username] = explode(':', $decoded, 2);

        return trim($username);
    }

    private function deny(string $error, string $description): Response
    {
        $status = $error === 'invalid_client' ? 401 : 400;

        return new \Illuminate\Http\JsonResponse([
            'error' => $error,
            'error_description' => $description,
            'message' => $description,
        ], $status);
    }
}
