import { MoneyMinor, assertSameCurrency } from './money';

export type ReceivableStatus = 'OPEN' | 'PARTIALLY_SETTLED' | 'SETTLED' | 'OVERDUE' | 'WRITTEN_OFF' | 'DISPUTED' | 'CANCELLED';

export interface Receivable {
  id: string;
  customerId?: string;
  invoiceId: string;
  originalAmount: MoneyMinor;
  outstandingAmount: MoneyMinor;
  status: ReceivableStatus;
  dueAt?: string;
}

export interface PaymentAllocation {
  paymentId: string;
  receivableId: string;
  amount: MoneyMinor;
  allocatedAt: string;
}

export function allocatePayment(receivable: Receivable, paymentId: string, amount: MoneyMinor, nowIso: string): Receivable | null {
  assertSameCurrency(receivable.outstandingAmount, amount);
  if (amount.amountMinor <= 0 || amount.amountMinor > receivable.outstandingAmount.amountMinor) return null;
  const outstanding = receivable.outstandingAmount.amountMinor - amount.amountMinor;
  void paymentId;
  return {
    ...receivable,
    outstandingAmount: { ...receivable.outstandingAmount, amountMinor: outstanding },
    status: outstanding === 0 ? 'SETTLED' : 'PARTIALLY_SETTLED',
  };
}
