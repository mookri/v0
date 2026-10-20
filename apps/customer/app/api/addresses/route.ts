import { newRequestId, ok, err } from '@/application/api-envelope';
import { laravelEnabled, laravelGet, laravelPost, laravelErrorResponse } from '@/adapters/backend/laravel-client';
import { wiredActor } from '@/server/wired-actor';

// Addresses (wired only — no mock book exists): caller-scoped CRUD
// straight from the backend. Without the flag every call is 401, like
// all BFF endpoints before their actor resolver lands.
export async function GET(req: Request) {
  const requestId = newRequestId();
  if (!laravelEnabled()) {
    // No mock book exists for addresses: unauthenticated surface only.
    return Response.json(err('UNAUTHENTICATED', 'Sign in to continue.', requestId), { status: 401 });
  }
  const actor = wiredActor(req, requestId);
  if (actor instanceof Response) return actor;
  try {
    const page = await laravelGet<unknown[]>(actor.token, '/customers/addresses', { limit: '25' });
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
      { headers: { 'Cache-Control': 'private, max-age=30' } },
    );
  } catch (e) {
    return laravelErrorResponse(e, 'Addresses are unavailable.', requestId);
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
    const { data: address } = await laravelPost<unknown>(actor.token, '/customers/addresses', body);
    return Response.json(ok(address as Record<string, unknown>, requestId), { status: 201 });
  } catch (e) {
    return laravelErrorResponse(e, 'Address could not be saved.', requestId);
  }
}
