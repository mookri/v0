import { newRequestId, ok, err } from '@/application/api-envelope';
import { laravelEnabled, laravelPost, laravelTenant, laravelErrorResponse } from '@/adapters/backend/laravel-client';
import { wiredActor } from '@/server/wired-actor';

// Wired MFA enroll (CUSTOMER_BACKEND=laravel): POST /api/mfa/enroll
export async function POST(req: Request) {
  const requestId = newRequestId();
  if (!laravelEnabled()) {
    return Response.json(err('UNAUTHENTICATED', 'Sign in to continue.', requestId), { status: 401 });
  }

  const actor = wiredActor(req, requestId);
  if (actor instanceof Response) return actor;

  try {
    const body = await req.json().catch(() => ({}));
    const { data } = await laravelPost(actor.token, '/identity/mfa/enroll', { ...body, tenant_id: laravelTenant() });
    return Response.json(ok(data, requestId), { status: 201 });
  } catch (e) {
    return laravelErrorResponse(e, 'MFA enroll unavailable.', requestId);
  }
}