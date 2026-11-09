import { newRequestId, ok } from '@/application/api-envelope';
import { laravelEnabled, laravelPost, laravelErrorResponse } from '@/adapters/backend/laravel-client';
import { wiredActor } from '@/server/wired-actor';

export async function POST(req: Request) {
  const requestId = newRequestId();

  if (!laravelEnabled()) {
    return Response.json(ok({ status: 'accepted', message: 'Mock erasure requested.' }, requestId));
  }

  const actor = wiredActor(req, requestId);
  if (actor instanceof Response) return actor;

  let body = {};
  try {
    body = await req.json();
  } catch {
    // Body is optional
  }

  try {
    const { data } = await laravelPost<unknown>(actor.token, '/customers/me/erasure', body);
    return Response.json(ok(data, requestId));
  } catch (e) {
    return laravelErrorResponse(e, 'Account erasure failed.', requestId);
  }
}

