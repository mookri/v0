import { newRequestId, ok } from '@/application/api-envelope';
import { laravelEnabled, laravelGet, laravelPatch, laravelErrorResponse } from '@/adapters/backend/laravel-client';
import { wiredActor } from '@/server/wired-actor';

export async function GET(req: Request) {
  const requestId = newRequestId();
  if (!laravelEnabled()) {
    return Response.json(ok([], requestId));
  }

  const actor = wiredActor(req, requestId);
  if (actor instanceof Response) return actor;

  try {
    const { data } = await laravelGet<unknown>(actor.token, '/customers/preferences');
    return Response.json(ok(data, requestId));
  } catch (e) {
    return laravelErrorResponse(e, 'Failed to fetch preferences.', requestId);
  }
}

export async function PATCH(req: Request) {
  const requestId = newRequestId();
  if (!laravelEnabled()) {
    return Response.json(ok({ status: 'updated' }, requestId));
  }

  const actor = wiredActor(req, requestId);
  if (actor instanceof Response) return actor;

  const body = await req.json();
  try {
    const { data } = await laravelPatch<unknown>(actor.token, '/customers/preferences', body);
    return Response.json(ok(data, requestId));
  } catch (e) {
    return laravelErrorResponse(e, 'Failed to update preferences.', requestId);
  }
}

