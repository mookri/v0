import { newRequestId, ok, err } from '@/application/api-envelope';
import { laravelEnabled, laravelGet, laravelErrorResponse } from '@/adapters/backend/laravel-client';
import { wiredActor } from '@/server/wired-actor';

// Activity (wired only): caller-scoped history straight from the backend
// read model. Without the flag the surface is unauthenticated-only.
export async function GET(req: Request) {
  const requestId = newRequestId();
  if (!laravelEnabled()) {
    return Response.json(err('UNAUTHENTICATED', 'Sign in to continue.', requestId), { status: 401 });
  }
  const actor = wiredActor(req, requestId);
  if (actor instanceof Response) return actor;
  try {
    const url = new URL(req.url);
    const query: Record<string, string> = { limit: '25' };
    const kind = url.searchParams.get('kind');
    if (kind) query['kind'] = kind;
    const cursor = url.searchParams.get('cursor');
    if (cursor) query['cursor'] = cursor;
    const page = await laravelGet<unknown[]>(actor.token, '/customers/activity', query);
    return Response.json(
      ok(
        {
          items: page.data ?? [],
          pagination: {
            hasMore: page.meta?.page?.has_more ?? false,
            nextCursor: page.meta?.page?.next_cursor ?? null,
          },
        },
        requestId,
      ),
    );
  } catch (e) {
    return laravelErrorResponse(e, 'Activity is unavailable.', requestId);
  }
}
