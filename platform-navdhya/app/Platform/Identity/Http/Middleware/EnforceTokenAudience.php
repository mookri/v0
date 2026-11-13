<?php

namespace App\Platform\Identity\Http\Middleware;

use App\Platform\Api\CodedHttpException;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Enforces OIDC / access-token audience isolation (`aud` claim vs allowed
 * app-surface audience) so a token minted for one surface (e.g. customer)
 * cannot be replayed on another surface (e.g. ops / dashboard) (T-AP03-03).
 */
final class EnforceTokenAudience
{
    public function handle(Request $request, Closure $next, string ...$allowedAudiences): Response
    {
        if ($allowedAudiences === []) {
            return $next($request);
        }

        $actor = $request->attributes->get('actor');
        if (! is_array($actor)) {
            return $next($request);
        }

        $tokenAud = $actor['aud'] ?? null;
        if (! is_string($tokenAud) || $tokenAud === '') {
            // Non-OIDC test/legacy session without JWT `aud` claim passes unless aud header asserts mismatch.
            $surfaceHeader = trim((string) $request->header('X-App-Audience', ''));
            if ($surfaceHeader !== '' && ! in_array($surfaceHeader, $allowedAudiences, true)) {
                return $this->forbidden($request);
            }

            return $next($request);
        }

        if (! in_array($tokenAud, $allowedAudiences, true)) {
            return $this->forbidden($request);
        }

        return $next($request);
    }

    private function forbidden(Request $request): never
    {
        throw new CodedHttpException(
            403,
            'audience_mismatch',
            'Access token audience is not permitted on this surface.',
        );
    }
}
