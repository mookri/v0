import { PriceQuote } from '../contracts/PriceQuote';

export function validatePrice(quote: PriceQuote): boolean {
  return quote.validUntil > new Date();
}
