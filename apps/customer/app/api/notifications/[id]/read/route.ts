import { newRequestId, ok, err } from '@/application/api-envelope';
import { laravelEnabled, laravelPost, laravelErrorResponse } from '@/adapters/backend/laravel-client';
import { wiredActor } from '@/server/wired-actor';

// Wired notification mark read (CUSTOMER_BACKEND=laravel): POST /api/notifications/[id]/read
export async function POST(
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
    const { data } = await laravelPost<unknown>(
      actor.token,
      `/customers/notifications/${id}:read`,
      {}
    );
    return Response.json(ok(data, requestId));
  } catch (e) {
    return laravelErrorResponse(e, 'Failed to mark notification as read.', requestId);
  }
}