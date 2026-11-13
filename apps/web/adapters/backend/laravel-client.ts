import {
  BackendApiError,
  createBackendClient,
  DEFAULT_BACKEND_CODE_MAP as SHARED_DEFAULT_CODE_MAP,
  mapBackendCode as sharedMapBackendCode,
  type BackendCodeMap as SharedBackendCodeMap,
  type BackendPage,
  type BackendResponse,
} from '@navdhya/api-client';
import { TIMEOUT_MS, type TimeoutClass } from '@/application/api-policy';
import { CustomerApiError } from '@/adapters/backend/customer-api-client';
import { err, type ApiErrorCode } from '@/application/api-envelope';

export function laravelEnabled(): boolean {
  return process.env.CUSTOMER_BACKEND === 'laravel' && laravelBase() !== '';
}

export function laravelBase(): string {
  return (process.env.NAVDHYA_API_BASE ?? '').replace(/\/$/, '');
}

export function laravelTenant(): string {
  return process.env.CUSTOMER_TENANT_ID ?? '';
}

export type BackendCodeMap = Record<string, ApiErrorCode>;

export const DEFAULT_BACKEND_CODE_MAP: BackendCodeMap =
  SHARED_DEFAULT_CODE_MAP as unknown as BackendCodeMap;

export function mapBackendCode(code: string, overrides: BackendCodeMap = {}): ApiErrorCode {
  return sharedMapBackendCode(code, overrides as SharedBackendCodeMap) as ApiErrorCode;
}

export type LaravelPage = BackendPage;
export type LaravelResponse<T> = BackendResponse<T>;

export interface LaravelCallOptions {
  timeout?: TimeoutClass;
  requestId?: string;
}

const client = createBackendClient({
  baseUrl: laravelBase,
  tenantId: laravelTenant,
  useDpop: false,
  defaultTimeoutMs: TIMEOUT_MS.normal,
  logTiming: () => process.env.PERF_TIMING === '1',
});

async function callLaravel<T>(
  token: string | null,
  method: 'GET' | 'POST' | 'PATCH' | 'DELETE',
  path: string,
  query: Record<string, string> = {},
  body: unknown = undefined,
  codeMap: BackendCodeMap = {},
  options: LaravelCallOptions = {},
): Promise<LaravelResponse<T>> {
  try {
    return await client.call<T>(token, method, path, {
      query,
      body,
      codeMap: codeMap as SharedBackendCodeMap,
      timeoutMs: TIMEOUT_MS[options.timeout ?? 'normal'],
      requestId: options.requestId,
      apiVersion: 'v1',
    });
  } catch (e) {
    if (e instanceof BackendApiError) {
      throw new CustomerApiError({
        code: e.code as ApiErrorCode,
        status: e.status,
        requestId: e.requestId,
        retryAfter: e.retryAfter,
      });
    }
    throw e;
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
