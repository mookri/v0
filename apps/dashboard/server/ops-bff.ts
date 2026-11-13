import { NextRequest } from 'next/server';
import { readSessionToken } from '@/server/session';
import {
  dashboardBackendClient,
  dashboardErrorToResponse,
  dashboardLaravelBase,
} from '@/server/laravel-client';

export interface OpsBffSession {
  token: string;
  tenantId: string;
}

export function requireOpsBffSession(request: NextRequest): OpsBffSession | Response {
  const token =
    readSessionToken(request.headers.get('cookie')) ??
    request.headers.get('authorization')?.replace(/^Bearer\s+/i, '').trim() ??
    null;

  if (!token) {
    return Response.json(
      { error: 'unauthenticated', message: 'Operator session required.' },
      { status: 401 },
    );
  }

  const base = dashboardLaravelBase();
  if (!base) {
    return Response.json(
      { error: 'backend_unavailable', message: 'Platform API base URL is not configured.' },
      { status: 503 },
    );
  }

  const tenantId =
    process.env.DASHBOARD_TENANT_ID ??
    request.headers.get('x-tenant-id') ??
    request.nextUrl.searchParams.get('tenant_id') ??
    '';

  return { token, tenantId };
}

export async function proxyOpsGet(
  request: NextRequest,
  laravelPath: string,
  extraQuery?: Record<string, string>,
): Promise<Response> {
  const session = requireOpsBffSession(request);
  if (session instanceof Response) return session;

  const qs = new URLSearchParams({ tenant_id: session.tenantId, ...(extraQuery ?? {}) });
  try {
    const res = await dashboardBackendClient.get<unknown>(
      session.token,
      `${laravelPath}?${qs.toString()}`,
      {
        extraHeaders: session.tenantId ? { 'X-Tenant-Id': session.tenantId } : undefined,
      },
    );
    return Response.json(res, {
      status: 200,
      headers: {
        'Cache-Control': 'private, no-store',
        Vary: 'Cookie, Authorization',
      },
    });
  } catch (e) {
    return dashboardErrorToResponse(e);
  }
}

export async function proxyOpsPost(
  request: NextRequest,
  laravelPath: string,
  body: Record<string, unknown>,
  options?: { idempotencyKey?: string },
): Promise<Response> {
  const session = requireOpsBffSession(request);
  if (session instanceof Response) return session;

  try {
    const res = await dashboardBackendClient.post<unknown>(
      session.token,
      laravelPath,
      { tenant_id: session.tenantId, ...body },
      {
        extraHeaders: session.tenantId ? { 'X-Tenant-Id': session.tenantId } : undefined,
        idempotencyKey: options?.idempotencyKey,
      },
    );
    return Response.json(res, {
      status: 200,
      headers: {
        'Cache-Control': 'private, no-store',
        Vary: 'Cookie, Authorization',
      },
    });
  } catch (e) {
    return dashboardErrorToResponse(e);
  }
}
