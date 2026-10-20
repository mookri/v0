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

export function add(a: MoneyMinor, b: MoneyMinor): MoneyMinor {
  assertSameCurrency(a, b);
  return { amountMinor: a.amountMinor + b.amountMinor, currency: a.currency };
}

export function subtract(a: MoneyMinor, b: MoneyMinor): MoneyMinor {
  assertSameCurrency(a, b);
  return { amountMinor: a.amountMinor - b.amountMinor, currency: a.currency };
}

export function percentOf(base: MoneyMinor, percent: number): MoneyMinor {
  return { amountMinor: Math.round((base.amountMinor * percent) / 100), currency: base.currency };
}

export function isNegative(m: MoneyMinor): boolean {
  return m.amountMinor < 0;
}
