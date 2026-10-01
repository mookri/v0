import { newRequestId, ok, err } from '@/application/api-envelope';
import { onboardingActors, onboardingApps } from '@/server/onboarding';
import { isReviewer } from '@/server/review';
import { headersToRecord } from '@/application/actor';
import { getTrackDefinition } from '@navdhya/partner/src/onboarding/tracks';
import { reviewSlaStatus } from '@navdhya/partner/src/application/review-sla';
import { ApplicationStatus } from '@navdhya/partner/src/domain/partner-application';

const REVIEW_STATES: ApplicationStatus[] = ['SUBMITTED', 'UNDER_REVIEW', 'APPEALED'];

export async function GET(req: Request) {
  const requestId = newRequestId();
  const actor = await onboardingActors.resolve(headersToRecord(req.headers));
  if (!actor) return Response.json(err('UNAUTHENTICATED', 'Sign in to continue.', requestId), { status: 401 });
  if (!isReviewer(actor.roles)) {
    return Response.json(err('ASSIGNMENT_FORBIDDEN', 'Not allowed.', requestId), { status: 403 });
  }
  const nowIso = new Date().toISOString();
  const apps = await onboardingApps.listByStates(REVIEW_STATES);
  const items = apps.map((app) => {
    const def = getTrackDefinition(app.track, app.trackVersion);
    const sla = app.submittedAt ? reviewSlaStatus(app.submittedAt, def.slaHours, nowIso) : null;
    return {
      id: app.id,
      track: app.track,
      status: app.status,
      submittedAt: app.submittedAt ?? null,
      slaBreached: sla?.breached ?? null,
      slaRemainingMs: sla?.remainingMs ?? null,
    };
  });
  return Response.json(ok({ items }, requestId));
}
