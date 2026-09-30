import { test, expect } from 'vitest';
import { normalizeAddress, isValidCoordinate } from '@navdhya/geography/src/domain/location';
import { canonicalRegionId, ancestors } from '@navdhya/geography/src/domain/region';
import { evaluateServiceability, batchEvaluate } from '@navdhya/geography/src/domain/serviceability';

test('addresses normalize without inventing precision', () => {
  const n = normalizeAddress({ line1: '  23,  MG Road ', city: 'Gurugram', postalCode: '122 001', countryCode: 'in' });
  expect(n).toEqual({ countryCode: 'IN', administrativeArea: undefined, district: undefined, city: 'Gurugram', locality: undefined, postalCode: '122001', lines: ['23, MG Road'] });
  expect(isValidCoordinate({ latitude: 28.6, longitude: 77.2 })).toBe(true);
  expect(isValidCoordinate({ latitude: 200, longitude: 0 })).toBe(false);
});

test('aliases canonicalize without becoming identity', () => {
  const aliases = [{ alias: 'Gurgaon', regionId: 'r-ggn', source: 'seed' }, { alias: 'Gurugram', regionId: 'r-ggn', source: 'seed' }];
  expect(canonicalRegionId(aliases, 'gurgaon')).toBe('r-ggn');
  expect(canonicalRegionId(aliases, 'Unknown')).toBeNull();
  const regions = [
    { id: 'in', type: 'COUNTRY' as const, name: 'India', status: 'ACTIVE' as const },
    { id: 'hr', type: 'STATE' as const, name: 'Haryana', parentId: 'in', status: 'ACTIVE' as const },
    { id: 'r-ggn', type: 'CITY' as const, name: 'Gurugram', parentId: 'hr', status: 'ACTIVE' as const },
  ];
  expect(ancestors(regions, 'r-ggn').map((r) => r.id)).toEqual(['hr', 'in']);
});

test('exclusions beat inclusions; unknown stays unknown', () => {
  const policy = {
    id: 'p', serviceId: 's', ruleVersion: 'v5',
    rules: [
      { kind: 'REGION_ALLOW' as const, regionId: 'r-ncr', ruleId: 'allow-ncr' },
      { kind: 'POSTAL_DENY' as const, postalCode: '110001', ruleId: 'deny-central' },
    ],
  };
  const base = { serviceId: 's', locationId: 'l', policy, nowIso: '2026-09-30T00:00:00.000Z' };
  expect(evaluateServiceability({ ...base, decisionId: 'd1', regionIds: ['r-ncr'], postalCode: '110001' }).result).toBe('NOT_SERVICEABLE');
  expect(evaluateServiceability({ ...base, decisionId: 'd2', regionIds: ['r-ncr'] }).result).toBe('SERVICEABLE');
  expect(evaluateServiceability({ ...base, decisionId: 'd3', regionIds: ['r-remote'] }).result).toBe('NOT_SERVICEABLE');
  expect(evaluateServiceability({ ...base, decisionId: 'd4', regionIds: ['r-ncr'], policy: { ...policy, effectiveFrom: '2030-01-01T00:00:00.000Z' } }).result).toBe('UNKNOWN');
});

test('batch evaluation keeps decision identity per request', () => {
  const policy = { id: 'p', serviceId: 's', ruleVersion: 'v5', rules: [{ kind: 'REGION_ALLOW' as const, regionId: 'r', ruleId: 'a' }] };
  const out = batchEvaluate([
    { serviceId: 's', locationId: 'l1', regionIds: ['r'], policy, nowIso: '2026-09-30T00:00:00.000Z' },
    { serviceId: 's', locationId: 'l2', regionIds: ['x'], policy, nowIso: '2026-09-30T00:00:00.000Z' },
  ], 'batch');
  expect(out.map((d) => [d.decisionId, d.result])).toEqual([['batch_1', 'SERVICEABLE'], ['batch_2', 'NOT_SERVICEABLE']]);
  expect(out[0]?.policyVersion).toBe('v5');
});
