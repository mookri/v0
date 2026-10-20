import { test, expect } from 'vitest';
import { canTransitionPartner } from '@navdhya/partner/src/application/lifecycle';
import { isPartnerWorkEligible } from '@navdhya/partner/src/domain/partner';
import { canSubmitApplication } from '@navdhya/partner/src/domain/partner-application';
import { isPublicProfile } from '@navdhya/partner/src/domain/partner-profile';
import { evaluateReadiness } from '@navdhya/partner/src/application/readiness';
import { isDocumentUsable } from '@navdhya/partner/src/verification/verification';
import { acceptOffer, rejectOffer } from '@apps/partner/application/assignments';

const NOW = '2026-09-30T00:00:00.000Z';

test('lifecycle separates suspension from identity deactivation', () => {
  expect(canTransitionPartner('ACTIVE', 'SUSPENDED')).toBe(true);
  expect(canTransitionPartner('SUSPENDED', 'ACTIVE')).toBe(true);
  expect(canTransitionPartner('ACTIVE', 'VERIFIED')).toBe(false);
  expect(isPartnerWorkEligible('ACTIVE')).toBe(true);
  expect(isPartnerWorkEligible('SUSPENDED')).toBe(false);
  expect(isPartnerWorkEligible('PAUSED')).toBe(false);
});

test('applications submit once, review explicitly', () => {
  expect(canSubmitApplication('DRAFT')).toBe(true);
  expect(canSubmitApplication('SUBMITTED')).toBe(false);
  expect(canSubmitApplication('APPROVED')).toBe(false);
});

test('readiness enumerates blockers instead of hiding them', () => {
  const ready = evaluateReadiness({ partnerId: 'p', active: true, verified: true, services: 1, serviceAreas: 1, availabilitySet: true, agreementAccepted: true, documentsValid: true });
  expect(ready.readyForAssignments).toBe(true);
  const blocked = evaluateReadiness({ partnerId: 'p', active: true, verified: false, services: 0, serviceAreas: 0, availabilitySet: false, agreementAccepted: false, documentsValid: false });
  expect(blocked.readyForAssignments).toBe(false);
  expect(blocked.blockers.map((b) => b.code)).toContain('NO_AVAILABILITY');
});

test('documents expire without deletion', () => {
  expect(isDocumentUsable({ id: 'd', partnerId: 'p', type: 'ID', storageReference: 's3://x', status: 'VERIFIED' }, NOW)).toBe(true);
  expect(isDocumentUsable({ id: 'd', partnerId: 'p', type: 'ID', storageReference: 's3://x', status: 'VERIFIED', expiresAt: '2020-01-01T00:00:00.000Z' }, NOW)).toBe(false);
  expect(isPublicProfile({ partnerId: 'p', displayName: 'n', languages: [], visibility: 'PUBLIC', publicationVersion: 1 })).toBe(true);
});

test('offer accept/reject enforce ownership and expiry', () => {
  const offer = { id: 'o', fulfillmentId: 'f', partnerId: 'p1', expiresAt: '2026-10-01T00:00:00.000Z', status: 'PENDING' as const };
  expect(acceptOffer(offer, 'p1', NOW, 'k').ok).toBe(true);
  expect(acceptOffer(offer, 'p2', NOW, 'k')).toEqual({ ok: false, reason: 'NOT_OWNED' });
  expect(acceptOffer({ ...offer, expiresAt: '2020-01-01T00:00:00.000Z' }, 'p1', NOW, 'k')).toEqual({ ok: false, reason: 'EXPIRED' });
  expect(rejectOffer(offer, 'p1', 'PERSONAL').ok).toBe(true);
  expect(rejectOffer(null, 'p1', 'PERSONAL')).toEqual({ ok: false, reason: 'NOT_FOUND' });
});
