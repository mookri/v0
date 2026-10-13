import { describe, it, expect } from 'vitest';
import { secondsUntilNext, resendLabel } from '@navdhya/otp/resend';

describe('resend schedule math', () => {
  it('returns null for null, undefined, or invalid ISO timestamp', () => {
    expect(secondsUntilNext(null)).toBeNull();
    expect(secondsUntilNext(undefined)).toBeNull();
    expect(secondsUntilNext('not-a-date')).toBeNull();
  });

  it('calculates seconds remaining accurately', () => {
    const now = 1000000;
    const targetIso = new Date(now + 45000).toISOString();
    expect(secondsUntilNext(targetIso, now)).toBe(45);
  });

  it('clamps elapsed timestamps to 0 seconds', () => {
    const now = 1000000;
    const pastIso = new Date(now - 10000).toISOString();
    expect(secondsUntilNext(pastIso, now)).toBe(0);
  });

  it('formats human readable countdown labels correctly', () => {
    expect(resendLabel(null)).toBeNull();
    expect(resendLabel(0)).toBeNull();
    expect(resendLabel(-5)).toBeNull();
    expect(resendLabel(30)).toBe('30s');
    expect(resendLabel(60)).toBe('1:00');
    expect(resendLabel(125)).toBe('2:05');
    expect(resendLabel(540)).toBe('9:00');
  });
});
