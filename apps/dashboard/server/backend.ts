/**
 * Dashboard backend seam — direct mode only (ADR-0020).
 *
 * Mirrors customer's laravelEnabled/laravelPost posture: the backend
 * is a deployment choice behind DASHBOARD_API_BASE, never assumed. No
 * dashboard backend is wired in this repo today (the SPA's former
 * Vite dev proxy pointed at the customer app, whose login ceremony
 * never matched this app's {email,password} contract), so an unset
 * base means every direct-ceremony route fails closed as unavailable —
 * a loud 503, not a login form that silently never works.
 *
 * Federation is the live path: DASHBOARD_AUTH_MODE=federated hands
 * login to the issuer entirely (server/federation.ts).
 */

const REQUEST_TIMEOUT_MS = 5000;

export function dashboardApiBase(): string | null {
  const base = (process.env.DASHBOARD_API_BASE ?? '').replace(/\/+$/, '');
  return base === '' ? null : base;
}

export class DashboardBackendUnavailableError extends Error {
  constructor() {
    super('Dashboard backend is not configured for this deployment.');
    this.name = 'DashboardBackendUnavailableError';
  }
}

/**
 * Proxy one request to the configured backend. Returns the parsed
 * upstream JSON verbatim — the {token, user} / User shapes the SPA
 * client already speaks are the upstream's contract to honor.
 * Typed failures (never a bare throw into a 500): unreachable or
 * non-2xx upstreams surface as DashboardBackendError with the
 * upstream's message when it has one.
 */
export class DashboardBackendError extends Error {
  constructor(
    message: string,
    readonly status: number,
  ) {
    super(message);
    this.name = 'DashboardBackendError';
  }
}

export async function dashboardFetch<T>(
  path: string,
  init: { method: string; body?: unknown; authorization?: string | null },
): Promise<T> {
  const base = dashboardApiBase();
  if (!base) throw new DashboardBackendUnavailableError();

  const headers: Record<string, string> = { Accept: 'application/json' };
  if (init.body !== undefined) headers['Content-Type'] = 'application/json';
  if (init.authorization) headers['Authorization'] = init.authorization;

  let response: Response;
  try {
    response = await fetch(`${base}${path}`, {
      method: init.method,
      headers,
      body: init.body === undefined ? undefined : JSON.stringify(init.body),
      signal: AbortSignal.timeout(REQUEST_TIMEOUT_MS),
    });
  } catch {
    throw new DashboardBackendError('Dashboard backend unreachable.', 502);
  }

  if (!response.ok) {
    const body = (await response.json().catch(() => null)) as { message?: string } | null;
    throw new DashboardBackendError(body?.message ?? `Dashboard backend responded ${response.status}.`, response.status);
  }

  return (await response.json()) as T;
}

/**
 * Map backend-seam failures onto the SPA client's `{ message }`
 * failure contract. Shared by the direct-mode login and me routes.
 */
export function backendErrorResponse(error: unknown): Response {
  if (error instanceof DashboardBackendUnavailableError) {
    return Response.json({ message: error.message }, { status: 503 });
  }
  if (error instanceof DashboardBackendError) {
    // 4xx carries upstream meaning (bad credentials, validation); 5xx
    // and transport failures collapse to a typed 502.
    const status = error.status >= 400 && error.status < 500 ? error.status : 502;
    return Response.json({ message: error.message }, { status });
  }
  return Response.json({ message: 'Unexpected server error.' }, { status: 500 });
}
