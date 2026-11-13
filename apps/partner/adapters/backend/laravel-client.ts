import {
  BackendApiError,
  createBackendClient,
  DEFAULT_BACKEND_CODE_MAP as SHARED_DEFAULT_CODE_MAP,
  mapBackendCode as sharedMapBackendCode,
  type BackendCodeMap as SharedBackendCodeMap,
  type BackendPage,
  type BackendResponse,
} from '@navdhya/api-client';
import { TIMEOUT_MS } from '@/application/api-policy';
import { err, type PartnerApiErrorCode } from '@/application/api-envelope';

export { TIMEOUT_MS } from '@/application/api-policy';

export function laravelEnabled(): boolean {
  return process.env.PARTNER_BACKEND === 'laravel' && laravelBase() !== '';
}

export function laravelBase(): string {
  return (process.env.NAVDHYA_API_BASE ?? '').replace(/\/$/, '');
}

export function laravelTenant(): string {
  return process.env.PARTNER_TENANT_ID ?? '';
}

export type BackendCodeMap = Record<string, PartnerApiErrorCode>;

export const DEFAULT_BACKEND_CODE_MAP: BackendCodeMap =
  SHARED_DEFAULT_CODE_MAP as unknown as BackendCodeMap;

export function mapBackendCode(code: string, overrides: BackendCodeMap = {}): PartnerApiErrorCode {
  return sharedMapBackendCode(code, overrides as SharedBackendCodeMap) as PartnerApiErrorCode;
}

export type LaravelPage = BackendPage;
export type LaravelResponse<T> = BackendResponse<T>;

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

const client = createBackendClient({
  baseUrl: laravelBase,
  tenantId: laravelTenant,
  useDpop: false,
  defaultTimeoutMs: TIMEOUT_MS.normal,
});

async function callLaravel<T>(
  token: string | null,
  method: 'GET' | 'POST' | 'PATCH' | 'PUT' | 'DELETE',
  path: string,
  query: Record<string, string> = {},
  body: unknown = undefined,
  codeMap: BackendCodeMap = {},
  extraHeaders: Record<string, string> = {},
): Promise<LaravelResponse<T>> {
  try {
    return await client.call<T>(token, method, path, {
      query,
      body,
      codeMap: codeMap as SharedBackendCodeMap,
      extraHeaders,
      apiVersion: 'v1',
    });
  } catch (e) {
    if (e instanceof BackendApiError) {
      throw new PartnerApiError({
        code: e.code as PartnerApiErrorCode,
        status: e.status,
        requestId: e.requestId,
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

export function laravelErrorResponse(e: unknown, message: string, requestId: string): Response {
  if (e instanceof PartnerApiError) {
    return Response.json(err(e.code, message, requestId), { status: e.status });
  }
  throw e;
}

export { err, newRequestId } from '@/application/api-envelope';
