<?php

namespace App\Modules\Jyotish\Http\Middleware;

use App\Platform\Features\FeatureFlags;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Fail-closed feature flag middleware for Jyotish endpoints (T-JY00-12).
 * When the required feature is disabled, aborts with 404 Not Found.
 */
final class EnsureJyotishFeatureEnabled
{
    public function __construct(
        private readonly FeatureFlags $flags,
    ) {
    }

    public function handle(Request $request, Closure $next, string $feature = FeatureFlags::JYOTISH_ENABLED): Response
    {
        $tenantId = $request->header('X-Tenant-ID') ?: null;

        if (! $this->flags->enabled($feature, tenant: $tenantId, defaultWhenUndefined: true)) {
            throw new NotFoundHttpException("Jyotish feature '{$feature}' is not enabled.");
        }

        return $next($request);
    }
}

