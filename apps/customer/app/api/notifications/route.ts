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
      const page = await laravelGet<unknown[]>(actor.token, '/customers/notifications', { limit: '25' });
      const items = page.data ?? [];
      return Response.json(
        ok(
          {
            items,
            pagination: {
              hasMore: page.meta?.page?.has_more ?? false,
              nextCursor: page.meta?.page?.next_cursor ?? null,
            },
          },
          requestId,
        ),
        { headers: { 'Cache-Control': 'private, max-age=15' } },
      );
    } catch (e) {
      return laravelErrorResponse(e, 'Notifications are unavailable.', requestId);
    }
  }
  const ctx = anonymousContext('customer.notifications', requestId);
  if (!ctx.actor) return Response.json(err('UNAUTHENTICATED', 'Sign in to continue.', requestId), { status: 401 });
  return Response.json(ok({ items: [], pagination: { hasMore: false, nextCursor: null } }, requestId), {
    headers: { 'Cache-Control': 'private, max-age=15' },
  });
}
