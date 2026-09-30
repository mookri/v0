import { PricingRepository } from '../ports/PricingRepository';
import { PricingContext } from '../contracts/PricingContext';
import { PriceQuote } from '../contracts/PriceQuote';

export async function calculateQuote(repo: PricingRepository, ctx: PricingContext): Promise<PriceQuote> {
  const base = await repo.getBasePrice(ctx.pujaId);
  return {
    quoteId: `quote_${Date.now()}`,
    price: {
      baseAmount: { amount: base, currency: 'INR' },
      discounts: [],
      taxes: [],
      finalAmount: { amount: base, currency: 'INR' },
    },
    validUntil: new Date(Date.now() + 15 * 60000),
  };
}
