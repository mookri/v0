import { MoneyMinor, assertSameCurrency } from './money';

export type PayableStatus = 'PENDING' | 'APPROVED' | 'ON_HOLD' | 'PAID' | 'FAILED' | 'REVERSED';

export interface PartnerPayable {
  id: string;
  partnerId: string;
  settlementId: string;
  amount: MoneyMinor;
  status: PayableStatus;
  holdReason?: string;
  agreementVersion: string;
}

export function holdPayable(payable: PartnerPayable, reason: string): PartnerPayable | null {
  if (payable.status !== 'APPROVED' && payable.status !== 'PENDING') return null;
  return { ...payable, status: 'ON_HOLD', holdReason: reason };
}

export function releasePayable(payable: PartnerPayable): PartnerPayable | null {
  if (payable.status !== 'ON_HOLD') return null;
  const { holdReason, ...rest } = payable;
  void holdReason;
  return { ...rest, status: 'APPROVED' };
}

export function validatePayout(payable: PartnerPayable, amount: MoneyMinor, partnerId: string): string | null {
  if (payable.partnerId !== partnerId) return 'PARTNER_MISMATCH';
  assertSameCurrency(payable.amount, amount);
  if (payable.status !== 'APPROVED') return 'PAYABLE_NOT_APPROVED';
  if (amount.amountMinor > payable.amount.amountMinor) return 'PAYOUT_OVERRUN';
  return null;
}
