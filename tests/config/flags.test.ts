import { test, expect } from 'vitest';
import { evaluateFlag, FlagPolicy } from '@navdhya/config/src/flags';

const base: FlagPolicy = { key: 'booking.new_flow', enabled: true };

test('kill switch and environment gates dominate', () => {
  expect(evaluateFlag({ ...base, enabled: false }, { environment: 'production' })).toBe(false);
  expect(evaluateFlag({ ...base, environments: ['staging'] }, { environment: 'production' })).toBe(false);
  expect(evaluateFlag({ ...base, environments: ['staging'] }, { environment: 'staging' })).toBe(true);
  expect(evaluateFlag(base, { environment: 'production' })).toBe(true);
});

test('allow-lists bypass rollout; rollout is deterministic per key', () => {
  const policy: FlagPolicy = { ...base, rolloutPercent: 50, allowedUserIds: ['vip-1'], allowedCohorts: ['beta'] };
  expect(evaluateFlag(policy, { environment: 'production', userId: 'vip-1' })).toBe(true);
  expect(evaluateFlag(policy, { environment: 'production', cohort: 'beta' })).toBe(true);
  const a = evaluateFlag(policy, { environment: 'production', userId: 'u-100' });
  expect(evaluateFlag(policy, { environment: 'production', userId: 'u-100' })).toBe(a);
  expect(evaluateFlag(policy, { environment: 'production' })).toBe(false);
  // Rough distribution sanity over a spread of ids.
  let on = 0;
  for (let i = 0; i < 200; i++) {
    if (evaluateFlag(policy, { environment: 'production', userId: `u-${i}` })) on++;
  }
  expect(on).toBeGreaterThan(40);
  expect(on).toBeLessThan(160);
});

test('rollout edges are exact', () => {
  expect(evaluateFlag({ ...base, rolloutPercent: 0 }, { environment: 'production', userId: 'u' })).toBe(false);
  expect(evaluateFlag({ ...base, rolloutPercent: 100 }, { environment: 'production', userId: 'u' })).toBe(true);
});
