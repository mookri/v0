import { Price } from '../domain/Price';

export interface PriceQuote {
  quoteId: string;
  price: Price;
  validUntil: Date;
}
