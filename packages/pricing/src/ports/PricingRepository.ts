import { PriceRule } from '@navdhya/pricing/src/domain/PriceRule';

export interface PricingRepository {
  getBasePrice(pujaId: string): Promise<number>;
  getActiveRules(): Promise<PriceRule[]>;
  validateDiscount(code: string): Promise<boolean>;
}
