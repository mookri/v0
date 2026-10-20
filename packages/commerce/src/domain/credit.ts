export interface MoneyMinor {
  amountMinor: number;
  currency: string;
}

export type CreditStatus = 'ACTIVE' | 'HELD' | 'EXHAUSTED' | 'EXPIRED' | 'REVOKED';

export interface Credit {
  id: string;
  customerId: string;
  originalAmount: MoneyMinor;
  remainingAmount: MoneyMinor;
  sourceType: string;
  sourceId: string;
  startsAt: string;
  expiresAt?: string;
  status: CreditStatus;
}

export interface CreditHold {
  id: string;
  creditId: string;
  amount: MoneyMinor;
  referenceId: string;
  expiresAt: string;
  status: 'HELD' | 'CONSUMED' | 'RELEASED';
}

export function reserveCredit(credit: Credit, amountMinor: number, nowIso: string): Credit | null {
  if (credit.status !== 'ACTIVE') return null;
  if (credit.expiresAt && credit.expiresAt <= nowIso) return null;
  if (amountMinor <= 0 || amountMinor > credit.remainingAmount.amountMinor) return null;
  if (credit.remainingAmount.currency !== credit.originalAmount.currency) return null;
  return { ...credit, status: 'HELD' };
}

export function consumeHeldCredit(credit: Credit, holdAmountMinor: number): Credit | null {
  if (credit.status !== 'HELD') return null;
  const remaining = credit.remainingAmount.amountMinor - holdAmountMinor;
  if (remaining < 0) return null;
  return {
    ...credit,
    remainingAmount: { ...credit.remainingAmount, amountMinor: remaining },
    status: remaining === 0 ? 'EXHAUSTED' : 'ACTIVE',
  };
}
