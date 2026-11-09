import { newRequestId, ok } from '@/application/api-envelope';
import { laravelEnabled, laravelGet, laravelPost, laravelErrorResponse } from '@/adapters/backend/laravel-client';
import { wiredActor } from '@/server/wired-actor';

export async function GET(req: Request) {
  const requestId = newRequestId();
  if (!laravelEnabled()) {
    return Response.json(ok({ RITUAL_REMINDERS: { state: 'GRANTED' }, ANALYTICS: { state: 'GRANTED' } }, requestId));
  }

  const actor = wiredActor(req, requestId);
  if (actor instanceof Response) return actor;

  try {
    const { data } = await laravelGet<unknown>(actor.token, '/customers/consent');
    return Response.json(ok(data, requestId));
  } catch (e) {
    return laravelErrorResponse(e, 'Failed to fetch consent posture.', requestId);
  }
}

export async function POST(req: Request) {
  const requestId = newRequestId();
  if (!laravelEnabled()) {
    return Response.json(ok({ status: 'updated' }, requestId));
  }

  const actor = wiredActor(req, requestId);
  if (actor instanceof Response) return actor;

  const body = await req.json();
  try {
    const { data } = await laravelPost<unknown>(actor.token, '/customers/consent', body);
    return Response.json(ok(data, requestId));
  } catch (e) {
    return laravelErrorResponse(e, 'Failed to update consent.', requestId);
  }
}

