import { laravelBase, laravelEnabled } from '@/adapters/backend/laravel-client';

/**
 * GET /api/readyz — readiness with the backend dependency in view:
 *  - backend not wired (mock mode): ready — the BFF serves its own
 *    surfaces without Laravel;
 *  - backend wired: probe the kernel's /up (bootstrap health route)
 *    with a hard 2s budget, 200 only when it answers OK.
 *
 * Liveness stays independent at /api/healthz so dependency outages
 * never flap the process probe. Per-request by default (cacheComponents
 * forbids segment config exports).
 */
const PROBE_TIMEOUT_MS = 2000;

export async function GET() {
  if (!laravelEnabled()) {
    return Response.json({ status: 'ok', backend: 'mock' }, { status: 200 });
  }

  try {
    const origin = new URL(laravelBase()).origin;
    const res = await fetch(`${origin}/up`, {
      cache: 'no-store',
      signal: AbortSignal.timeout(PROBE_TIMEOUT_MS),
    });

    if (res.ok) {
      return Response.json({ status: 'ok', backend: 'up' }, { status: 200 });
    }

    return Response.json({ status: 'unavailable', backend: `up:${res.status}` }, { status: 503 });
  } catch {
    return Response.json({ status: 'unavailable', backend: 'unreachable' }, { status: 503 });
  }
}
