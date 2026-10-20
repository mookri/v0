<?php

namespace App\Platform\Identity\Http\Middleware;

use App\Platform\Identity\Federation\RuntimeRole;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * D1/D6 legacy-login surface gate: the tenant-scoped direct-mode login
 * routes (customers auth/* + OTP-proofed reactivation, public by
 * design) exist only while the strangler window is open.
 *
 *  - On the issuer runtime they never exist: the authorization server
 *    is the SOLE issuer of identity (D1) — a second live login
 *    surface on it is a second issuer.
 *  - On an application runtime they vanish once federated: the cutover
 *    is committed and apps redirect to the auth subdomain instead.
 *  - The monolith keeps them in every mode (both surfaces coexist on
 *    one process until the cutover lands).
 *
 * Reads config per request (canary flips need no reboot) and fails
 * closed with the same plain 404 as the federation host gate — an
 * oracle about which runtime answered would itself be a leak.
 */
final class LegacyLoginSurfaceGate
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! RuntimeRole::servesLegacyLoginSurface()) {
            abort(404);
        }

        return $next($request);
    }
}
