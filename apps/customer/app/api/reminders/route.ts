import { newRequestId, ok } from '@/application/api-envelope';
import { laravelEnabled, laravelGet, laravelPost, laravelErrorResponse } from '@/adapters/backend/laravel-client';
import { wiredActor } from '@/server/wired-actor';

export async function GET(req: Request) {
  const requestId = newRequestId();
  if (!laravelEnabled()) {
    return Response.json(ok([], requestId));
  }

  const actor = wiredActor(req, requestId);
  if (actor instanceof Response) return actor;

  try {
    const { data } = await laravelGet<unknown>(actor.token, '/customers/reminders');
    return Response.json(ok(data, requestId));
  } catch (e) {
    return laravelErrorResponse(e, 'Failed to fetch ritual reminders.', requestId);
  }
}

export async function POST(req: Request) {
  const requestId = newRequestId();
  if (!laravelEnabled()) {
    return Response.json(ok({ id: 'mock_rem_1' }, requestId), { status: 201 });
  }

  const actor = wiredActor(req, requestId);
  if (actor instanceof Response) return actor;

  const body = await req.json();
  try {
    const { data } = await laravelPost<unknown>(actor.token, '/customers/reminders', body);
    return Response.json(ok(data, requestId), { status: 201 });
  } catch (e) {
    return laravelErrorResponse(e, 'Failed to save reminder.', requestId);
  }
}

