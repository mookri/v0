import { Money } from './Money';

export interface Tax {
  name: string;
  percentage: number;
  amount: Money;
}
