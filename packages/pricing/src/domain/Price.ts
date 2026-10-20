import { Money } from './Money';
import { Tax } from './Tax';
import { Discount } from './Discount';

export interface Price {
  baseAmount: Money;
  discounts: Discount[];
  taxes: Tax[];
  finalAmount: Money;
}
