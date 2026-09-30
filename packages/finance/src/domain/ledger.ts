import { MoneyMinor } from './money';

export type AccountType = 'ASSET' | 'LIABILITY' | 'EQUITY' | 'REVENUE' | 'EXPENSE';

export interface LedgerAccount {
  id: string;
  code: string;
  name: string;
  type: AccountType;
  parentId?: string;
  status: 'ACTIVE' | 'INACTIVE';
}

export type JournalStatus = 'DRAFT' | 'POSTED' | 'REVERSED';

export interface JournalLine {
  id: string;
  journalEntryId: string;
  accountId: string;
  direction: 'DEBIT' | 'CREDIT';
  amountMinor: number;
  currency: string;
}

export interface JournalEntry {
  id: string;
  entryNumber: string;
  occurredAt: string;
  postedAt?: string;
  description: string;
  source: { type: string; id: string };
  policyVersion: string;
  periodId: string;
  status: JournalStatus;
  lines: JournalLine[];
  reversedBy?: string;
}

export interface JournalReversal {
  originalEntryId: string;
  reversalEntryId: string;
  reasonCode: string;
  createdAt: string;
  createdBy: string;
}

export function journalTotals(entry: Pick<JournalEntry, 'lines'>): { debits: number; credits: number } {
  let debits = 0;
  let credits = 0;
  for (const line of entry.lines) {
    if (line.direction === 'DEBIT') debits += line.amountMinor;
    else credits += line.amountMinor;
  }
  return { debits, credits };
}

export function isBalanced(entry: Pick<JournalEntry, 'lines'>): boolean {
  if (entry.lines.length === 0) return false;
  const { debits, credits } = journalTotals(entry);
  return debits === credits && debits > 0;
}

export function validatePosting(
  entry: JournalEntry,
  periodStatus: 'OPEN' | 'SOFT_CLOSED' | 'CLOSED' | 'LOCKED',
): string | null {
  if (entry.status !== 'DRAFT') return 'ENTRY_NOT_DRAFT';
  if (!isBalanced(entry)) return 'UNBALANCED_ENTRY';
  const currency = entry.lines[0]?.currency;
  if (!currency || entry.lines.some((l) => l.currency !== currency)) return 'CURRENCY_MISMATCH';
  if (periodStatus !== 'OPEN') return 'PERIOD_CLOSED';
  return null;
}
