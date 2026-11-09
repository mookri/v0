import { newRequestId, ok } from '@/application/api-envelope';
import { laravelEnabled, laravelDelete, laravelErrorResponse } from '@/adapters/backend/laravel-client';
import { wiredActor } from '@/server/wired-actor';

export async function DELETE(req: Request, { params }: { params: Promise<{ id: string }> }) {
  const requestId = newRequestId();
  const { id } = await params;

  if (!laravelEnabled()) {
    return Response.json(ok({ status: 'deleted' }, requestId));
  }

  const actor = wiredActor(req, requestId);
  if (actor instanceof Response) return actor;

  try {
    const { data } = await laravelDelete<unknown>(actor.token, `/customers/family/${id}`);
    return Response.json(ok(data, requestId));
  } catch (e) {
    return laravelErrorResponse(e, 'Failed to delete family member.', requestId);
  }
}

