import { test, expect } from 'vitest';
import { isEntitlementUsable, remainingQuantity } from '../../packages/commerce/src/domain/entitlement';
import { isMembershipActive } from '../../packages/commerce/src/domain/membership';
import { reserveCredit, consumeHeldCredit } from '../../packages/commerce/src/domain/credit';
import { rebuildWalletBalance, canSpend } from '../../packages/commerce/src/domain/wallet';
import { redeemGiftCard } from '../../packages/commerce/src/domain/gift-card';
import { rebuildPointsBalance, canQualifyReferral } from '../../packages/commerce/src/domain/loyalty';
import { resolveCommerceDecision, BENEFIT_APPLICATION_ORDER } from '../../packages/commerce/src/application/commerce-context';

const NOW = '2026-09-30T00:00:00.000Z';
const INR = 'INR';

test('entitlements gate on status, expiry, and quantity', () => {
  const base = { id: 'e', customerId: 'u', type: 'FREE_ADD_ON', sourceType: 'MEMBERSHIP' as const, sourceId: 'm', status: 'AVAILABLE' as const, quantityTotal: 1, quantityConsumed: 0, startsAt: NOW };
  expect(isEntitlementUsable(base, NOW)).toBe(true);
  expect(remainingQuantity(base)).toBe(1);
  expect(isEntitlementUsable({ ...base, quantityConsumed: 1 }, NOW)).toBe(false);
  expect(isEntitlementUsable({ ...base, expiresAt: '2020-01-01T00:00:00.000Z' }, NOW)).toBe(false);
  expect(isEntitlementUsable({ ...base, status: 'REVOKED' as const }, NOW)).toBe(false);
});

test('memberships preserve plan versions and expiry', () => {
  expect(isMembershipActive({ id: 'm', customerId: 'u', planId: 'p', planVersion: 'v1', status: 'ACTIVE', startsAt: NOW, source: 'PURCHASE' }, NOW)).toBe(true);
  expect(isMembershipActive({ id: 'm', customerId: 'u', planId: 'p', planVersion: 'v1', status: 'PAUSED', startsAt: NOW, source: 'PURCHASE' }, NOW)).toBe(false);
});

test('credit reservation prevents double spend', () => {
  const credit = { id: 'c', customerId: 'u', originalAmount: { amountMinor: 50000, currency: INR }, remainingAmount: { amountMinor: 50000, currency: INR }, sourceType: 'SUPPORT', sourceId: 'case', startsAt: NOW, status: 'ACTIVE' as const };
  expect(reserveCredit(credit, 50000, NOW)?.status).toBe('HELD');
  expect(reserveCredit(credit, 60000, NOW)).toBeNull();
  const held = reserveCredit(credit, 50000, NOW)!;
  expect(consumeHeldCredit(held, 50000)?.status).toBe('EXHAUSTED');
  expect(consumeHeldCredit(credit, 100, NOW)).toBeNull();
});

test('wallet balances rebuild from entries, never stored truth', () => {
  const balance = rebuildWalletBalance([
    { id: 'a', walletId: 'w', type: 'CREDIT' as const, amount: { amountMinor: 100000, currency: INR }, createdAt: NOW },
    { id: 'b', walletId: 'w', type: 'HOLD' as const, amount: { amountMinor: 30000, currency: INR }, createdAt: NOW },
  ], INR);
  expect(balance).toEqual({ available: 70000, held: 30000 });
  expect(canSpend(balance, 70000)).toBe(true);
  expect(canSpend(balance, 70001)).toBe(false);
});

test('gift cards redeem partially with expiry guards', () => {
  const card = { id: 'g', publicReference: 'GC-1', codeHash: 'h', currency: INR, originalValue: { amountMinor: 100000, currency: INR }, remainingValue: { amountMinor: 100000, currency: INR }, status: 'ACTIVE' as const, issuedAt: NOW };
  expect(redeemGiftCard(card, 30000, NOW)?.status).toBe('PARTIALLY_USED');
  expect(redeemGiftCard(card, 100000, NOW)?.status).toBe('EXHAUSTED');
  expect(redeemGiftCard(card, 200000, NOW)).toBeNull();
});

test('loyalty rebuilds from entries; referrals qualify explicitly', () => {
  const balance = rebuildPointsBalance([
    { id: 'a', accountId: 'l', type: 'EARN' as const, points: 200, sourceType: 'FULFILLMENT', sourceId: 'f', policyVersion: 'v1', createdAt: NOW },
    { id: 'b', accountId: 'l', type: 'REDEEM' as const, points: 50, sourceType: 'REWARD', sourceId: 'r', policyVersion: 'v1', createdAt: NOW },
  ]);
  expect(balance).toBe(150);
  expect(canQualifyReferral('CLAIMED')).toBe(true);
  expect(canQualifyReferral('CREATED')).toBe(false);
});

test('commerce decisions order benefits explicitly with provenance', () => {
  const d = resolveCommerceDecision({
    decisionId: 'd1',
    candidates: [
      { type: 'WALLET', sourceType: 'WALLET', sourceId: 'w', effectDescription: 'w', policyVersion: 'v1', eligible: true },
      { type: 'CREDIT', sourceType: 'SUPPORT', sourceId: 'c', effectDescription: 'c', policyVersion: 'v1', eligible: true },
      { type: 'LOYALTY', sourceType: 'LOYALTY', sourceId: 'l', effectDescription: 'x', policyVersion: 'v1', eligible: false, rejectReason: 'INSUFFICIENT_POINTS' },
    ],
    policyVersions: ['v1'],
    nowIso: NOW,
    ttlSeconds: 300,
  });
  expect(d.eligibleBenefits.map((b) => b.type)).toEqual(['CREDIT', 'WALLET']);
  expect(d.rejectedBenefits).toEqual([{ type: 'LOYALTY', reason: 'INSUFFICIENT_POINTS' }]);
  expect(BENEFIT_APPLICATION_ORDER[0]).toBe('CREDIT');
});
