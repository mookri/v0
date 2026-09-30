export interface PriceRule {
  id: string;
  name: string;
  apply(basePrice: number): number;
}
