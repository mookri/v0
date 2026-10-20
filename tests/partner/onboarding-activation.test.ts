import { test, expect } from 'vitest';
import { activateOnApproval } from '@navdhya/partner/src/application/activation';
import { MemoryPartnerRepository } from '@navdhya/partner/src/adapters/memory-partners';
import { MemoryOnboardingRepository } from '@navdhya/partner/src/adapters/memory-applications';
import { SeededChallengePort } from './seeded-challenge-port';
import { beginSubmit, confirmSubmit } from '@navdhya/partner/src/application/onboarding-submit';
import { reviewApplication } from '@navdhya/partner/src/application/review';
import { isPartnerWorkEligible } from '@navdhya/partner/src/domain/partner';

const NOW = '2026-10-01T00:00:00.000Z';
const full = {
  identity: { name: 'Ravi', phone: '+91' },
  credentials: { certifications: ['vedic'] },
  services: ['lakshmi-puja'],
  areas: ['delhi'],
  availability: { set: true },
  documents: ['doc_1'],
  agreement: { accepted: true },
};

test('activation creates, advances, and never force-exits', async () => {
  const partners = new MemoryPartnerRepository();
  const fresh = await activateOnApproval({ partners }, { identityId: 'u1', type: 'PANDIT', partnerId: 'p_u1', nowIso: NOW });
  expect(fresh).toMatchObject({ created: true, eligible: true });
  expect(fresh.partner.lifecycleStatus).toBe('ACTIVE');

  await partners.save({
    id: 'p_v',
    identityId: 'u2',
    type: 'VENDOR',
    lifecycleStatus: 'VERIFIED',
    agreementVersion: 1,
    capabilityVersion: 1,
  });
  const advanced = await activateOnApproval({ partners }, { identityId: 'u2', type: 'VENDOR', partnerId: 'p_v', nowIso: NOW });
  expect(advanced.created).toBe(false);
  expect(advanced.partner.lifecycleStatus).toBe('ACTIVE');
  expect((await partners.findById('p_v'))?.lifecycleStatus).toBe('ACTIVE');

  await partners.save({
    id: 'p_s',
    identityId: 'u3',
    type: 'PANDIT',
    lifecycleStatus: 'SUSPENDED',
    agreementVersion: 1,
    capabilityVersion: 1,
  });
  const suspended = await activateOnApproval({ partners }, { identityId: 'u3', type: 'PANDIT', partnerId: 'p_s', nowIso: NOW });
  expect(suspended.partner.lifecycleStatus).toBe('SUSPENDED');
  expect(suspended.eligible).toBe(false);
});

test('end-to-end proof: draft → submit → approve → eligible', async () => {
  const apps = new MemoryOnboardingRepository();
  const partners = new MemoryPartnerRepository();
  const otp = new SeededChallengePort();
  await apps.save({
    id: 'app_e2e',
    identityId: 'u9',
    type: 'PANDIT',
    track: 'pandit',
    trackVersion: 'pandit-v1',
    status: 'DRAFT',
  });
  const draft = (await apps.findById('app_e2e'))!;
  const { challengeId } = await beginSubmit({ otp }, draft, full, '+91', NOW);
  const confirmed = await confirmSubmit({ otp }, draft, full, challengeId, otp.codeFor(challengeId), NOW);
  expect(confirmed.application.status).toBe('SUBMITTED');
  await apps.update(confirmed.application);
  const underReview = { ...confirmed.application, status: 'UNDER_REVIEW' as const };
  await apps.update(underReview);
  const { application: approved } = reviewApplication(underReview, { decision: 'APPROVED', reviewerId: 'r1', nowIso: NOW });
  await apps.update(approved);
  const { partner, eligible } = await activateOnApproval(
    { partners },
    { identityId: 'u9', type: 'PANDIT', partnerId: 'p_u9', nowIso: NOW },
  );
  expect(eligible).toBe(true);
  expect(isPartnerWorkEligible(partner.lifecycleStatus)).toBe(true);
});
