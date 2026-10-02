<?php

namespace App\Platform\Authz\Http\Middleware;

use App\Platform\Identity\MachinePrincipal;
use Closure;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

/**
 * Per-tier machine rate limiting (REDIS-001 interim posture): machine
 * principals are capped per key per minute from their key's rate_tier
 * through the driver-agnostic RateLimiter (array/database cache —
 * single-instance ceilings, never pretending to be distributed).
 * Humans pass through untouched; denied credentials never reach here
 * (auth runs first) and scope denials run before quota burn.
 */
final class ThrottleTier
{
    /** @var array<string, int> requests per key per minute */
    private const LIMITS = [
        'standard' => 60,
        'professional' => 300,
        'enterprise' => 1200,
        'partner' => 600,
    ];

    private const WINDOW_SECONDS = 60;

    public function handle(Request $request, Closure $next): Response
    {
        $principal = $request->user();

        if (! $principal instanceof MachinePrincipal) {
            return $next($request);
        }

        $max = self::LIMITS[$principal->rateTier()] ?? self::LIMITS['standard'];
        $key = 'tier:'.$principal->getAuthIdentifier().':'.($request->route()?->getName() ?? $request->path());

        if (RateLimiter::tooManyAttempts($key, $max)) {
            throw new ThrottleRequestsException(
                'Too many requests.',
                null,
                ['Retry-After' => (string) self::WINDOW_SECONDS]
            );
        }

        RateLimiter::hit($key, self::WINDOW_SECONDS);

        return $next($request);
    }
}
