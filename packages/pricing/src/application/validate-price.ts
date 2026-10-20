import { PriceQuote } from '@navdhya/pricing/src/contracts/PriceQuote';

export function validatePrice(quote: PriceQuote): boolean {
  return quote.validUntil > new Date();
}
