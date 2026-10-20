import { Quote } from '@navdhya/pricing/src/domain/Quote';

export interface QuoteRepository {
  save(quote: Quote): Promise<void>;
  findById(id: string): Promise<Quote | null>;
}
