import { test, expect } from 'vitest';
import { SeededOtpProvider } from '@navdhya/identity/src/adapters/memory-otp';
import { beginSubmit, confirmSubmit } from '@navdhya/partner/src/application/onboarding-submit';
import { PartnerApplication } from '@navdhya/partner/src/domain/partner-application';

const NOW = '2026-10-01T00:00:00.000Z';
const app = (over: Partial<PartnerApplication> = {}): PartnerApplication => ({
  id: 'app_1',
  identityId: 'u1',
  type: 'PANDIT',
  track: 'pandit',
  trackVersion: 'pandit-v1',
  status: 'DRAFT',
  ...over,
});
const full = {
  identity: { name: 'Ravi', phone: '+91' },
  credentials: { certifications: ['vedic'] },
  services: ['lakshmi-puja'],
  areas: ['delhi'],
  availability: { set: true },
  documents: ['id-proof'],
  agreement: { accepted: true },
};
const deps = () => ({ otp: new SeededOtpProvider() });

test('two-phase submit: validate, challenge, confirm transitions', async () => {
  const { application, challengeId } = await beginSubmit(deps(), app(), full, '+91', NOW);
  expect(application.status).toBe('DRAFT');
  const provider = deps().otp;
  const { challengeId: ch } = await beginSubmit({ otp: provider }, app(), full, '+91', NOW);
  const done = await confirmSubmit({ otp: provider }, app(), full, ch, provider.codeFor(ch), NOW);
  expect(done.application.status).toBe('SUBMITTED');
  expect(done.application.submittedAt).toBe(NOW);
  expect(challengeId).toBeDefined();
});

test('incomplete drafts block both phases; edits between phases caught', async () => {
  await expect(beginSubmit(deps(), app(), {}, '+91', NOW)).rejects.toThrow('SUBMIT_INCOMPLETE:identity');
  const provider = deps().otp;
  const { challengeId } = await beginSubmit({ otp: provider }, app(), full, '+91', NOW);
  await expect(confirmSubmit({ otp: provider }, app(), {}, challengeId, provider.codeFor(challengeId), NOW)).rejects.toThrow(
    'SUBMIT_INCOMPLETE',
  );
});

test('wrong codes, expiry, exhaustion, and replay all collapse to OTP_INVALID', async () => {
  const provider = deps().otp;
  const { challengeId } = await beginSubmit({ otp: provider }, app(), full, '+91', NOW);
  await expect(confirmSubmit({ otp: provider }, app(), full, challengeId, '000000', NOW)).rejects.toThrow('OTP_INVALID');
  await expect(
    confirmSubmit({ otp: provider }, app(), full, challengeId, provider.codeFor(challengeId), '2026-10-01T02:00:00.000Z'),
  ).rejects.toThrow('OTP_INVALID');
  // Exhaust attempts, then even the right code fails.
  const p2 = deps().otp;
  const { challengeId: ch2 } = await beginSubmit({ otp: p2 }, app(), full, '+91', NOW);
  for (let i = 0; i < 3; i++) await confirmSubmit({ otp: p2 }, app(), full, ch2, '000000', NOW).catch(() => null);
  await expect(confirmSubmit({ otp: p2 }, app(), full, ch2, p2.codeFor(ch2), NOW)).rejects.toThrow('OTP_INVALID');
  // Consumed challenges cannot replay.
  const p3 = deps().otp;
  const { challengeId: ch3 } = await beginSubmit({ otp: p3 }, app(), full, '+91', NOW);
  await confirmSubmit({ otp: p3 }, app(), full, ch3, p3.codeFor(ch3), NOW);
  await expect(confirmSubmit({ otp: p3 }, app(), full, ch3, p3.codeFor(ch3), NOW)).rejects.toThrow('OTP_INVALID');
});

test('illegal states and rate limits reject', async () => {
  await expect(beginSubmit(deps(), app({ status: 'SUBMITTED' }), full, '+91', NOW)).rejects.toThrow(
    'SUBMIT_INVALID_STATE:SUBMITTED',
  );
  const provider = deps().otp;
  for (let i = 0; i < 5; i++) await beginSubmit({ otp: provider }, app(), full, '+91', NOW);
  await expect(beginSubmit({ otp: provider }, app(), full, '+91', NOW)).rejects.toThrow('OTP_RATE_LIMITED');
});
