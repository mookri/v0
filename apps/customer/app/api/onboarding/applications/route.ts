import { newRequestId, ok, err } from '@/application/api-envelope';
import { laravelEnabled, laravelPost, laravelErrorResponse } from '@/adapters/backend/laravel-client';
import { wiredActor } from '@/server/wired-actor';

interface StartApplicationResponse {
  data: { id: string; object: string; track: string; track_version: string; status: string };
  meta: { request_id?: string };
}

export async function POST(req: Request) {
  const requestId = newRequestId();

  if (!laravelEnabled()) {
    const ctx = (await import('@/application/request-context')).anonymousContext('customer.onboarding.start', requestId);
    if (!ctx.actor) {
      return Response.json(err('UNAUTHENTICATED', 'Sign in to continue.', requestId), { status: 401 });
    }
    return Response.json(err('VALIDATION_ERROR', 'Track is required.', requestId), { status: 422 });
  }

  const actor = wiredActor(req, requestId);
  if (actor instanceof Response) return actor;

  try {
    const body = await req.json();
    const { track } = body as { track?: string };

    if (!track) {
      return Response.json(err('VALIDATION_ERROR', 'Track is required.', requestId), { status: 422 });
    }

    const { data } = await laravelPost<StartApplicationResponse>(
      actor.token,
      '/onboarding/applications',
      { track },
      {
        validation_failed: 'VALIDATION_ERROR',
        unknown_track: 'VALIDATION_ERROR',
      },
    );

    return Response.json(ok(data, requestId), { status: 201 });
  } catch (e) {
    return laravelErrorResponse(e, 'Failed to start onboarding application.', requestId);
  }
}