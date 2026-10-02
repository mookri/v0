import { newRequestId, ok, err } from '@/application/api-envelope';
import { laravelEnabled, laravelPost, laravelErrorResponse } from '@/adapters/backend/laravel-client';
import { wiredActor } from '@/server/wired-actor';

export async function POST(req: Request, { params }: { params: Promise<{ id: string }> }) {
  const requestId = newRequestId();
  if (!laravelEnabled()) {
    return Response.json(err('UNAUTHENTICATED', 'Sign in to continue.', requestId), { status: 401 });
  }
  const actor = wiredActor(req, requestId);
  if (actor instanceof Response) return actor;
  try {
    const { data } = await laravelPost<unknown>(
      actor.token,
      `/customers/addresses/${encodeURIComponent((await params).id)}:default`,
      {},
    );
    return Response.json(ok(data as Record<string, unknown>, requestId));
  } catch (e) {
    return laravelErrorResponse(e, 'Address is unavailable.', requestId);
  }
}
