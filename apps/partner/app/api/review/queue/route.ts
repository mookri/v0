import { newRequestId, ok, err } from '@/application/api-envelope';
import { resolveReviewerActor } from '@/server/review';
import { withCookieFallback, readSessionCookie } from '@/application/actor';
import { isReviewer } from '@/server/review';
import { laravelEnabled, laravelGet, laravelErrorResponse } from '@/adapters/backend/laravel-client';
import { DEFAULT_MARKET } from '@navdhya/identity/src/ports/actor-resolver';

interface ReviewQueueResponse {
  data: Array<{
    id: string;
    object: string;
    track: string;
    status: string;
    submitted_at: string | null;
    sla_breached: boolean | null;
    sla_remaining_ms: number | null;
  }>;
  meta: { request_id?: string; page?: { limit: number; has_more: boolean; next_cursor: string | null } };
}

export async function GET(req: Request) {
  const requestId = newRequestId();

  if (!laravelEnabled()) {
    // Dev/mock fallback
    const { getReviewQueue, resolveReviewerActor: resolveActor, isReviewer: checkReviewer } = await import('@/server/review');
    const { DEFAULT_MARKET: DEF_MARKET } = await import('@navdhya/identity/src/ports/actor-resolver');
    const { withCookieFallback: withFallback } = await import('@/application/actor');

    const actor = await resolveActor(withFallback(req));
    if (!actor) return Response.json(err('UNAUTHENTICATED', 'Sign in to continue.', requestId), { status: 401 });
    if (!checkReviewer(actor.roles)) {
      return Response.json(err('ASSIGNMENT_FORBIDDEN', 'Not allowed.', requestId), { status: 403 });
    }
    const items = await getReviewQueue(actor.market ?? DEF_MARKET, new Date().toISOString());
    return Response.json(ok({ items }, requestId));
  }

  const actor = await resolveReviewerActor(withCookieFallback(req));
  if (!actor) return Response.json(err('UNAUTHENTICATED', 'Sign in to continue.', requestId), { status: 401 });
  if (!isReviewer(actor.roles)) {
    return Response.json(err('ASSIGNMENT_FORBIDDEN', 'Not allowed.', requestId), { status: 403 });
  }

  try {
    const { searchParams } = new URL(req.url);
    const query: Record<string, string> = {};
    for (const [k, v] of searchParams.entries()) {
      query[k] = v;
    }

    const token = req.headers.get('authorization')?.startsWith('Bearer ')
      ? req.headers.get('authorization')!.slice('Bearer '.length).trim()
      : req.headers.get('cookie')
        ? readSessionCookie(req.headers.get('cookie'))
        : null;

    const { data } = await laravelGet<{
      data: Array<{
        id: string;
        object: string;
        track: string;
        status: string;
        submitted_at: string | null;
        sla_breached: boolean | null;
        sla_remaining_ms: number | null;
      }>;
      meta: { request_id?: string; page?: { limit: number; has_more: boolean; next_cursor: string | null } };
    }>(
      token,
      '/onboarding/review/queue',
      query,
      {
        not_found: 'NOT_FOUND',
        permission_missing: 'FORBIDDEN',
        step_up_required: 'FORBIDDEN',
      },
    );

    return Response.json(
      ok(data, requestId),
    );
  } catch (e) {
    return laravelErrorResponse(e, 'Review queue is unavailable.', requestId);
  }
}