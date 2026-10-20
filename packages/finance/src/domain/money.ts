export interface MoneyMinor {
  amountMinor: number;
  currency: string;
}

export function money(amountMinor: number, currency = 'INR'): MoneyMinor {
  if (!Number.isInteger(amountMinor)) throw new Error('MONEY_NOT_MINOR_UNITS');
  return { amountMinor, currency };
}

export function assertSameCurrency(a: MoneyMinor, b: MoneyMinor): void {
  if (a.currency !== b.currency) throw new Error('CURRENCY_MISMATCH');
}

export function addMinor(a: MoneyMinor, b: MoneyMinor): MoneyMinor {
  assertSameCurrency(a, b);
  return { amountMinor: a.amountMinor + b.amountMinor, currency: a.currency };
}

export function isNegativeMinor(m: MoneyMinor): boolean {
  return m.amountMinor < 0;
}
