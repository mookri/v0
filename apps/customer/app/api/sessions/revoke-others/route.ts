import { newRequestId, ok, err } from '@/application/api-envelope';
import { laravelEnabled, laravelPost, laravelTenant, laravelErrorResponse } from '@/adapters/backend/laravel-client';

// Wired revoke other sessions (CUSTOMER_BACKEND=laravel): POST /api/sessions/revoke-others
export async function POST(req: Request) {
  const requestId = newRequestId();
  if (!laravelEnabled()) {
    return Response.json(err('UNAUTHENTICATED', 'Sign in to continue.', requestId), { status: 401 });
  }

  try {
    const token = req.headers.get('Authorization')?.replace('Bearer ', '') ?? null;
    const tenant = laravelTenant();
    const { data } = await laravelPost(token, '/identity/sessions:revoke-others', { tenant_id: tenant });
    return Response.json(ok(data, requestId));
  } catch (e) {
    return laravelErrorResponse(e, 'Revoke sessions unavailable.', requestId);
  }
}