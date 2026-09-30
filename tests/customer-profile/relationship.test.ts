import { test, expect } from 'vitest';
import { validateDisplayName, profileStatus } from '../../packages/customer-profile/src/domain/profile';
import { resolvePreference } from '../../packages/customer-profile/src/domain/preferences';
import { saveKey } from '../../packages/customer-profile/src/domain/saved';
import { resolveLifecycle } from '../../packages/customer-profile/src/domain/lifecycle';
import { composeSelfView } from '../../packages/customer-profile/src/application/customer-360';
import { resolveDecision } from '../../packages/personalization/src/index';

const NOW = '2026-09-30T00:00:00.000Z';

test('profile validates fields and derives completeness', () => {
  expect(validateDisplayName('A')).toBe(false);
  expect(validateDisplayName('Asha')).toBe(true);
  const p = (fields: string[]) => ({
    customerId: 'u', version: 1, updatedAt: NOW,
    ...Object.fromEntries(fields.map((f) => [f, { value: 'x', source: 'CUSTOMER' as const, updatedAt: NOW }])),
  });
  expect(profileStatus(p([]) as never, ['displayName', 'preferredLanguage'] as never)).toBe('MINIMAL');
  expect(profileStatus(p(['displayName']) as never, ['displayName', 'preferredLanguage'] as never)).toBe('PARTIAL');
});

test('explicit preferences beat inference; inference expires', () => {
  const explicit = { key: 'LANGUAGE' as const, value: 'hi-IN', source: 'EXPLICIT_CUSTOMER' as const, updatedAt: NOW, version: 1 };
  const inferred = { key: 'LANGUAGE' as const, value: 'en-IN', confidence: 0.9, observedAt: NOW, expiresAt: '2027-01-01T00:00:00.000Z' };
  expect(resolvePreference(explicit, inferred, NOW)).toBe('hi-IN');
  expect(resolvePreference(null, inferred, NOW)).toBe('en-IN');
  expect(resolvePreference(null, { ...inferred, expiresAt: '2020-01-01T00:00:00.000Z' }, NOW)).toBeNull();
});

test('save keys are idempotent per customer+entity+list', () => {
  expect(saveKey('u', 'PUJA', 'p1')).toBe(saveKey('u', 'PUJA', 'p1'));
  expect(saveKey('u', 'PUJA', 'p1')).not.toBe(saveKey('u', 'PUJA', 'p1', 'l2'));
  expect(saveKey('u', 'PUJA', 'p1')).not.toBe(saveKey('v', 'PUJA', 'p1'));
});

test('lifecycle derives from facts with policy versions', () => {
  const base = { registered: true, profileStarted: true, completedBookings: 2, inactivityDays: 30, nowIso: NOW };
  expect(resolveLifecycle({ ...base, lastActivityAt: NOW }).state).toBe('REPEAT_CUSTOMER');
  expect(resolveLifecycle({ ...base, lastActivityAt: '2020-01-01T00:00:00.000Z' }).state).toBe('INACTIVE');
  expect(resolveLifecycle({ registered: false, profileStarted: false, completedBookings: 0, inactivityDays: 30, nowIso: NOW }).state).toBe('VISITOR');
});

test('360 composition degrades per section, never fails wholesale', async () => {
  const view = await composeSelfView({
    profiles: { get: async () => { throw new Error('down'); }, save: async () => false },
    preferences: { list: async () => ({ explicit: [], inferred: [] }), upsert: async () => false },
    saved: { list: async () => [{ id: 's', customerId: 'u', entityType: 'PUJA', entityId: 'p', savedAt: NOW }], save: async () => true, unsave: async () => true },
    lifecycleInputs: async () => ({ registered: true, profileStarted: true, completedBookings: 1, lastActivityAt: NOW }),
    inactivityDays: 30, nowIso: NOW, customerId: 'u',
  });
  expect(view.profile).toBeNull();
  expect(view.savedCount.pujas).toBe(1);
  expect(view.lifecycle).toBe('ACTIVE_CUSTOMER');
});

test('personalization ranks saved intent first with reasons', () => {
  const d = resolveDecision({
    decisionId: 'd', context: { anonymous: false, surface: 'CUSTOMER_HOME' },
    signals: [{ type: 'saved-category', value: 'wealth', source: 'SAVED', observedAt: NOW }],
    candidates: [{ id: 'a', categories: ['festival'] }, { id: 'b', categories: ['wealth'] }],
    policyVersion: 'v1', nowIso: NOW,
  });
  expect(d.candidates[0]).toEqual({ id: 'b', reason: 'saved-match' });
});
