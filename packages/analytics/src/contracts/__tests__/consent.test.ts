// packages/analytics/src/contracts/__tests__/consent.test.ts
// B1 acceptance: consent state machine + per-batch HMAC consent token.

import { describe, expect, it } from 'vitest';
import {
  UNKNOWN_CONSENT,
  analyticsAllowed,
  applyConsentAction,
  issueConsentToken,
  transitionConsent,
  verifyConsentToken,
  type ConsentState,
} from '@navdhya/analytics/src/contracts/consent';

describe('consent state machine', () => {
  it('fails closed: UNKNOWN does not allow analytics', () => {
    expect(analyticsAllowed(UNKNOWN_CONSENT)).toBe(false);
    expect(analyticsAllowed({ analytics: 'DENIED', ads: 'GRANTED' })).toBe(false);
    expect(analyticsAllowed({ analytics: 'GRANTED', ads: 'DENIED' })).toBe(true);
  });

  it('UNKNOWN → DENIED | GRANTED; grant/deny from any state; withdraw records a decision', () => {
    const cases: Array<[ConsentState, Parameters<typeof transitionConsent>[1], ConsentState]> = [
      ['UNKNOWN', 'grant', 'GRANTED'],
      ['UNKNOWN', 'deny', 'DENIED'],
      ['DENIED', 'grant', 'GRANTED'],
      ['GRANTED', 'withdraw', 'DENIED'],
      ['GRANTED', 'deny', 'DENIED'],
      ['GRANTED', 'reset', 'UNKNOWN'],
      ['DENIED', 'reset', 'UNKNOWN'],
    ];
    for (const [from, action, to] of cases) {
      expect(transitionConsent(from, action)).toBe(to);
    }
  });

  it('purposes are independent — ads consent never inferred from analytics', () => {
    const next = applyConsentAction(UNKNOWN_CONSENT, 'analytics', 'grant');
    expect(next).toEqual({ analytics: 'GRANTED', ads: 'UNKNOWN' });
    expect(analyticsAllowed(next)).toBe(true);
  });
});

describe('consent token (HMAC-SHA256)', () => {
  const secret = 'test-consent-secret';
  const payload = { v: 1 as const, sub: 'hmac_fixture_hash_a', analytics: true, ads: false, iat: 1000, exp: 2000 };

  it('round-trips issue → verify within validity window', async () => {
    const token = await issueConsentToken(payload, secret);
    const verified = await verifyConsentToken(token, secret, 1500);
    expect(verified).toEqual(payload);
  });

  it('fails closed on tampering, wrong secret and expiry', async () => {
    const token = await issueConsentToken(payload, secret);

    const [body, sig] = token.split('.') as [string, string];
    const tamperedSig = (sig[0] === 'A' ? 'B' : 'A') + sig.slice(1);
    expect(await verifyConsentToken(`${body}.${tamperedSig}`, secret, 1500)).toBeNull();

    expect(await verifyConsentToken(token, 'wrong-secret', 1500)).toBeNull();
    expect(await verifyConsentToken(token, secret, 2000)).toBeNull(); // nowSec >= exp
    expect(await verifyConsentToken('garbage', secret, 1500)).toBeNull();
    expect(await verifyConsentToken('a.b', secret, 1500)).toBeNull();
  });

  it('rejects invalid timestamp windows at issue time', async () => {
    await expect(issueConsentToken({ ...payload, exp: 1000 }, secret)).rejects.toThrow();
  });
});
