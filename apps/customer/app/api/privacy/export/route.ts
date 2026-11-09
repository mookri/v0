import { newRequestId, ok } from '@/application/api-envelope';
import { laravelEnabled, laravelGet, laravelErrorResponse } from '@/adapters/backend/laravel-client';
import { wiredActor } from '@/server/wired-actor';

export async function GET(req: Request) {
  const requestId = newRequestId();

  if (!laravelEnabled()) {
    return Response.json(ok({ devotee: { name: 'Devotee (Mock)' } }, requestId));
  }

  const actor = wiredActor(req, requestId);
  if (actor instanceof Response) return actor;

  try {
    const { data } = await laravelGet<unknown>(actor.token, '/customers/me/data-export');
    return Response.json(ok(data, requestId), {
      headers: {
        'Content-Disposition': 'attachment; filename="devotee-data-export.json"',
      },
    });
  } catch (e) {
    return laravelErrorResponse(e, 'Data export failed.', requestId);
  }
}

