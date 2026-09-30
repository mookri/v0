import { MoneyMinor, assertSameCurrency } from './money';

export type RefundStatus = 'REQUESTED' | 'APPROVED' | 'SUBMITTED' | 'PROCESSING' | 'COMPLETED' | 'FAILED' | 'CANCELLED';

export interface Refund {
  id: string;
  paymentId: string;
  requestedAmount: MoneyMinor;
  approvedAmount?: MoneyMinor;
  processedAmount?: MoneyMinor;
  reasonCode: string;
  status: RefundStatus;
  idempotencyKey: string;
  createdAt: string;
}

export function cumulativeRefunded(refunds: Refund[]): number {
  return refunds
    .filter((r) => r.status === 'COMPLETED')
    .reduce((acc, r) => acc + (r.processedAmount ?? r.approvedAmount ?? r.requestedAmount).amountMinor, 0);
}

export function canRefund(refund: Refund, capturedTotalMinor: number, priorCompleted: Refund[], currency: string): string | null {
  assertSameCurrency(refund.requestedAmount, { amountMinor: 0, currency });
  const completed = priorCompleted.reduce((acc, r) => acc + (r.processedAmount ?? r.approvedAmount ?? r.requestedAmount).amountMinor, 0);
  if (refund.requestedAmount.amountMinor <= 0) return 'INVALID_AMOUNT';
  if (completed + refund.requestedAmount.amountMinor > capturedTotalMinor) return 'REFUND_OVERRUN';
  return null;
}

export type CreditStatus = 'ACTIVE' | 'EXPIRED' | 'EXHAUSTED' | 'CANCELLED';

export interface CustomerCredit {
  id: string;
  customerId: string;
  sourceType: string;
  sourceId: string;
  amount: MoneyMinor;
  remaining: MoneyMinor;
  expiresAt?: string;
  status: CreditStatus;
}

export function consumeCredit(credit: CustomerCredit, amountMinor: number, nowIso: string): CustomerCredit | null {
  if (credit.status !== 'ACTIVE') return null;
  if (credit.expiresAt && credit.expiresAt <= nowIso) return null;
  if (amountMinor <= 0 || amountMinor > credit.remaining.amountMinor) return null;
  const remaining = credit.remaining.amountMinor - amountMinor;
  return {
    ...credit,
    remaining: { ...credit.remaining, amountMinor: remaining },
    status: remaining === 0 ? 'EXHAUSTED' : 'ACTIVE',
  };
}
