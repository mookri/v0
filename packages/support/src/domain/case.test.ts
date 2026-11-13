import { describe, expect, it } from 'vitest';
import { canTransitionCase } from './case';

describe('Support case lifecycle parity', () => {
  it('allows valid escalation and redress paths', () => {
    expect(canTransitionCase('NEW', 'ACKNOWLEDGED')).toBe(true);
    expect(canTransitionCase('UNDER_INVESTIGATION', 'RESOLUTION_PROPOSED')).toBe(true);
    expect(canTransitionCase('RESOLUTION_PROPOSED', 'REFUND_PENDING')).toBe(true);
    expect(canTransitionCase('CLOSED', 'REOPENED')).toBe(true);
    expect(canTransitionCase('CLOSED', 'REFERRED_EXTERNALLY')).toBe(true);
  });

  it('blocks illegal terminal transitions from WITHDRAWN', () => {
    expect(canTransitionCase('WITHDRAWN', 'REOPENED')).toBe(false);
    expect(canTransitionCase('WITHDRAWN', 'RESOLVED')).toBe(false);
  });
});

