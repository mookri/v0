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
  /** Correlation id forwarded as `X-Request-Id` (contract §8). */
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
  if (options.requestId) headers['X-Request-Id'] = options.requestId;
  const res = await fetch(`${laravelBase()}/api/v1${path}${qs}`, {
    method,
    headers,
    body: body === undefined ? undefined : JSON.stringify(body),
    signal: AbortSignal.timeout(TIMEOUT_MS[options.timeout ?? 'normal']),
  });
  const parsed = (await res.json().catch(() => null)) as LaravelEnvelope | null;
  if (!res.ok) {
    throw new CustomerApiError({
      code: mapBackendCode(parsed?.error?.code ?? '', codeMap),
      status: res.status,
      requestId: parsed?.error?.requestId ?? parsed?.meta?.request_id ?? 'unknown',
    });
  }
  return { data: (parsed?.data ?? null) as T, meta: parsed?.meta ?? {} };
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
    return Response.json(err(e.code as ApiErrorCode, message, requestId), { status: e.status });
  }
  throw e;
}
