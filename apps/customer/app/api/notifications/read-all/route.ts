import { newRequestId, ok, err } from '@/application/api-envelope';
import { laravelEnabled, laravelPost, laravelErrorResponse } from '@/adapters/backend/laravel-client';
import { wiredActor } from '@/server/wired-actor';

// Wired mark all notifications read (CUSTOMER_BACKEND=laravel): POST /api/notifications/read-all
export async function POST(req: Request) {
  const requestId = newRequestId();
  if (!laravelEnabled()) {
    return Response.json(err('UNAUTHENTICATED', 'Sign in to continue.', requestId), { status: 401 });
  }

  const actor = wiredActor(req, requestId);
  if (actor instanceof Response) return actor;

  try {
    const { data } = await laravelPost<unknown>(
      actor.token,
      '/customers/notifications:read-all',
      {}
    );
    return Response.json(ok(data, requestId));
  } catch (e) {
    return laravelErrorResponse(e, 'Failed to mark all notifications as read.', requestId);
  }
}