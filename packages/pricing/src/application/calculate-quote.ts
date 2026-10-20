import { PricingRepository } from '@navdhya/pricing/src/ports/PricingRepository';
import { PricingContext } from '@navdhya/pricing/src/contracts/PricingContext';
import { PriceQuote } from '@navdhya/pricing/src/contracts/PriceQuote';

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
