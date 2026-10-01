import { newRequestId, ok, err } from '@/application/api-envelope';
import { onboardingActors } from '@/server/onboarding';
import { isReviewer, getReviewQueue } from '@/server/review';
import { headersToRecord } from '@/application/actor';
import { DEFAULT_MARKET } from '@navdhya/identity/src/ports/actor-resolver';

export async function GET(req: Request) {
  const requestId = newRequestId();
  const actor = await onboardingActors.resolve(headersToRecord(req.headers));
  if (!actor) return Response.json(err('UNAUTHENTICATED', 'Sign in to continue.', requestId), { status: 401 });
  if (!isReviewer(actor.roles)) {
    return Response.json(err('ASSIGNMENT_FORBIDDEN', 'Not allowed.', requestId), { status: 403 });
  }
  const items = await getReviewQueue(actor.market ?? DEFAULT_MARKET, new Date().toISOString());
  return Response.json(ok({ items }, requestId));
}
