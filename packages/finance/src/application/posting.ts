import { JournalEntry, isBalanced } from '../domain/ledger';

export interface PostingProposal {
  sourceEventId: string;
  policyVersion: string;
  entry: JournalEntry;
}

// Idempotent posting: same source event never posts twice.
export async function postJournalEntry(
  existing: JournalEntry[],
  proposal: PostingProposal,
  periodStatus: 'OPEN' | 'SOFT_CLOSED' | 'CLOSED' | 'LOCKED',
): Promise<{ ok: true; entry: JournalEntry } | { ok: false; reason: string }> {
  if (existing.some((e) => e.source.type === proposal.entry.source.type && e.source.id === proposal.entry.source.id)) {
    return { ok: false, reason: 'DUPLICATE_SOURCE_POSTING' };
  }
  if (!isBalanced(proposal.entry)) return { ok: false, reason: 'UNBALANCED_ENTRY' };
  if (periodStatus !== 'OPEN') return { ok: false, reason: 'PERIOD_CLOSED' };
  return { ok: true, entry: { ...proposal.entry, status: 'POSTED', postedAt: new Date().toISOString() } };
}
