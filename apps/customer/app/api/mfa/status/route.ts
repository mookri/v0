import { newRequestId, ok, err } from '@/application/api-envelope';
import { laravelEnabled, laravelGet, laravelTenant, laravelErrorResponse } from '@/adapters/backend/laravel-client';

// Wired MFA status (CUSTOMER_BACKEND=laravel): GET /api/mfa/status
export async function GET(req: Request) {
  const requestId = newRequestId();
  if (!laravelEnabled()) {
    return Response.json(err('UNAUTHENTICATED', 'Sign in to continue.', requestId), { status: 401 });
  }

  try {
    const token = req.headers.get('Authorization')?.replace('Bearer ', '');
    const tenant = laravelTenant();
    const { data } = await laravelGet(token, '/identity/mfa', { tenant_id: tenant });
    return Response.json(ok(data, requestId));
  } catch (e) {
    return laravelErrorResponse(e, 'MFA status unavailable.', requestId);
  }
}