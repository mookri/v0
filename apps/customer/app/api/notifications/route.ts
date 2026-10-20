import { newRequestId, ok } from '@/application/api-envelope';
import { laravelEnabled, laravelGet, laravelErrorResponse } from '@/adapters/backend/laravel-client';
import { wiredActor } from '@/server/wired-actor';

export async function GET(req: Request) {
  const requestId = newRequestId();
  if (laravelEnabled()) {
    const actor = wiredActor(req, requestId);
    if (actor instanceof Response) return actor;
    try {
      const page = await laravelGet<unknown[]>(actor.token, '/customers/notifications', { limit: '25' });
      // Backend rows use `body` / `read_at`; the customer view model uses
      // `message` / `read`. Map at the BFF boundary so pages and clients
      // share one shape.
      const items = (page.data ?? []).map((raw) => {
        const row = raw as Record<string, unknown>;
        return {
          id: String(row.id ?? ''),
          title: String(row.title ?? ''),
          message: String(row.body ?? row.message ?? ''),
          createdAt: String(row.created_at ?? ''),
          read: row.read_at !== null && row.read_at !== undefined ? true : Boolean(row.read),
          kind: row.kind,
          category: row.category,
        };
      });
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
  return Response.json(ok({ items: [], pagination: { hasMore: false, nextCursor: null } }, requestId), {
    headers: { 'Cache-Control': 'private, max-age=15' },
  });
}
