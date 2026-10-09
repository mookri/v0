import { TIMEOUT_MS, type TimeoutClass } from '@/application/api-policy';
import { CustomerApiError } from '@/adapters/backend/customer-api-client';
import { err, type ApiErrorCode } from '@/application/api-envelope';

// Server-side Laravel client (F2): BFF route handlers call
// api.navdhya.com through here when CUSTOMER_BACKEND=laravel.
// Mocks remain the default — no flag, no backend calls, no rewrites.
//
// Envelope translation (parity-customers.md): kernel `{data, meta}`
// unwraps to `data`; backend snake codes map per call site (the default
// map covers auth-shaped errors; bookings passes its own overrides).
export function laravelEnabled(): boolean {
  return process.env.CUSTOMER_BACKEND === 'laravel' && laravelBase() !== '';
}

export function laravelBase(): string {
  return (process.env.NAVDHYA_API_BASE ?? '').replace(/\/$/, '');
}

export function laravelTenant(): string {
  // Drill/staging tenant. Per-request tenant resolution lands with the
  // web→customer handoff carrying tenant context.
  return process.env.CUSTOMER_TENANT_ID ?? '';
}

export type BackendCodeMap = Record<string, ApiErrorCode>;

export const DEFAULT_BACKEND_CODE_MAP: BackendCodeMap = {
  unauthenticated: 'UNAUTHENTICATED',
  membership_inactive: 'UNAUTHENTICATED',
  tenant_suspended: 'FORBIDDEN',
  permission_missing: 'FORBIDDEN',
  authorization_denied: 'FORBIDDEN',
  validation_failed: 'VALIDATION_ERROR',
  not_found: 'NOT_FOUND',
  conflict: 'CONFLICT',
  version_conflict: 'STALE_RESOURCE',
  state_forbidden: 'CONFLICT',
  rate_limited: 'RATE_LIMITED',
  service_unavailable: 'DEPENDENCY_UNAVAILABLE',
  // RequiresAcr denial (403 step_up_required) — without this the code
  // fell through to INTERNAL_ERROR (mismatch M9).
  step_up_required: 'FORBIDDEN',
  internal_error: 'INTERNAL_ERROR',
};

export function mapBackendCode(code: string, overrides: BackendCodeMap = {}): ApiErrorCode {
  return overrides[code] ?? DEFAULT_BACKEND_CODE_MAP[code] ?? 'INTERNAL_ERROR';
}

interface LaravelEnvelope {
  data?: unknown;
  error?: { code?: string; requestId?: string };
  meta?: { request_id?: string };
}

export interface LaravelPage {
  limit?: number;
  has_more?: boolean;
  next_cursor?: string | null;
}

export interface LaravelResponse<T> {
  data: T;
  meta: { request_id?: string; page?: LaravelPage };
}

export interface LaravelCallOptions {
  /**
   * Per-class timeout (contract / P0 plan): OTP issue = 'fast' (2s),
   * login/verify = 'normal' (8s). Auth POSTs are never retried by this
   * client — a retry could double-send an SMS or re-attempt redemption.
   */
  timeout?: TimeoutClass;
  /** Correlation id forwarded as `X-Request-Id` (contract §8) and as
   * `Navdhya-Request-Id` — the header the backend's AttachRequestId
   * actually reads, so BFF and Laravel logs share one `rid`. */
  requestId?: string;
}

async function callLaravel<T>(
  token: string | null,
  method: 'GET' | 'POST' | 'PATCH' | 'DELETE',
  path: string,
  query: Record<string, string> = {},
  body: unknown = undefined,
  codeMap: BackendCodeMap = {},
  options: LaravelCallOptions = {},
): Promise<LaravelResponse<T>> {
  const params = new URLSearchParams(query);
  const qs = params.size > 0 ? `?${params.toString()}` : '';
  const headers: Record<string, string> = { 'Content-Type': 'application/json' };
  if (token) headers['Authorization'] = `Bearer ${token}`;
  const tenant = laravelTenant();
  if (tenant) headers['X-Tenant-Id'] = tenant;
  if (options.requestId) {
    headers['X-Request-Id'] = options.requestId; // contract §8
    // The backend's AttachRequestId reads ONLY `Navdhya-Request-Id` —
    // without it the server minted its own id and the two logs could
    // never be joined. Same value, both headers: strict backend charset
    // `A-Za-z0-9._-` (≤64) accepts `req_<uuid>`.
    headers['Navdhya-Request-Id'] = options.requestId;
  }

  // PERF_TIMING (drill-only): one [upstream] line per call — method,
  // path, status, duration. Never the token, body or query string.
  const startedAt = performance.now();
  let status = 0;
  let failure = '';
  try {
    const res = await fetch(`${laravelBase()}/api/v1${path}${qs}`, {
      method,
      headers,
      body: body === undefined ? undefined : JSON.stringify(body),
      signal: AbortSignal.timeout(TIMEOUT_MS[options.timeout ?? 'normal']),
    });
    status = res.status;
    const parsed = (await res.json().catch(() => null)) as LaravelEnvelope | null;
    if (!res.ok) {
      throw new CustomerApiError({
        code: mapBackendCode(parsed?.error?.code ?? '', codeMap),
        status: res.status,
        requestId: parsed?.error?.requestId ?? parsed?.meta?.request_id ?? 'unknown',
        // Contract §8: a forwarded 429 must keep the backend's
        // Retry-After — dropping it renders an unactionable rate-limit
        // response (the BFF's own throttle already sets the header).
        retryAfter: res.headers.get('retry-after') ?? undefined,
      });
    }
    return { data: (parsed?.data ?? null) as T, meta: parsed?.meta ?? {} };
  } catch (e) {
    failure = e instanceof Error ? e.constructor.name : 'unknown';
    // AbortSignal.timeout rejects with a DOMException named 'TimeoutError',
    // which is not a CustomerApiError — every route's catch let it escape
    // as a bare 500: no envelope, no requestId (contract §8 violation;
    // observed on the cold-start run, report §7). Map it here so all
    // routes render the standard envelope with the BFF's own rid. Other
    // failures keep the documented convention: propagate to framework 500.
    // Property check, not `instanceof Error`: a DOMException from another
    // realm (jest's vm context) fails the instanceof test while behaving
    // identically.
    if ((e as { name?: string } | null)?.name === 'TimeoutError') {
      throw new CustomerApiError({
        code: 'DEPENDENCY_UNAVAILABLE',
        status: 504,
        requestId: options.requestId ?? 'unknown',
      });
    }
    throw e;
  } finally {
    if (process.env.PERF_TIMING === '1') {
      console.log(
        `[upstream] rid=${options.requestId ?? '-'} method=${method} path=${path} status=${status} ms=${(performance.now() - startedAt).toFixed(1)}${failure ? ` error=${failure}` : ''}`,
      );
    }
  }
}

export function laravelGet<T>(
  token: string | null,
  path: string,
  query: Record<string, string> = {},
  codeMap: BackendCodeMap = {},
  options: LaravelCallOptions = {},
): Promise<LaravelResponse<T>> {
  return callLaravel<T>(token, 'GET', path, query, undefined, codeMap, options);
}

export function laravelPost<T>(
  token: string | null,
  path: string,
  body: unknown,
  codeMap: BackendCodeMap = {},
  options: LaravelCallOptions = {},
): Promise<LaravelResponse<T>> {
  return callLaravel<T>(token, 'POST', path, {}, body, codeMap, options);
}

export function laravelDelete<T>(
  token: string | null,
  path: string,
  codeMap: BackendCodeMap = {},
  options: LaravelCallOptions = {},
): Promise<LaravelResponse<T>> {
  return callLaravel<T>(token, 'DELETE', path, {}, undefined, codeMap, options);
}

export function laravelPatch<T>(
  token: string | null,
  path: string,
  body: unknown,
  codeMap: BackendCodeMap = {},
  options: LaravelCallOptions = {},
): Promise<LaravelResponse<T>> {
  return callLaravel<T>(token, 'PATCH', path, {}, body, codeMap, options);
}

// Shared failure mapping for branched handlers: backend-mapped BFF codes
// render as envelopes; anything else propagates to the framework 500
// (BFF convention: no explicit 500 envelopes).
export function laravelErrorResponse(e: unknown, message: string, requestId: string): Response {
  if (e instanceof CustomerApiError) {
    const headers: Record<string, string> = {};
    if (e.retryAfter) headers['Retry-After'] = e.retryAfter;
    return Response.json(err(e.code as ApiErrorCode, message, requestId), {
      status: e.status,
      headers,
    });
  }
  throw e;
}
