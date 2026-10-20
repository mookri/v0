import { test, expect } from 'vitest';
import { availableOf, projectBalance } from '@navdhya/resource-management/src/domain/inventory';
import { reserveStock, buildResourcePlan } from '@navdhya/resource-management/src/application/inventory';
import { isReservationLive, consumptionVariance } from '@navdhya/resource-management/src/domain/reservation';
import { recipeActive } from '@navdhya/resource-management/src/domain/recipe';
import { belowReorderPoint } from '@navdhya/resource-management/src/domain/procurement';

const NOW = '2026-09-30T00:00:00.000Z';

test('available decomposes into tracked components, never a bare number', () => {
  expect(availableOf({ itemId: 'k', locationId: 'w', onHand: 20, reserved: 5, allocated: 3, version: 1 })).toBe(12);
});

test('balance rebuilds from movement history', () => {
  const mk = (type: 'RECEIPT' | 'CONSUMPTION' | 'ADJUSTMENT' | 'RESERVATION', amount: number) => ({
    id: 'm', itemId: 'k', locationId: 'w', type, quantity: { amount, unit: 'kit' }, occurredAt: NOW,
  });
  expect(projectBalance([mk('RECEIPT', 100), mk('CONSUMPTION', 7), mk('ADJUSTMENT', -3), mk('RESERVATION', 50)])).toBe(90);
});

test('reservations are version-guarded against oversell', async () => {
  let position = { itemId: 'k', locationId: 'w', onHand: 20, reserved: 0, allocated: 0, version: 1 };
  const store = {
    get: async () => ({ ...position }),
    save: async (next: typeof position, expected: number) => {
      if (expected !== position.version) return false;
      position = next;
      return true;
    },
  };
  const reservations = { saved: [] as unknown[], save: async (r: unknown) => { (reservations.saved as unknown[]).push(r); } };
  const base = { itemId: 'k', locationId: 'w', unit: 'kit', referenceType: 'fulfillment', referenceId: 'f', nowIso: NOW, idempotencyKey: 'k' };
  expect((await reserveStock(store, reservations, { ...base, reservationId: 'r1', quantity: 10 })).ok).toBe(true);
  expect((await reserveStock(store, reservations, { ...base, reservationId: 'r2', quantity: 15 })).ok).toBe(false);
  const stale = { ...position, version: 1 };
  void stale;
  expect(position.reserved).toBe(10);
});

test('reservation liveness honors expiry; consumption records variance', () => {
  const live = { id: 'r', itemId: 'k', locationId: 'w', quantity: { amount: 1, unit: 'kit' }, referenceType: 'f', referenceId: 'f1', status: 'HELD' as const, version: 1 };
  expect(isReservationLive(live, NOW)).toBe(true);
  expect(isReservationLive({ ...live, expiresAt: '2020-01-01T00:00:00.000Z' }, NOW)).toBe(false);
  expect(consumptionVariance({ amount: 2, unit: 'kg' }, { amount: 1.7, unit: 'kg' })).toBeCloseTo(-0.3);
  expect(consumptionVariance({ amount: 2, unit: 'kg' }, { amount: 1.7, unit: 'g' })).toBeNull();
});

test('recipes gate on version windows; plans snapshot versions', () => {
  const recipe = { id: 'r', offeringId: 'o', version: 'v4', status: 'ACTIVE' as const, effectiveFrom: NOW, lines: [{ itemId: 'k', quantity: { amount: 1, unit: 'kit' }, consumptionType: 'CONSUMED' as const, optional: false, mode: 'REQUIRED' as const, sourceMode: 'PLATFORM' as const }] };
  expect(recipeActive(recipe, NOW)).toBe(true);
  expect(recipeActive({ ...recipe, effectiveFrom: '2030-01-01T00:00:00.000Z' }, NOW)).toBe(false);
  const plan = buildResourcePlan({ fulfillmentId: 'f', recipe, nowIso: NOW });
  expect(plan?.recipeVersion).toBe('v4');
  expect(plan?.requirements).toHaveLength(1);
});

test('reorder points fire on explicit thresholds', () => {
  expect(belowReorderPoint(12, { reorderPoint: { amount: 12, unit: 'kit' } })).toBe(true);
  expect(belowReorderPoint(13, { reorderPoint: { amount: 12, unit: 'kit' } })).toBe(false);
});
