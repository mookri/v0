import { newRequestId, ok, err } from '@/application/api-envelope';
import { laravelEnabled, laravelDelete, laravelErrorResponse } from '@/adapters/backend/laravel-client';
import { wiredActor } from '@/server/wired-actor';

// Wired saved delete (CUSTOMER_BACKEND=laravel): DELETE /api/saved/[id]
export async function DELETE(
  req: Request,
  { params }: { params: Promise<{ id: string }> }
) {
  const requestId = newRequestId();
  if (!laravelEnabled()) {
    return Response.json(err('UNAUTHENTICATED', 'Sign in to continue.', requestId), { status: 401 });
  }

  const { id } = await params;

  const actor = wiredActor(req, requestId);
  if (actor instanceof Response) return actor;

  try {
    await laravelDelete(actor.token, `/customers/saved/${id}`);
    return Response.json(ok({ deleted: true }, requestId));
  } catch (e) {
    return laravelErrorResponse(e, 'Failed to delete saved item.', requestId);
  }
}