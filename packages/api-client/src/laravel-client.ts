import { dpopProof as signDpopProof } from './dpop';

export type BackendCodeMap = Record<string, string>;

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
  dependency_unavailable: 'DEPENDENCY_UNAVAILABLE',
  step_up_required: 'FORBIDDEN',
  onboarding_not_found: 'ONBOARDING_NOT_FOUND',
  onboarding_incomplete: 'ONBOARDING_INCOMPLETE',
  onboarding_invalid_transition: 'ONBOARDING_INVALID_TRANSITION',
  otp_rate_limited: 'RATE_LIMITED',
  otp_invalid: 'UNAUTHENTICATED',
  payload_too_large: 'VALIDATION_ERROR',
  unknown_track: 'VALIDATION_ERROR',
  internal_error: 'INTERNAL_ERROR',
};

export function mapBackendCode(code: string, overrides: BackendCodeMap = {}): string {
  return overrides[code] ?? DEFAULT_BACKEND_CODE_MAP[code] ?? 'INTERNAL_ERROR';
}

export interface BackendPage {
  limit?: number;
  has_more?: boolean;
  next_cursor?: string | null;
}

export interface BackendResponse<T> {
  data: T;
  meta: { request_id?: string; page?: BackendPage };
  etag?: string | null;
}

export interface BackendCallOptions {
  query?: Record<string, string>;
  body?: unknown;
  codeMap?: BackendCodeMap;
  extraHeaders?: Record<string, string>;
  apiVersion?: 'v1' | 'v2';
  timeoutMs?: number;
  requestId?: string;
  idempotencyKey?: string;
}

export class BackendApiError extends Error {
  readonly code: string;
  readonly rawCode: string;
  readonly status: number;
  readonly requestId: string;
  readonly retryAfter?: string;
  readonly detail?: string;

  constructor(shape: {
    code: string;
    rawCode?: string;
    status: number;
    requestId: string;
    retryAfter?: string;
    detail?: string;
  }) {
    super(shape.detail || shape.code);
    this.name = 'BackendApiError';
    this.code = shape.code;
    this.rawCode = shape.rawCode ?? shape.code;
    this.status = shape.status;
    this.requestId = shape.requestId;
    this.retryAfter = shape.retryAfter;
    this.detail = shape.detail;
  }
}

export class StepUpRequiredError extends BackendApiError {
  readonly requiredAcr: string;
  readonly challengeUrl?: string;

  constructor(shape: {
    status: number;
    requestId: string;
    requiredAcr?: string;
    challengeUrl?: string;
    retryAfter?: string;
    detail?: string;
  }) {
    super({
      code: 'FORBIDDEN',
      rawCode: 'step_up_required',
      status: shape.status,
      requestId: shape.requestId,
      retryAfter: shape.retryAfter,
      detail: shape.detail ?? 'Step-up authentication required.',
    });
    this.name = 'StepUpRequiredError';
    this.requiredAcr = shape.requiredAcr ?? '2';
    this.challengeUrl = shape.challengeUrl;
  }
}

export interface BackendClientConfig {
  baseUrl: () => string;
  tenantId?: () => string;
  useDpop?: boolean;
  dpopKeyEnvVar?: string;
  defaultTimeoutMs?: number;
  defaultCodeMap?: BackendCodeMap;
  logTiming?: () => boolean;
}

interface RawEnvelope {
  data?: unknown;
  error?: { code?: string; requestId?: string; request_id?: string };
  meta?: { request_id?: string; page?: BackendPage };
  code?: string;
  title?: string;
  detail?: string;
  request_id?: string;
  required_acr?: string;
  challenge_url?: string;
}

export function createBackendClient(config: BackendClientConfig) {
  const call = async <T>(
    token: string | null,
    method: 'GET' | 'POST' | 'PATCH' | 'PUT' | 'DELETE',
    path: string,
    options: BackendCallOptions = {},
  ): Promise<BackendResponse<T>> => {
    const query = options.query ?? {};
    const params = new URLSearchParams(query);
    const qs = params.size > 0 ? `?${params.toString()}` : '';
    const version =
      options.apiVersion ?? (path.startsWith('/v2/') || path.startsWith('v2/') ? 'v2' : 'v1');
    const cleanPath = path.replace(/^\/?v[12]\//, '/');
    const base = config.baseUrl().replace(/\/$/, '');
    const url = `${base}/api/${version}${cleanPath}${qs}`;

    const headers: Record<string, string> = {
      'Content-Type': 'application/json',
      ...(options.extraHeaders ?? {}),
    };
    if (options.idempotencyKey && !headers['Idempotency-Key']) {
      headers['Idempotency-Key'] = options.idempotencyKey;
    }
    if (token) {
      headers['Authorization'] = `Bearer ${token}`;
      if (config.useDpop) {
        headers['DPoP'] = signDpopProof(method, url, token, config.dpopKeyEnvVar);
      }
    }
    const tenant = config.tenantId?.() ?? '';
    if (tenant) {
      headers['X-Tenant-Id'] = tenant;
    }
    if (options.requestId) {
      headers['X-Request-Id'] = options.requestId;
      headers['Navdhya-Request-Id'] = options.requestId;
    }

    const startedAt = performance.now();
    let status = 0;
    let failure = '';
    const mergedMap: BackendCodeMap = {
      ...DEFAULT_BACKEND_CODE_MAP,
      ...(config.defaultCodeMap ?? {}),
      ...(options.codeMap ?? {}),
    };

    try {
      const res = await fetch(url, {
        method,
        headers,
        body: options.body === undefined ? undefined : JSON.stringify(options.body),
        signal: AbortSignal.timeout(options.timeoutMs ?? config.defaultTimeoutMs ?? 8000),
      });
      status = res.status;
      const etag = res.headers.get('etag');
      const contentType = res.headers.get('content-type') ?? '';
      const isProblemJson = contentType.includes('application/problem+json');
      const parsed = (await res.json().catch(() => null)) as RawEnvelope | null;

      if (!res.ok) {
        const rawCode = isProblemJson
          ? (parsed?.code ?? '')
          : (parsed?.error?.code ?? parsed?.code ?? '');
        const detailMsg = isProblemJson ? (parsed?.detail ?? parsed?.title ?? '') : parsed?.detail;
        const reqId =
          parsed?.error?.requestId ??
          parsed?.error?.request_id ??
          parsed?.meta?.request_id ??
          parsed?.request_id ??
          options.requestId ??
          'unknown';
        const retryAfter = res.headers.get('retry-after') ?? undefined;

        if (rawCode === 'step_up_required' || res.headers.get('x-step-up-required') === '1') {
          throw new StepUpRequiredError({
            status: res.status,
            requestId: reqId,
            requiredAcr: parsed?.required_acr ?? '2',
            challengeUrl: parsed?.challenge_url,
            retryAfter,
            detail: detailMsg,
          });
        }

        throw new BackendApiError({
          code: mapBackendCode(rawCode, mergedMap),
          rawCode,
          status: res.status,
          requestId: reqId,
          retryAfter,
          detail: detailMsg,
        });
      }

      return { data: (parsed?.data ?? null) as T, meta: parsed?.meta ?? {}, etag };
    } catch (e) {
      failure = e instanceof Error ? e.constructor.name : 'unknown';
      if ((e as { name?: string } | null)?.name === 'TimeoutError') {
        throw new BackendApiError({
          code: 'DEPENDENCY_UNAVAILABLE',
          rawCode: 'timeout',
          status: 504,
          requestId: options.requestId ?? 'unknown',
        });
      }
      throw e;
    } finally {
      if (config.logTiming?.()) {
        console.log(
          `[upstream] rid=${options.requestId ?? '-'} method=${method} path=${cleanPath} status=${status} ms=${(performance.now() - startedAt).toFixed(1)}${failure ? ` error=${failure}` : ''}`,
        );
      }
    }
  };

  return {
    call,
    get: <T>(token: string | null, path: string, options?: BackendCallOptions) =>
      call<T>(token, 'GET', path, options),
    post: <T>(token: string | null, path: string, body: unknown, options?: BackendCallOptions) =>
      call<T>(token, 'POST', path, { ...options, body }),
    patch: <T>(token: string | null, path: string, body: unknown, options?: BackendCallOptions) =>
      call<T>(token, 'PATCH', path, { ...options, body }),
    put: <T>(token: string | null, path: string, body: unknown, options?: BackendCallOptions) =>
      call<T>(token, 'PUT', path, { ...options, body }),
    del: <T>(token: string | null, path: string, options?: BackendCallOptions) =>
      call<T>(token, 'DELETE', path, options),
  };
}

