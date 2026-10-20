<?php

namespace App\Platform\Authz\Http\Middleware;

use App\Platform\Api\CodedHttpException;
use App\Platform\Identity\MachinePrincipal;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Machine scope gate (SEC-014 amendment A-SEC-014-2): ownership-gated
 * reads that machines may consume declare `->middleware('scope:<scope>')`.
 * Human principals pass through untouched (their grants govern); machine
 * principals must hold every listed scope exactly (no wildcards, no
 * write-implies-read in v1). Denials are credential errors — 403
 * `insufficient_scope`, unaudited like unauthenticated rejections.
 *
 * Laravel splits middleware parameters on `:`, so multi-segment scopes
 * arrive separately and are re-joined here (same as CheckCapability).
 */
final class CheckScope
{
    public function handle(Request $request, Closure $next, string ...$segments): Response
    {
        $principal = $request->user();

        if (! $principal instanceof MachinePrincipal) {
            return $next($request);
        }

        $required = [implode(':', $segments)];
        $held = $principal->scopes();

        foreach ($required as $scope) {
            if (! in_array($scope, $held, true)) {
                throw new CodedHttpException(
                    403,
                    'insufficient_scope',
                    sprintf("API key lacks required scope '%s'.", $scope)
                );
            }
        }

        return $next($request);
    }
}
