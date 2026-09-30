import { test, expect } from 'vitest';
import { createQuote } from '@navdhya/pricing/src/engine/quote-engine';
import { redeemCoupon, Coupon, RedemptionLedger } from '@navdhya/pricing/src/promotions/coupon';
import { promotionEligible } from '@navdhya/pricing/src/promotions/promotion-engine';
import { applicableTaxRule } from '@navdhya/pricing/src/tax/tax-engine';
import { assertQuoteValid } from '@navdhya/pricing/src/domain/Quote';
import { toCustomerQuote } from '@navdhya/pricing/src/contracts/QuoteResponse';
import { add, percentOf } from '@navdhya/pricing/src/domain/MoneyMinor';

const NOW = '2026-10-01T00:00:00.000Z';
const promo = (over: Record<string, unknown> = {}) => ({
  id: 'fest20', name: 'Festival 20%', status: 'ACTIVE' as const,
  conditions: {}, reward: { kind: 'PERCENTAGE' as const, percent: 20 },
  stacking: 'STACKABLE' as const, priority: 10, ...over,
});
const base = { pricingPolicyVersion: 'base-v7', promotionPolicyVersion: 'promo-v2', taxPolicyVersion: 'tax-v3', createdAt: NOW };

test('golden: base only', () => {
  const { quote } = createQuote({ quoteId: 'q1', currency: 'INR', serviceLines: [{ id: 's', description: 'Puja', quantity: 1, unitMinor: 450000 }], promotions: [], promotionContext: { serviceId: 'p' }, taxRules: [], ...base });
  expect(quote.total.amountMinor).toBe(450000);
});

test('golden: promotion + fee + tax composes deterministically', () => {
  const { quote, trace } = createQuote({
    quoteId: 'q2', currency: 'INR',
    serviceLines: [{ id: 's', description: 'Lakshmi Puja', quantity: 1, unitMinor: 450000 }],
    feeLines: [{ id: 'f', description: 'Platform fee', amountMinor: 10000 }],
    promotions: [promo()],
    promotionContext: { serviceId: 'p' },
    taxRules: [{ id: 'gst', version: 'v3', ratePercent: 18 }],
    ...base,
  });
  // 450000 - 90000 = 360000 taxable (fees excluded); tax 64800; total 434800
  expect(quote.total.amountMinor).toBe(434800);
  expect(trace.appliedPromotions).toEqual(['fest20']);
  expect(trace.pricingPolicyVersion).toBe('base-v7');
});

test('non-stackable beats stackables by priority; exclusive wins all', () => {
  const a = promo({ id: 'a', priority: 1 });
  const b = promo({ id: 'b', priority: 5, stacking: 'NON_STACKABLE' as const });
  const r1 = createQuote({ quoteId: 'q', currency: 'INR', serviceLines: [{ id: 's', description: 's', quantity: 1, unitMinor: 100000 }], promotions: [a, b], promotionContext: { serviceId: 'p' }, taxRules: [], ...base });
  expect(r1.trace.appliedPromotions).toEqual(['b', 'a']);
  const r2 = createQuote({ quoteId: 'q', currency: 'INR', serviceLines: [{ id: 's', description: 's', quantity: 1, unitMinor: 100000 }], promotions: [a, { ...b, stacking: 'EXCLUSIVE' as const }], promotionContext: { serviceId: 'p' }, taxRules: [], ...base });
  expect(r2.trace.appliedPromotions).toEqual(['b']);
});

test('expired promotion ineligible; tax effective dates select version', () => {
  expect(promotionEligible(promo({ endsAt: '2020-01-01T00:00:00.000Z' }), { serviceId: 'p', subtotalMinor: 1 }, NOW)).toBe(false);
  const rule = applicableTaxRule(
    [{ id: 'old', version: 'v2', ratePercent: 12, effectiveFrom: '2020-01-01T00:00:00.000Z' }, { id: 'new', version: 'v3', ratePercent: 18, effectiveFrom: '2026-01-01T00:00:00.000Z' }],
    NOW,
  );
  expect(rule?.id).toBe('new');
});

test('zero total supported; negative totals rejected', () => {
  const { quote } = createQuote({ quoteId: 'q', currency: 'INR', serviceLines: [{ id: 's', description: 's', quantity: 1, unitMinor: 50000 }], promotions: [promo({ id: 'full', reward: { kind: 'FIXED' as const, amountMinor: 50000 } })], promotionContext: { serviceId: 'p' }, taxRules: [], ...base });
  expect(quote.total.amountMinor).toBe(0);
  expect(() => assertQuoteValid({ ...quote, total: { amountMinor: -1, currency: 'INR' } })).toThrow('NEGATIVE_TOTAL');
});

test('money arithmetic is integer-based with currency guard', () => {
  expect(add({ amountMinor: 1, currency: 'INR' }, { amountMinor: 2, currency: 'INR' }).amountMinor).toBe(3);
  expect(percentOf({ amountMinor: 99, currency: 'INR' }, 10).amountMinor).toBe(10);
  expect(() => add({ amountMinor: 1, currency: 'INR' }, { amountMinor: 1, currency: 'USD' })).toThrow('CURRENCY_MISMATCH');
});

test('coupon limits enforced exactly', async () => {
  const coupon: Coupon = { id: 'c1', code: 'X', promotionId: 'p', status: 'ACTIVE' };
  let n = 0;
  const ledger: RedemptionLedger = { countTotal: async () => n, countByCustomer: async () => 0, redeem: async () => { n++; return true; } };
  expect(await redeemCoupon({ ...ledger, countTotal: async () => 1 }, { ...coupon, usageLimit: 1 }, 'u', 'q', NOW)).toEqual({ ok: false, reason: 'COUPON_LIMIT_REACHED' });
  expect(await redeemCoupon(ledger, coupon, 'u', 'q', NOW)).toEqual({ ok: true });
});

test('customer DTO exposes breakdown, never policy internals', () => {
  const { quote } = createQuote({ quoteId: 'q', currency: 'INR', serviceLines: [{ id: 's', description: 'Puja', quantity: 1, unitMinor: 100000 }], promotions: [], promotionContext: { serviceId: 'p' }, taxRules: [], ...base });
  const dto = toCustomerQuote(quote);
  expect(dto.totalMinor).toBe(100000);
  expect(JSON.stringify(dto)).not.toMatch(/priority|effectiveFrom/);
});
