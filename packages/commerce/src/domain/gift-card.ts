import { MoneyMinor } from './credit';

export type GiftCardStatus = 'ISSUED' | 'ACTIVE' | 'PARTIALLY_USED' | 'EXHAUSTED' | 'EXPIRED' | 'CANCELLED';

export interface GiftCard {
  id: string;
  publicReference: string;
  codeHash: string;
  currency: string;
  originalValue: MoneyMinor;
  remainingValue: MoneyMinor;
  status: GiftCardStatus;
  issuedAt: string;
  expiresAt?: string;
  recipientId?: string;
}

export interface GiftCardTransaction {
  id: string;
  giftCardId: string;
  type: 'ISSUANCE' | 'ACTIVATION' | 'REDEMPTION' | 'REVERSAL' | 'EXPIRATION' | 'CANCELLATION';
  amountMinor: number;
  idempotencyKey: string;
  createdAt: string;
}

export function redeemGiftCard(card: GiftCard, amountMinor: number, nowIso: string): GiftCard | null {
  if (card.status !== 'ACTIVE' && card.status !== 'PARTIALLY_USED') return null;
  if (card.expiresAt && card.expiresAt <= nowIso) return null;
  if (amountMinor <= 0 || amountMinor > card.remainingValue.amountMinor) return null;
  const remaining = card.remainingValue.amountMinor - amountMinor;
  return {
    ...card,
    remainingValue: { ...card.remainingValue, amountMinor: remaining },
    status: remaining === 0 ? 'EXHAUSTED' : 'PARTIALLY_USED',
  };
}
