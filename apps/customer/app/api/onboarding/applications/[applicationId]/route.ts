import { newRequestId, ok, err } from '@/application/api-envelope';
import { laravelEnabled, laravelGet, laravelErrorResponse } from '@/adapters/backend/laravel-client';
import { wiredActor } from '@/server/wired-actor';

interface ApplicationDetail {
  id: string;
  object: string;
  track: string;
  track_version: string;
  status: string;
  submitted_at: string | null;
  reviewed_at: string | null;
  rejection_code: string | null;
  evidence: Record<string, unknown> | null;
}

export async function GET(req: Request, { params }: { params: Promise<{ applicationId: string }> }) {
  const requestId = newRequestId();
  const { applicationId } = await params;

  if (!laravelEnabled()) {
    const ctx = (await import('@/application/request-context')).anonymousContext('customer.onboarding.detail', requestId);
    if (!ctx.actor) {
      return Response.json(err('UNAUTHENTICATED', 'Sign in to continue.', requestId), { status: 401 });
    }
    return Response.json(err('NOT_FOUND', 'Application not found.', requestId), { status: 404 });
  }

  const actor = wiredActor(req, requestId);
  if (actor instanceof Response) return actor;

  try {
    const { data } = await laravelGet<ApplicationDetail>(
      actor.token,
      `/onboarding/applications/${encodeURIComponent(applicationId)}`,
      {},
      {
        not_found: 'NOT_FOUND',
        permission_missing: 'FORBIDDEN',
      },
    );

    return Response.json(ok(data, requestId));
  } catch (e) {
    return laravelErrorResponse(e, 'Application details are unavailable.', requestId);
  }
}