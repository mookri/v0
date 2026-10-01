import { MemoryReviewAuditStore } from '@navdhya/partner/src/adapters/memory-review-audit';
import { MemoryPartnerRepository } from '@navdhya/partner/src/adapters/memory-partners';
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
