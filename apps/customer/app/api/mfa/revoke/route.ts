import { newRequestId, ok, err } from '@/application/api-envelope';
import { laravelEnabled, laravelPost, laravelTenant, laravelErrorResponse } from '@/adapters/backend/laravel-client';

// Wired MFA revoke (CUSTOMER_BACKEND=laravel): POST /api/mfa/revoke (requires live TOTP)
export async function POST(req: Request) {
  const requestId = newRequestId();
  if (!laravelEnabled()) {
    return Response.json(err('UNAUTHENTICATED', 'Sign in to continue.', requestId), { status: 401 });
  }

  let body: { code?: string } = {};
  try {
    body = await req.json();
  } catch {
    return Response.json(err('VALIDATION_ERROR', 'Invalid request body.', requestId), { status: 422 });
  }

  if (!body.code) {
    return Response.json(err('VALIDATION_ERROR', 'code is required.', requestId), { status: 422 });
  }

  try {
    const token = req.headers.get('Authorization')?.replace('Bearer ', '');
    const tenant = laravelTenant();
    const { data } = await laravelPost(token, '/identity/mfa/revoke', { ...body, tenant_id: tenant });
    return Response.json(ok(data, requestId));
  } catch (e) {
    return laravelErrorResponse(e, 'MFA revoke unavailable.', requestId);
  }
}