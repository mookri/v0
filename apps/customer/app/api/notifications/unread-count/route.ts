import { newRequestId, ok, err } from '@/application/api-envelope';
import { anonymousContext } from '@/application/request-context';
import { laravelEnabled, laravelGet, laravelErrorResponse } from '@/adapters/backend/laravel-client';
import { wiredActor } from '@/server/wired-actor';

export async function GET(req: Request) {
  const requestId = newRequestId();
  if (laravelEnabled()) {
    const actor = wiredActor(req, requestId);
    if (actor instanceof Response) return actor;
    try {
      const { data } = await laravelGet<{ count: number }>(actor.token, '/customers/notifications/unread-count');
      return Response.json(
        ok(data, requestId),
        { headers: { 'Cache-Control': 'private, max-age=15' } },
      );
    } catch (e) {
      return laravelErrorResponse(e, 'Unread count unavailable.', requestId);
    }
  }
  const ctx = anonymousContext('customer.notifications.unread-count', requestId);
  if (!ctx.actor) return Response.json(err('UNAUTHENTICATED', 'Sign in to continue.', requestId), { status: 401 });
  return Response.json(ok({ object: 'notification_unread_count', count: 0 }, requestId), {
    headers: { 'Cache-Control': 'private, max-age=15' },
  });
}