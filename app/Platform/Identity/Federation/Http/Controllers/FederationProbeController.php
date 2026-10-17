<?php

namespace App\Platform\Identity\Federation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Federation surface liveness + issuer identity probe (ADR-0020). The
 * only route mounted on the auth subdomain in Phase 0 — it proves the
 * host gate routes correctly and publishes the canonical issuer so BFF
 * clients can pin `iss` verification without hardcoding. Non-secret by
 * construction: issuer URL and configured policy bounds only.
 */
final class FederationProbeController
{
    public function __invoke(Request $request): JsonResponse
    {
        return new JsonResponse([
            'status' => 'ok',
            'issuer' => (string) config('federation.issuer', ''),
            'domain' => (string) config('federation.domain', ''),
            'mode' => (string) config('federation.mode', 'direct'),
            'runtime' => (string) config('federation.runtime', 'monolith'),
            'revocation_status_ttl_seconds' => (int) config('federation.revocation_status_ttl_seconds', 30),
            'refresh_retry_window_seconds' => (int) config('federation.refresh_retry_window_seconds', 60),
            'access_token_ttl_seconds' => (int) config('federation.access_token_ttl_seconds', 900),
        ], 200, ['Cache-Control' => 'no-store']);
    }
}
