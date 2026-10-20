import { MoneyMinor } from './MoneyMinor';

export type QuoteLineType = 'SERVICE' | 'ADD_ON' | 'FEE' | 'TAX' | 'DISCOUNT' | 'ADJUSTMENT';

export interface QuoteLine {
  id: string;
  type: QuoteLineType;
  referenceId?: string;
  description: string;
  quantity: number;
  unitAmount: MoneyMinor;
  lineAmount: MoneyMinor;
  source?: { promotionId?: string; couponId?: string; ruleVersion?: string };
}

export interface CommercialAdjustment {
  type: string;
  amount: MoneyMinor;
  reasonCode: string;
  source: 'SYSTEM' | 'OPERATOR' | 'POLICY';
  referenceId?: string;
  actorId?: string;
  createdAt: string;
}

export interface Quote {
  id: string;
  currency: string;
  lines: QuoteLine[];
  subtotal: MoneyMinor;
  discounts: MoneyMinor;
  fees: MoneyMinor;
  taxes: MoneyMinor;
  adjustments: MoneyMinor;
  total: MoneyMinor;
  pricingPolicyVersion: string;
  promotionPolicyVersion?: string;
  taxPolicyVersion?: string;
  createdAt: string;
  expiresAt?: string;
  committed?: boolean;
}

export function quoteTotals(lines: QuoteLine[], currency: string): Pick<Quote, 'subtotal' | 'discounts' | 'fees' | 'taxes' | 'adjustments' | 'total'> {
  const sum = (t: QuoteLineType): number =>
    lines.filter((l) => l.type === t).reduce((acc, l) => acc + l.lineAmount.amountMinor, 0);
  const subtotal = sum('SERVICE') + sum('ADD_ON');
  const discounts = sum('DISCOUNT');
  const fees = sum('FEE');
  const taxes = sum('TAX');
  const adjustments = sum('ADJUSTMENT');
  const total = subtotal + discounts + fees + taxes + adjustments;
  const m = (amountMinor: number): MoneyMinor => ({ amountMinor, currency });
  return { subtotal: m(subtotal), discounts: m(discounts), fees: m(fees), taxes: m(taxes), adjustments: m(adjustments), total: m(total) };
}

export function assertQuoteValid(quote: Quote): void {
  if (quote.total.amountMinor < 0) throw new Error('NEGATIVE_TOTAL');
  for (const line of quote.lines) {
    if (line.lineAmount.currency !== quote.currency) throw new Error('CURRENCY_MISMATCH');
    if (line.unitAmount.currency !== quote.currency) throw new Error('CURRENCY_MISMATCH');
  }
}
