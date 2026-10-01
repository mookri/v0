import { test, expect } from 'vitest';
import { reviewApplication } from '@navdhya/partner/src/application/review';
import { reviewSlaStatus } from '@navdhya/partner/src/application/review-sla';
import { PartnerApplication } from '@navdhya/partner/src/domain/partner-application';

const NOW = '2026-10-01T00:00:00.000Z';
const app = (over: Partial<PartnerApplication> = {}): PartnerApplication => ({
  id: 'app_1',
  identityId: 'u1',
  type: 'PANDIT',
  track: 'pandit',
  trackVersion: 'pandit-v1',
  status: 'UNDER_REVIEW',
  submittedAt: NOW,
  ...over,
});

test('review decisions transition and audit correctly', () => {
  const approved = reviewApplication(app(), { decision: 'APPROVED', reviewerId: 'r1', nowIso: NOW });
  expect(approved.application.status).toBe('APPROVED');
  expect(approved.record).toMatchObject({ applicationId: 'app_1', reviewerId: 'r1', decision: 'APPROVED' });

  const rejected = reviewApplication(app(), { decision: 'REJECTED', reviewerId: 'r1', reasonCode: 'OTHER', nowIso: NOW });
  expect(rejected.application.status).toBe('REJECTED');
  expect(rejected.application.rejectionCode).toBe('OTHER');

  const info = reviewApplication(app(), { decision: 'REQUEST_INFO', reviewerId: 'r1', nowIso: NOW });
  expect(info.application.status).toBe('DRAFT');

  // Appeals stay open: REQUEST_INFO is UNDER_REVIEW-only; direct approve
  // from appeal must re-enter review instead.
  expect(() => reviewApplication(app({ status: 'APPEALED' }), { decision: 'REQUEST_INFO', reviewerId: 'r2', nowIso: NOW })).toThrow(
    'REVIEW_INVALID_STATE:APPEALED',
  );
  // Appeals return to review; direct approve from appeal is illegal.
  expect(() => reviewApplication(app({ status: 'APPEALED' }), { decision: 'APPROVED', reviewerId: 'r2', nowIso: NOW })).toThrow(
    'ILLEGAL_APPLICATION_TRANSITION:APPEALED->APPROVED',
  );
});

test('review guards states and reasons', () => {
  expect(() => reviewApplication(app({ status: 'DRAFT' }), { decision: 'APPROVED', reviewerId: 'r', nowIso: NOW })).toThrow(
    'REVIEW_INVALID_STATE:DRAFT',
  );
  expect(() => reviewApplication(app(), { decision: 'REJECTED', reviewerId: 'r', nowIso: NOW })).toThrow(
    'REVIEW_REASON_REQUIRED',
  );
  expect(() => reviewApplication(app({ status: 'APPEALED' }), { decision: 'REQUEST_INFO', reviewerId: 'r', nowIso: NOW })).toThrow(
    'REVIEW_INVALID_STATE:APPEALED',
  );
});

test('SLA breach computed from track hours', () => {
  expect(reviewSlaStatus(NOW, 48, NOW)).toEqual({ breached: false, remainingMs: 48 * 3600_000 });
  expect(reviewSlaStatus(NOW, 48, '2026-10-04T00:00:00.000Z')).toEqual({ breached: true, remainingMs: -24 * 3600_000 });
});
