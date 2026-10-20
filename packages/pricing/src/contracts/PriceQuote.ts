import { Price } from '@navdhya/pricing/src/domain/Price';

export interface PriceQuote {
  quoteId: string;
  price: Price;
  validUntil: Date;
}
