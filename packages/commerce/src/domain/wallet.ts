import { MoneyMinor } from './credit';

export type WalletStatus = 'ACTIVE' | 'LOCKED' | 'CLOSED';
export type WalletEntryType = 'CREDIT' | 'DEBIT' | 'HOLD' | 'RELEASE' | 'ADJUSTMENT';

export interface WalletEntry {
  id: string;
  walletId: string;
  type: WalletEntryType;
  amount: MoneyMinor;
  referenceType?: string;
  referenceId?: string;
  createdAt: string;
}

export interface Wallet {
  id: string;
  ownerId: string;
  currency: string;
  status: WalletStatus;
}

export function rebuildWalletBalance(entries: WalletEntry[], currency: string): { available: number; held: number } {
  let available = 0;
  let held = 0;
  for (const e of entries) {
    if (e.amount.currency !== currency) continue;
    switch (e.type) {
      case 'CREDIT':
      case 'RELEASE':
        available += e.amount.amountMinor;
        break;
      case 'DEBIT':
        available -= e.amount.amountMinor;
        break;
      case 'HOLD':
        available -= e.amount.amountMinor;
        held += e.amount.amountMinor;
        break;
      case 'ADJUSTMENT':
        available += e.amount.amountMinor;
        break;
    }
  }
  return { available, held };
}

export function canSpend(balance: { available: number }, amountMinor: number): boolean {
  return amountMinor > 0 && amountMinor <= balance.available;
}
