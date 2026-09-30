import { MoneyMinor, add } from '../domain/MoneyMinor';
import { Quote, QuoteLine, quoteTotals, assertQuoteValid } from '../domain/Quote';
import { Promotion, promotionEligible, promotionDiscount, selectPromotions } from '../promotions/promotion-engine';
import { TaxRule, applicableTaxRule, calculateTax } from '../tax/tax-engine';

export interface QuoteEngineInput {
  quoteId: string;
  currency: string;
  serviceLines: { id: string; description: string; quantity: number; unitMinor: number; referenceId?: string }[];
  feeLines?: { id: string; description: string; amountMinor: number }[];
  promotions: Promotion[];
  promotionContext: { serviceId: string; category?: string; newCustomer?: boolean };
  taxRules: TaxRule[];
  pricingPolicyVersion: string;
  promotionPolicyVersion: string;
  taxPolicyVersion: string;
  createdAt: string;
  expiresAt?: string;
}

export interface QuoteTrace {
  pricingPolicyVersion: string;
  promotionPolicyVersion: string;
  taxPolicyVersion: string;
  appliedPromotions: string[];
}

// Pure pipeline: base → promotions → fees → taxes → totals. Client totals never trusted.
export function createQuote(input: QuoteEngineInput): { quote: Quote; trace: QuoteTrace } {
  const m = (amountMinor: number): MoneyMinor => ({ amountMinor, currency: input.currency });
  const lines: QuoteLine[] = input.serviceLines.map((s, i) => ({
    id: `line_${i + 1}`,
    type: 'SERVICE' as const,
    referenceId: s.referenceId,
    description: s.description,
    quantity: s.quantity,
    unitAmount: m(s.unitMinor),
    lineAmount: m(s.unitMinor * s.quantity),
  }));
  const subtotalMinor = lines.reduce((acc, l) => acc + l.lineAmount.amountMinor, 0);
  const eligible = input.promotions.filter((p) =>
    promotionEligible(p, { serviceId: input.promotionContext.serviceId, category: input.promotionContext.category, subtotalMinor, newCustomer: input.promotionContext.newCustomer }, input.createdAt),
  );
  const selected = selectPromotions(eligible);
  let discountTotal = m(0);
  selected.forEach((p, i) => {
    const d = promotionDiscount(p, { amountMinor: subtotalMinor, currency: input.currency });
    discountTotal = add(discountTotal, d);
    lines.push({
      id: `disc_${i + 1}`,
      type: 'DISCOUNT',
      description: p.name,
      quantity: 1,
      unitAmount: d,
      lineAmount: d,
      source: { promotionId: p.id },
    });
  });
  (input.feeLines ?? []).forEach((f, i) => {
    lines.push({ id: `fee_${i + 1}`, type: 'FEE', description: f.description, quantity: 1, unitAmount: m(f.amountMinor), lineAmount: m(f.amountMinor) });
  });
  const totals = quoteTotals(lines, input.currency);
  const taxable = add(totals.subtotal, totals.discounts);
  const rule = applicableTaxRule(input.taxRules, input.createdAt);
  if (rule) {
    const tax = calculateTax({ ...taxable, currency: input.currency }, rule);
    lines.push({ id: `tax_1`, type: 'TAX', description: `Tax ${rule.id}`, quantity: 1, unitAmount: tax, lineAmount: tax, source: { ruleVersion: rule.version } });
  }
  const finalTotals = quoteTotals(lines, input.currency);
  const quote: Quote = {
    id: input.quoteId,
    currency: input.currency,
    lines,
    ...finalTotals,
    pricingPolicyVersion: input.pricingPolicyVersion,
    promotionPolicyVersion: input.promotionPolicyVersion,
    taxPolicyVersion: input.taxPolicyVersion,
    createdAt: input.createdAt,
    expiresAt: input.expiresAt,
  };
  assertQuoteValid(quote);
  return {
    quote,
    trace: {
      pricingPolicyVersion: input.pricingPolicyVersion,
      promotionPolicyVersion: input.promotionPolicyVersion,
      taxPolicyVersion: input.taxPolicyVersion,
      appliedPromotions: selected.map((p) => p.id),
    },
  };
}

export function isQuoteExpired(quote: Quote, nowIso: string): boolean {
  return !!quote.expiresAt && nowIso >= quote.expiresAt;
}
