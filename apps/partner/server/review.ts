import { MemoryReviewAuditStore } from '@navdhya/partner/src/adapters/memory-review-audit';
import { MemoryPartnerRepository } from '@navdhya/partner/src/adapters/memory-partners';
import { partnerSessions, partnerTokenSecret, SESSION_COOKIE } from '@/server/auth';
import { resolveSessionToken } from '@navdhya/identity/src/application/token-session';
import { RequestActor } from '@navdhya/identity/src/ports/actor-resolver';
import { onboardingApps } from '@/server/onboarding';
import { getTrackDefinition } from '@navdhya/partner/src/onboarding/tracks';
import { reviewSlaStatus } from '@navdhya/partner/src/application/review-sla';
import { ApplicationStatus } from '@navdhya/partner/src/domain/partner-application';

// Review audit composition (single-instance staging; shared store later).
export const reviewAudit = new MemoryReviewAuditStore();
export const reviewPartners = new MemoryPartnerRepository();

export function isReviewer(roles: string[]): boolean {
  return roles.includes('reviewer') || roles.includes('admin');
}

// Session-bound actor resolution for reviewer surfaces (routes + pages):
// revoked/expired sessions reject even with valid signatures. Returns the
// actor regardless of role — callers enforce isReviewer (403) so role
// failures stay distinguishable from auth failures.
export async function resolveReviewerActor(
  headers: Record<string, string | undefined>,
): Promise<RequestActor | null> {
  const bearer = headers['authorization'];
  if (!bearer?.startsWith('Bearer ')) return null;
  try {
    return await resolveSessionToken(
      { sessions: partnerSessions, tokenSecret: partnerTokenSecret() },
      bearer.slice('Bearer '.length).trim(),
      new Date().toISOString(),
    );
  } catch {
    return null;
  }
}

// Page-side actor resolution (server components can't send Authorization):
// bearer header first, then the httpOnly session cookie. Session-bound, so
// logout revokes pages as well as APIs.
export async function resolveReviewer(request: { headers: { get(name: string): string | null } }): Promise<RequestActor | null> {
  const header = request.headers.get('authorization');
  let token: string | null = null;
  if (header?.startsWith('Bearer ')) {
    token = header.slice('Bearer '.length).trim();
  } else {
    const cookie = request.headers.get('cookie') ?? '';
    for (const part of cookie.split(';')) {
      const [name, ...rest] = part.trim().split('=');
      if (name === SESSION_COOKIE && rest.length > 0) {
        token = rest.join('=');
        break;
      }
    }
  }
  if (!token) return null;
  try {
    const actor = await resolveSessionToken(
      { sessions: partnerSessions, tokenSecret: partnerTokenSecret() },
      token,
      new Date().toISOString(),
    );
    return isReviewer(actor.roles) ? actor : null;
  } catch {
    return null;
  }
}

const REVIEW_STATES: ApplicationStatus[] = ['SUBMITTED', 'UNDER_REVIEW', 'APPEALED'];

export interface ReviewQueueItem {
  id: string;
  track: string;
  status: string;
  market: string;
  submittedAt: string | null;
  slaBreached: boolean | null;
  slaRemainingMs: number | null;
}

// Shared by the queue route and the reviewer pages (server components call
// use cases directly — no loopback HTTP).
export async function getReviewQueue(market: string, nowIso: string): Promise<ReviewQueueItem[]> {
  const apps = (await onboardingApps.listByStates(REVIEW_STATES)).filter((app) => (app.market ?? 'in') === market);
  return apps.map((app) => {
    const def = getTrackDefinition(app.track, app.trackVersion);
    const sla = app.submittedAt ? reviewSlaStatus(app.submittedAt, def.slaHours, nowIso) : null;
    return {
      id: app.id,
      track: app.track,
      status: app.status,
      market: app.market ?? 'in',
      submittedAt: app.submittedAt ?? null,
      slaBreached: sla?.breached ?? null,
      slaRemainingMs: sla?.remainingMs ?? null,
    };
  });
}
