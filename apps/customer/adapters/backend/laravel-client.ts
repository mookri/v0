import { TIMEOUT_MS } from '@/application/api-policy';
import { CustomerApiError } from '@/adapters/backend/customer-api-client';
import { err, type ApiErrorCode } from '@/application/api-envelope';
import { dpopProof } from '@/server/dpop';

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
  precondition_required: 'STALE_RESOURCE',
  precondition_failed: 'STALE_RESOURCE',
  quote_stale: 'CONFLICT',
  resource_unavailable: 'CONFLICT',
  state_forbidden: 'CONFLICT',
  invalid_state_transition: 'CONFLICT',
  rate_limited: 'RATE_LIMITED',
  service_unavailable: 'DEPENDENCY_UNAVAILABLE',
  // OTP exchange: the typed 503 — provider creds
  // absent or authority unreachable — must not fall through to
  // INTERNAL_ERROR; the UI needs to say "try again shortly", not
  // "invalid credentials".
  dependency_unavailable: 'DEPENDENCY_UNAVAILABLE',
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
  etag?: string | null;
}

async function callLaravel<T>(
  token: string | null,
  method: 'GET' | 'POST' | 'PATCH' | 'PUT' | 'DELETE',
  path: string,
  query: Record<string, string> = {},
  body: unknown = undefined,
  codeMap: BackendCodeMap = {},
  extraHeaders: Record<string, string> = {},
  apiVersion?: 'v1' | 'v2',
): Promise<LaravelResponse<T>> {
  const params = new URLSearchParams(query);
  const qs = params.size > 0 ? `?${params.toString()}` : '';
  const version = apiVersion ?? (path.startsWith('/v2/') || path.startsWith('v2/') ? 'v2' : 'v1');
  const cleanPath = path.replace(/^\/?v[12]\//, '/');
  const url = `${laravelBase()}/api/${version}${cleanPath}${qs}`;
  const headers: Record<string, string> = { 'Content-Type': 'application/json', ...extraHeaders };
  if (token) {
    headers['Authorization'] = `Bearer ${token}`;
    // A-SEC-004-9 (RFC 9449): every upstream call carrying a bearer
    // token proves possession of the binding key — `ath` over this
    // token, htm/htu of this request. Unbound tokens ignore the extra
    // header; bound tokens are refused without it, and the proof key
    // is the same one that bound the token at the exchange, so a
    // session's token and its proofs always match by construction.
    headers['DPoP'] = dpopProof(method, url, token);
  }
  const tenant = laravelTenant();
  if (tenant) headers['X-Tenant-Id'] = tenant;
  const res = await fetch(url, {
    method,
    headers,
    body: body === undefined ? undefined : JSON.stringify(body),
    signal: AbortSignal.timeout(TIMEOUT_MS.normal),
  });
  const etag = res.headers.get('etag');
  const contentType = res.headers.get('content-type') ?? '';
  const isProblemJson = contentType.includes('application/problem+json');
  const parsed = (await res.json().catch(() => null)) as (LaravelEnvelope & { code?: string; title?: string; detail?: string }) | null;
  if (!res.ok) {
    const rawCode = isProblemJson ? (parsed?.code ?? '') : (parsed?.error?.code ?? '');
    const detailMsg = isProblemJson ? (parsed?.detail ?? parsed?.title ?? '') : undefined;
    throw new CustomerApiError({
      code: mapBackendCode(rawCode, codeMap),
      status: res.status,
      requestId: parsed?.error?.requestId ?? parsed?.meta?.request_id ?? (parsed as any)?.request_id ?? 'unknown',
      // 429s carry actionable timing (backend throttle, MFA lock) —
      // the web console's client forwards it; parity here (S50).
      retryAfter: res.headers.get('retry-after') ?? undefined,
      detail: detailMsg,
    });
  }
  return { data: (parsed?.data ?? null) as T, meta: parsed?.meta ?? {}, etag };
}

export function laravelGet<T>(
  token: string | null,
  path: string,
  query: Record<string, string> = {},
  codeMap: BackendCodeMap = {},
  extraHeaders: Record<string, string> = {},
  apiVersion?: 'v1' | 'v2',
): Promise<LaravelResponse<T>> {
  return callLaravel<T>(token, 'GET', path, query, undefined, codeMap, extraHeaders, apiVersion);
}

export function laravelPost<T>(
  token: string | null,
  path: string,
  body: unknown,
  codeMap: BackendCodeMap = {},
  extraHeaders: Record<string, string> = {},
  apiVersion?: 'v1' | 'v2',
): Promise<LaravelResponse<T>> {
  return callLaravel<T>(token, 'POST', path, {}, body, codeMap, extraHeaders, apiVersion);
}

export function laravelDelete<T>(
  token: string | null,
  path: string,
  codeMap: BackendCodeMap = {},
  extraHeaders: Record<string, string> = {},
  apiVersion?: 'v1' | 'v2',
): Promise<LaravelResponse<T>> {
  return callLaravel<T>(token, 'DELETE', path, {}, undefined, codeMap, extraHeaders, apiVersion);
}

export function laravelPatch<T>(
  token: string | null,
  path: string,
  body: unknown,
  codeMap: BackendCodeMap = {},
  extraHeaders: Record<string, string> = {},
  apiVersion?: 'v1' | 'v2',
): Promise<LaravelResponse<T>> {
  return callLaravel<T>(token, 'PATCH', path, {}, body, codeMap, extraHeaders, apiVersion);
}

export function laravelPut<T>(
  token: string | null,
  path: string,
  body: unknown,
  codeMap: BackendCodeMap = {},
  extraHeaders: Record<string, string> = {},
  apiVersion?: 'v1' | 'v2',
): Promise<LaravelResponse<T>> {
  return callLaravel<T>(token, 'PUT', path, {}, body, codeMap, extraHeaders, apiVersion);
}

// Shared failure mapping for branched handlers: backend-mapped BFF codes
// render as envelopes; anything else propagates to the framework 500
// (BFF convention: no explicit 500 envelopes).
export function laravelErrorResponse(e: unknown, message: string, requestId: string): Response {
  if (e instanceof CustomerApiError) {
    // Forward the upstream Retry-After on 429s (backend throttle, MFA
    // lock) so screens can surface an honest countdown — parity with
    // the web console's client (S50).
    const headers: Record<string, string> = {};
    if (e.retryAfter) headers['Retry-After'] = e.retryAfter;
    return Response.json(err(e.code as ApiErrorCode, e.detail || message, requestId), {
      status: e.status,
      headers,
    });
  }
  throw e;
}

// Low-level call for multipart/form-data or other custom needs.
// Prefer laravelGet/Post/Patch/Put/Delete for JSON APIs.
export async function laravelCall<T>(
  token: string | null,
  method: 'GET' | 'POST' | 'PATCH' | 'PUT' | 'DELETE',
  path: string,
  query: Record<string, string> = {},
  body: unknown = undefined,
  codeMap: BackendCodeMap = {},
  extraHeaders: Record<string, string> = {},
): Promise<LaravelResponse<T>> {
  return callLaravel<T>(token, method, path, query, body, codeMap, extraHeaders);
}
