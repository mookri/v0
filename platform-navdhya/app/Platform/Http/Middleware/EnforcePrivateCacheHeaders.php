<?php

namespace App\Platform\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Ensures authenticated or credential-bearing API responses are never stored
 * in shared or public caches (T-AP03-04).
 */
final class EnforcePrivateCacheHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $sessionCookie = (string) config('session.cookie', 'laravel_session');
        $hasCredential = $request->user() !== null
            || $request->headers->has('Authorization')
            || $request->headers->has('X-Api-Key')
            || $request->cookies->has($sessionCookie);

        if (! $hasCredential) {
            return $response;
        }

        if ($response->headers->hasCacheControlDirective('public')) {
            return $response;
        }

        $response->headers->set('Cache-Control', 'private, no-store');

        $existingVary = array_filter(array_map('trim', explode(',', (string) $response->headers->get('Vary', ''))));
        foreach (['Cookie', 'Authorization'] as $varyHeader) {
            if (! in_array($varyHeader, $existingVary, true)) {
                $existingVary[] = $varyHeader;
            }
        }
        $response->headers->set('Vary', implode(', ', $existingVary));

        return $response;
    }
}

