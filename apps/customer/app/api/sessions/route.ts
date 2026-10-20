import { newRequestId, ok, err } from '@/application/api-envelope';
import { laravelEnabled, laravelGet, laravelTenant, laravelErrorResponse } from '@/adapters/backend/laravel-client';
import { wiredActor } from '@/server/wired-actor';

// Wired sessions list (CUSTOMER_BACKEND=laravel): GET /api/sessions
export async function GET(req: Request) {
  const requestId = newRequestId();
  if (!laravelEnabled()) {
    return Response.json(err('UNAUTHENTICATED', 'Sign in to continue.', requestId), { status: 401 });
  }

  const actor = wiredActor(req, requestId);
  if (actor instanceof Response) return actor;

  try {
    const { data } = await laravelGet(actor.token, '/identity/sessions', { tenant_id: laravelTenant() });
    return Response.json(ok(data, requestId));
  } catch (e) {
    return laravelErrorResponse(e, 'Sessions unavailable.', requestId);
  }
}