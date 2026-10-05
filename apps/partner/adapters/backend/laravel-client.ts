import { TIMEOUT_MS } from '@/application/api-policy';
import { err, type PartnerApiErrorCode } from '@/application/api-envelope';

// Server-side Laravel client for Partner BFF: calls
// api.navdhya.com through here when PARTNER_BACKEND=laravel.
// Mocks remain the default — no flag, no backend calls, no rewrites.
//
// Envelope translation (parity-partners.md): kernel `{data, meta}`
// unwraps to `data`; backend snake codes map per call site.

// Re-export TIMEOUT_MS for consumers
export { TIMEOUT_MS } from '@/application/api-policy';

export function laravelEnabled(): boolean {
  return process.env.PARTNER_BACKEND === 'laravel' && laravelBase() !== '';
}

export function laravelBase(): string {
  return (process.env.NAVDHYA_API_BASE ?? '').replace(/\/$/, '');
}

export function laravelTenant(): string {
  // Tenant from subdomain/session — set by middleware or route handler
  return process.env.PARTNER_TENANT_ID ?? '';
}

export type BackendCodeMap = Record<string, PartnerApiErrorCode>;

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
  onboarding_not_found: 'ONBOARDING_NOT_FOUND',
  onboarding_incomplete: 'ONBOARDING_INCOMPLETE',
  onboarding_invalid_transition: 'ONBOARDING_INVALID_TRANSITION',
  otp_rate_limited: 'RATE_LIMITED',
  otp_invalid: 'UNAUTHENTICATED',
  payload_too_large: 'VALIDATION_ERROR',
  unknown_track: 'VALIDATION_ERROR',
  step_up_required: 'FORBIDDEN',
};

export function mapBackendCode(code: string, overrides: BackendCodeMap = {}): PartnerApiErrorCode {
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

export class PartnerApiError extends Error {
  code: PartnerApiErrorCode;
  status: number;
  requestId: string;

  constructor(shape: { code: PartnerApiErrorCode; status: number; requestId: string }) {
    super(shape.code);
    this.code = shape.code;
    this.status = shape.status;
    this.requestId = shape.requestId;
  }
}

async function callLaravel<T>(
  token: string | null,
  method: 'GET' | 'POST' | 'PATCH' | 'PUT' | 'DELETE',
  path: string,
  query: Record<string, string> = {},
  body: unknown = undefined,
  codeMap: BackendCodeMap = {},
  extraHeaders: Record<string, string> = {},
): Promise<LaravelResponse<T>> {
  const params = new URLSearchParams(query);
  const qs = params.size > 0 ? `?${params.toString()}` : '';
  const headers: Record<string, string> = { 'Content-Type': 'application/json', ...extraHeaders };
  if (token) headers['Authorization'] = `Bearer ${token}`;
  const tenant = laravelTenant();
  if (tenant) headers['X-Tenant-Id'] = tenant;
  const res = await fetch(`${laravelBase()}/api/v1${path}${qs}`, {
    method,
    headers,
    body: body === undefined ? undefined : JSON.stringify(body),
    signal: AbortSignal.timeout(TIMEOUT_MS.normal),
  });
  const parsed = (await res.json().catch(() => null)) as LaravelEnvelope | null;
  if (!res.ok) {
    throw new PartnerApiError({
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
): Promise<LaravelResponse<T>> {
  return callLaravel<T>(token, 'GET', path, query, undefined, codeMap);
}

export function laravelPost<T>(
  token: string | null,
  path: string,
  body: unknown,
  codeMap: BackendCodeMap = {},
  extraHeaders: Record<string, string> = {},
): Promise<LaravelResponse<T>> {
  return callLaravel<T>(token, 'POST', path, {}, body, codeMap, extraHeaders);
}

export function laravelPut<T>(
  token: string | null,
  path: string,
  body: unknown,
  codeMap: BackendCodeMap = {},
): Promise<LaravelResponse<T>> {
  return callLaravel<T>(token, 'PUT', path, {}, body, codeMap);
}

export function laravelDelete<T>(
  token: string | null,
  path: string,
  codeMap: BackendCodeMap = {},
): Promise<LaravelResponse<T>> {
  return callLaravel<T>(token, 'DELETE', path, {}, undefined, codeMap);
}

// Low-level call for multipart/form-data or other custom needs.
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

// Shared failure mapping for branched handlers: backend-mapped BFF codes
// render as envelopes; anything else propagates to the framework 500
// (BFF convention: no explicit 500 envelopes).
export function laravelErrorResponse(e: unknown, message: string, requestId: string): Response {
  if (e instanceof PartnerApiError) {
    return Response.json(err(e.code, message, requestId), { status: e.status });
  }
  throw e;
}

// Re-export err and newRequestId for convenience
export { err, newRequestId } from '@/application/api-envelope';

export const partnerApi = {
  // Temple directory
  getTemples: (params?: {
    city?: string;
    state?: string;
    amenities?: string[];
    lat?: number;
    lng?: number;
    radius?: number;
    q?: string;
    sort?: string;
    limit?: number;
  }) => {
    const qs = new URLSearchParams();
    if (params?.city) qs.set('city', params.city);
    if (params?.state) qs.set('state', params.state);
    if (params?.amenities) qs.set('amenities', params.amenities.join(','));
    if (params?.lat !== undefined) qs.set('lat', String(params.lat));
    if (params?.lng !== undefined) qs.set('lng', String(params.lng));
    if (params?.radius !== undefined) qs.set('radius', String(params.radius));
    if (params?.q) qs.set('q', params.q);
    if (params?.sort) qs.set('sort', params.sort);
    if (params?.limit !== undefined) qs.set('limit', String(params.limit));
    const suffix = qs.toString() ? `?${qs.toString()}` : '';
    return request(`/api/temples${suffix}`);
  },

  getTemple: (id: string) => request(`/api/temples/${id}`),

  // Temple dashboard
  getTempleDashboardOverview: (templeId: string) =>
    request(`/api/partner/temples/${templeId}/dashboard/overview`),
  getTempleDashboardPujas: (templeId: string, params?: { status?: string; limit?: number }) => {
    const qs = new URLSearchParams();
    if (params?.status) qs.set('status', params.status);
    if (params?.limit !== undefined) qs.set('limit', String(params.limit));
    const suffix = qs.toString() ? `?${qs.toString()}` : '';
    return request(`/api/partner/temples/${templeId}/dashboard/pujas${suffix}`);
  },
  getTempleDashboardBookings: (templeId: string, params?: { status?: string; limit?: number }) => {
    const qs = new URLSearchParams();
    if (params?.status) qs.set('status', params.status);
    if (params?.limit !== undefined) qs.set('limit', String(params.limit));
    const suffix = qs.toString() ? `?${qs.toString()}` : '';
    return request(`/api/partner/temples/${templeId}/dashboard/bookings${suffix}`);
  },
  getTempleDashboardCalendar: (templeId: string, year: number, month: number) =>
    request(`/api/partner/temples/${templeId}/dashboard/calendar`, {
      year: String(year),
      month: String(month),
    }),
  getTempleDashboardAnalytics: (templeId: string, months?: number) =>
    request(`/api/partner/temples/${templeId}/dashboard/analytics`, {
      months: months !== undefined ? String(months) : '12',
    }),
  getTempleDashboardSettings: (templeId: string) =>
    request(`/api/partner/temples/${templeId}/dashboard/settings`),
};