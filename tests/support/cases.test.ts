import { test, expect } from 'vitest';
import { canTransitionCase, SupportCase } from '@navdhya/support/src/domain/case';
import { visibleToCustomer, visibleToPartner } from '@navdhya/support/src/domain/conversation';
import { computeSlaDue, isSlaBreached } from '@navdhya/support/src/domain/tasks';
import { transitionCase, customerOwnsCase } from '@navdhya/support/src/application/cases';
import { canTransitionDispute } from '@navdhya/support/src/dispute/dispute';

const kase = (over: Partial<SupportCase> = {}): SupportCase => ({
  id: 'c1', reference: 'NAV-1024', type: 'COMPLAINT', category: 'BOOKING',
  status: 'OPEN', priority: 'NORMAL', customerId: 'u1', version: 3,
  createdAt: '2026-09-30T00:00:00.000Z', ...over,
});

test('case lifecycle separates resolved from closed with reopen path', () => {
  expect(canTransitionCase('OPEN', 'RESOLVED')).toBe(true);
  expect(canTransitionCase('RESOLVED', 'CLOSED')).toBe(true);
  expect(canTransitionCase('CLOSED', 'REOPENED')).toBe(true);
  expect(canTransitionCase('REOPENED', 'OPEN')).toBe(true);
  expect(canTransitionCase('OPEN', 'CLOSED')).toBe(false);
  expect(canTransitionCase('CANCELLED', 'OPEN')).toBe(false);
});

test('transitions guard versions and invalid jumps', () => {
  expect(transitionCase(kase(), 'RESOLVED', 3).ok).toBe(true);
  expect(transitionCase(kase(), 'RESOLVED', 2)).toEqual({ ok: false, reason: 'VERSION_CONFLICT' });
  expect(transitionCase(kase(), 'CLOSED', 3)).toEqual({ ok: false, reason: 'INVALID_TRANSITION' });
  expect(transitionCase(kase({ version: 4 }), 'RESOLVED', 4).ok).toBe(true);
});

test('visibility is server-enforced per thread, never UI-filtered', () => {
  const internal = { id: 'm', caseId: 'c', author: { type: 'AGENT' as const }, visibility: 'INTERNAL' as const, channel: 'APP' as const, body: 'x', sequence: 1, createdAt: '2026-09-30T00:00:00.000Z' };
  const partner = { ...internal, visibility: 'PARTNER' as const };
  expect(visibleToCustomer(internal)).toBe(false);
  expect(visibleToPartner(partner)).toBe(true);
  expect(visibleToCustomer({ ...internal, visibility: 'CUSTOMER' })).toBe(true);
});

test('SLA tracks pause semantics and breach explicitly', () => {
  const due = computeSlaDue('2026-09-30T00:00:00.000Z', 60);
  expect(due).toBe('2026-09-30T01:00:00.000Z');
  expect(isSlaBreached({ startedAt: '2026-09-30T00:00:00.000Z', dueAt: due, breached: false }, '2026-09-30T02:00:00.000Z')).toBe(true);
  expect(isSlaBreached({ startedAt: '2026-09-30T00:00:00.000Z', pausedAt: '2026-09-30T00:10:00.000Z', dueAt: due, breached: false }, '2026-09-30T02:00:00.000Z')).toBe(false);
});

test('ownership is resource-scoped, never body-trusted', () => {
  expect(customerOwnsCase(kase(), 'u1')).toBe(true);
  expect(customerOwnsCase(kase(), 'u2')).toBe(false);
});

test('disputes decide without mutating business state', () => {
  expect(canTransitionDispute('OPEN', 'EVIDENCE_COLLECTION')).toBe(true);
  expect(canTransitionDispute('DECIDED', 'IMPLEMENTING')).toBe(true);
  expect(canTransitionDispute('OPEN', 'DECIDED')).toBe(false);
  expect(canTransitionDispute('RESOLVED', 'OPEN')).toBe(false);
});
