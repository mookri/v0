<?php

namespace App\Platform\Identity\Federation;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * ADR-0020 D1/D6 runtime gate for the federation route scope: the auth
 * surface answers only when a federation domain is configured AND the
 * request arrived on that exact host AND this process serves the
 * federation surface for its runtime role (application runtimes never
 * do — a mispointed vhost or copied env must never expose the issuer
 * surface on a business runtime). Everything else — no domain
 * configured (direct-mode deployments), any other host, any spoofed
 * Host header — fails closed with a plain 404, as if the surface does
 * not exist. The gate reads config per request (not at route
 * registration) so tests and canary flips need no reboot, and so a
 * misconfigured deployment degrades to "no auth subdomain" instead of
 * leaking the issuer surface on the business host.
 */
final class FederationHostGate
{
    /**
     * Exact-host match on the configured federation domain, restricted
     * to the runtimes that serve the federation surface. Shared with
     * the exception-render bounce in bootstrap/app.php so the unified
     * login redirect obeys the same fail-closed rule as the route
     * scope: no domain configured, any other host, or an application
     * runtime is not the auth surface — the bounce never redirects
     * onto a surface this process would 404.
     */
    public static function matches(Request $request): bool
    {
        if (! RuntimeRole::servesFederationSurface()) {
            return false;
        }

        $domain = strtolower((string) config('federation.domain', ''));

        return $domain !== '' && strtolower((string) $request->getHost()) === $domain;
    }

    public function handle(Request $request, Closure $next): Response
    {
        if (! self::matches($request)) {
            abort(404);
        }

        return $next($request);
    }
}
