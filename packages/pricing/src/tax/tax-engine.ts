import { MoneyMinor } from '../domain/MoneyMinor';

export interface TaxRule {
  id: string;
  version: string;
  ratePercent: number;
  jurisdiction?: string;
  effectiveFrom?: string;
  effectiveUntil?: string;
}

export function applicableTaxRule(rules: TaxRule[], nowIso: string): TaxRule | null {
  const eligible = rules
    .filter((r) => (!r.effectiveFrom || nowIso >= r.effectiveFrom) && (!r.effectiveUntil || nowIso < r.effectiveUntil))
    .sort((a, b) => (b.effectiveFrom ?? '').localeCompare(a.effectiveFrom ?? ''));
  return eligible[0] ?? null;
}

export function calculateTax(taxable: MoneyMinor, rule: TaxRule): MoneyMinor {
  return { amountMinor: Math.round((taxable.amountMinor * rule.ratePercent) / 100), currency: taxable.currency };
}
