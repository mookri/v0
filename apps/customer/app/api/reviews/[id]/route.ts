import { newRequestId, ok, err } from '@/application/api-envelope';
import { laravelEnabled, laravelDelete, laravelErrorResponse } from '@/adapters/backend/laravel-client';
import { wiredActor } from '@/server/wired-actor';

export async function DELETE(req: Request, { params }: { params: Promise<{ id: string }> }) {
  const requestId = newRequestId();
  if (!laravelEnabled()) {
    return Response.json(err('UNAUTHENTICATED', 'Sign in to continue.', requestId), { status: 401 });
  }
  const actor = wiredActor(req, requestId);
  if (actor instanceof Response) return actor;
  try {
    const { data } = await laravelDelete<unknown>(
      actor.token,
      `/booking/reviews/${encodeURIComponent((await params).id)}`,
    );
    return Response.json(ok(data as Record<string, unknown>, requestId));
  } catch (e) {
    return laravelErrorResponse(e, 'Review is unavailable.', requestId);
  }
}
