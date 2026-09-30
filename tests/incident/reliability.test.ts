import { test, expect } from 'vitest';
import { canTransitionIncident, Incident } from '@navdhya/incident/src/domain/incident';
import { fingerprintKey, groupAlerts } from '@navdhya/incident/src/domain/alert';
import { isControlLive } from '@navdhya/incident/src/domain/control';
import { toPublicStatus } from '@navdhya/incident/src/domain/status';
import { errorBudgetRemaining } from '@navdhya/incident/src/reliability/slo';
import { transitionIncident } from '@navdhya/incident/src/application/incidents';

const NOW = '2026-09-30T00:00:00.000Z';
const incident = (over: Partial<Incident> = {}): Incident => ({
  id: 'i1', reference: 'NAV-INC-1024', title: 'Payment delays', type: 'PAYMENT',
  severity: 'SEV_2', status: 'DECLARED', services: ['payment'],
  startedAt: NOW, detectedAt: NOW, version: 1, ...over,
});

test('lifecycle requires monitoring window before resolution sticks', () => {
  expect(canTransitionIncident('MITIGATING', 'MONITORING')).toBe(true);
  expect(canTransitionIncident('MONITORING', 'RESOLVED')).toBe(true);
  expect(canTransitionIncident('MITIGATING', 'RESOLVED')).toBe(false);
  expect(canTransitionIncident('RESOLVED', 'CLOSED')).toBe(true);
  expect(canTransitionIncident('CLOSED', 'MITIGATING')).toBe(false);
});

test('transitions guard versions and stamp lifecycle times', () => {
  expect(transitionIncident(incident(), 'MITIGATING', 2)).toEqual({ ok: false, reason: 'VERSION_CONFLICT' });
  expect(transitionIncident(incident(), 'RESOLVED', 1)).toEqual({ ok: false, reason: 'INVALID_TRANSITION' });
  const r = transitionIncident(incident({ status: 'MONITORING' }), 'RESOLVED', 1);
  expect(r.ok).toBe(true);
  if (r.ok) {
    expect(r.incident.version).toBe(2);
    expect(r.incident.resolvedAt).toBeTruthy();
  }
});

test('alerts group by fingerprint instead of drowning operators', () => {
  const a = { id: 'a', fingerprint: { source: 's', ruleId: 'r', resource: 'pay', environment: 'prod' }, severity: 'high', status: 'OPEN' as const, detectedAt: NOW };
  const groups = groupAlerts([a, { ...a, id: 'b' }, { ...a, id: 'c', fingerprint: { ...a.fingerprint, resource: 'search' } }]);
  expect(groups.get(fingerprintKey(a.fingerprint))?.length).toBe(2);
  expect(groups.size).toBe(2);
});

test('controls expire and never run silently forever', () => {
  const base = { id: 'c', type: 'PROVIDER_DISABLE' as const, scope: 'whatsapp', enabled: true, activatedBy: 'op', reason: 'outage', incidentId: 'i1', activatedAt: NOW };
  expect(isControlLive(base, NOW)).toBe(true);
  expect(isControlLive({ ...base, expiresAt: '2020-01-01T00:00:00.000Z' }, NOW)).toBe(false);
  expect(isControlLive({ ...base, deactivatedAt: NOW }, NOW)).toBe(false);
});

test('public status carries only customer-safe messaging', () => {
  const s = toPublicStatus({ id: 's', incidentId: 'i1', title: 'Delays', status: 'MONITORING', customerMessage: 'Confirmations may be delayed.', nowIso: NOW });
  expect(s.version).toBe(1);
  expect(JSON.stringify(s)).not.toMatch(/root cause|credential|internal/i);
});

test('error budgets quantify reliability posture', () => {
  expect(errorBudgetRemaining({ id: 's', sliId: 'pay', target: 99.9, window: '30d', policyVersion: 'v1' }, 99.95)).toBeCloseTo(-0.05);
});
