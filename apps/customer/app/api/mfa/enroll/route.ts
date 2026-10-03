import { newRequestId, ok, err } from '@/application/api-envelope';
import { laravelEnabled, laravelPost, laravelTenant, laravelErrorResponse } from '@/adapters/backend/laravel-client';

// Wired MFA enroll (CUSTOMER_BACKEND=laravel): POST /api/mfa/enroll
export async function POST(req: Request) {
  const requestId = newRequestId();
  if (!laravelEnabled()) {
    return Response.json(err('UNAUTHENTICATED', 'Sign in to continue.', requestId), { status: 401 });
  }

  try {
    const token = req.headers.get('Authorization')?.replace('Bearer ', '') ?? null;
    const tenant = laravelTenant();
    const body = await req.json().catch(() => ({}));
    const { data } = await laravelPost(token, '/identity/mfa/enroll', { ...body, tenant_id: tenant });
    return Response.json(ok(data, requestId), { status: 201 });
  } catch (e) {
    return laravelErrorResponse(e, 'MFA enroll unavailable.', requestId);
  }
}