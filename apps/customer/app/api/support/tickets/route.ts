import { newRequestId, ok, err } from '@/application/api-envelope';
import { laravelEnabled, laravelGet, laravelPost, laravelErrorResponse } from '@/adapters/backend/laravel-client';
import { wiredActor } from '@/server/wired-actor';

// Support tickets (wired only): open/list/detail/reply/close straight
// from the backend. Without the flag the surface is unauthenticated-only.
export async function GET(req: Request) {
  const requestId = newRequestId();
  if (!laravelEnabled()) {
    return Response.json(err('UNAUTHENTICATED', 'Sign in to continue.', requestId), { status: 401 });
  }
  const actor = wiredActor(req, requestId);
  if (actor instanceof Response) return actor;
  try {
    const page = await laravelGet<unknown[]>(actor.token, '/customers/support/tickets', { limit: '25' });
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
    return laravelErrorResponse(e, 'Support tickets are unavailable.', requestId);
  }
}

export async function POST(req: Request) {
  const requestId = newRequestId();
  if (!laravelEnabled()) {
    return Response.json(err('UNAUTHENTICATED', 'Sign in to continue.', requestId), { status: 401 });
  }
  const actor = wiredActor(req, requestId);
  if (actor instanceof Response) return actor;
  let body: Record<string, unknown> = {};
  try {
    body = (await req.json()) as typeof body;
  } catch {
    return Response.json(err('VALIDATION_ERROR', 'Invalid request body.', requestId), { status: 422 });
  }
  try {
    const { data: ticket } = await laravelPost<unknown>(actor.token, '/customers/support/tickets', body);
    return Response.json(ok(ticket as Record<string, unknown>, requestId), { status: 201 });
  } catch (e) {
    return laravelErrorResponse(e, 'Ticket could not be opened.', requestId);
  }
}
