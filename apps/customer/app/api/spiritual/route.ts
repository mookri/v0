import { newRequestId, ok } from '@/application/api-envelope';
import { laravelEnabled, laravelGet, laravelPut, laravelErrorResponse } from '@/adapters/backend/laravel-client';
import { wiredActor } from '@/server/wired-actor';

export async function GET(req: Request) {
  const requestId = newRequestId();
  if (!laravelEnabled()) {
    return Response.json(ok({ gotra: 'Kashyapa', tradition: 'AMANTA' }, requestId));
  }

  const actor = wiredActor(req, requestId);
  if (actor instanceof Response) return actor;

  try {
    const { data } = await laravelGet<unknown>(actor.token, '/customers/spiritual');
    return Response.json(ok(data, requestId));
  } catch (e) {
    return laravelErrorResponse(e, 'Failed to fetch spiritual profile.', requestId);
  }
}

export async function PUT(req: Request) {
  const requestId = newRequestId();
  if (!laravelEnabled()) {
    return Response.json(ok({ status: 'updated' }, requestId));
  }

  const actor = wiredActor(req, requestId);
  if (actor instanceof Response) return actor;

  const body = await req.json();
  try {
    const { data } = await laravelPut<unknown>(actor.token, '/customers/spiritual', body);
    return Response.json(ok(data, requestId));
  } catch (e) {
    return laravelErrorResponse(e, 'Failed to update spiritual profile.', requestId);
  }
}

