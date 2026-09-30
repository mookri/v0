import { Quote, QuoteLine } from '@navdhya/pricing/src/domain/Quote';

export interface CustomerQuoteResponse {
  quoteId: string;
  currency: string;
  lines: { description: string; amountMinor: number; type: QuoteLine['type'] }[];
  subtotalMinor: number;
  discountsMinor: number;
  feesMinor: number;
  taxesMinor: number;
  totalMinor: number;
  expiresAt?: string;
}

export function toCustomerQuote(quote: Quote): CustomerQuoteResponse {
  return {
    quoteId: quote.id,
    currency: quote.currency,
    lines: quote.lines.map((l) => ({ description: l.description, amountMinor: l.lineAmount.amountMinor, type: l.type })),
    subtotalMinor: quote.subtotal.amountMinor,
    discountsMinor: quote.discounts.amountMinor,
    feesMinor: quote.fees.amountMinor,
    taxesMinor: quote.taxes.amountMinor,
    totalMinor: quote.total.amountMinor,
    expiresAt: quote.expiresAt,
  };
}
