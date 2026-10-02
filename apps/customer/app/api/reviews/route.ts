import { newRequestId, ok, err } from '@/application/api-envelope';
import { laravelEnabled, laravelGet, laravelErrorResponse } from '@/adapters/backend/laravel-client';
import { wiredActor } from '@/server/wired-actor';

// Reviews (wired only): author Passthrough to the booking module. Without
// the flag the surface is unauthenticated-only.
export async function GET(req: Request) {
  const requestId = newRequestId();
  if (!laravelEnabled()) {
    return Response.json(err('UNAUTHENTICATED', 'Sign in to continue.', requestId), { status: 401 });
  }
  const actor = wiredActor(req, requestId);
  if (actor instanceof Response) return actor;
  try {
    const page = await laravelGet<unknown[]>(actor.token, '/booking/reviews', { limit: '25' });
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
    return laravelErrorResponse(e, 'Reviews are unavailable.', requestId);
  }
}
