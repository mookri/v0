import { newRequestId, ok, err } from '@/application/api-envelope';
import { laravelEnabled, laravelPost, laravelTenant, laravelErrorResponse } from '@/adapters/backend/laravel-client';
import { wiredActor } from '@/server/wired-actor';

// Wired revoke other sessions (CUSTOMER_BACKEND=laravel): POST /api/sessions/revoke-others
export async function POST(req: Request) {
  const requestId = newRequestId();
  if (!laravelEnabled()) {
    return Response.json(err('UNAUTHENTICATED', 'Sign in to continue.', requestId), { status: 401 });
  }

  const actor = wiredActor(req, requestId);
  if (actor instanceof Response) return actor;

  try {
    const { data } = await laravelPost(actor.token, '/identity/sessions:revoke-others', { tenant_id: laravelTenant() });
    return Response.json(ok(data, requestId));
  } catch (e) {
    return laravelErrorResponse(e, 'Revoke sessions unavailable.', requestId);
  }
}