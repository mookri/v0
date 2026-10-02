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
        $clientId = (string) $request->input('client_id', '');

        if ($grant === '' || $clientId === '') {
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

        return $next($request);
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
