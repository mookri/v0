import { test, expect } from 'vitest';
import { validatePosting, journalTotals } from '../../packages/finance/src/domain/ledger';
import { invoiceTotals } from '../../packages/finance/src/domain/invoice';
import { allocatePayment } from '../../packages/finance/src/domain/receivable';
import { holdPayable, releasePayable, validatePayout } from '../../packages/finance/src/domain/payable';
import { canRefund, consumeCredit, cumulativeRefunded } from '../../packages/finance/src/domain/refund';
import { reconcileAmounts } from '../../packages/finance/src/domain/reconciliation';
import { postJournalEntry } from '../../packages/finance/src/application/posting';

const INR = 'INR';
const m = (amountMinor: number) => ({ amountMinor, currency: INR });

const balancedEntry = (id: string, sourceId: string) => ({
  id, entryNumber: `JE-${id}`, occurredAt: '2026-09-30T00:00:00.000Z',
  description: 'Booking payment captured', source: { type: 'PaymentCaptured', id: sourceId },
  policyVersion: 'posting-v1', periodId: 'p1', status: 'DRAFT' as const,
  lines: [
    { id: 'l1', journalEntryId: id, accountId: 'cash', direction: 'DEBIT' as const, amountMinor: 118000, currency: INR },
    { id: 'l2', journalEntryId: id, accountId: 'revenue', direction: 'CREDIT' as const, amountMinor: 118000, currency: INR },
  ],
});

test('ledger invariant: debits must equal credits', () => {
  expect(journalTotals(balancedEntry('e1', 's1'))).toEqual({ debits: 118000, credits: 118000 });
  expect(validatePosting({ ...balancedEntry('e1', 's1') }, 'OPEN')).toBeNull();
  const unbalanced = balancedEntry('e2', 's2');
  unbalanced.lines[1]!.amountMinor = 1;
  expect(validatePosting(unbalanced, 'OPEN')).toBe('UNBALANCED_ENTRY');
  expect(validatePosting({ ...balancedEntry('e3', 's3'), status: 'POSTED' }, 'OPEN')).toBe('ENTRY_NOT_DRAFT');
  expect(validatePosting(balancedEntry('e4', 's4'), 'CLOSED')).toBe('PERIOD_CLOSED');
});

test('posting is idempotent per source event', async () => {
  const existing = [{ ...balancedEntry('e1', 'evt1'), status: 'POSTED' as const }];
  expect(await postJournalEntry(existing, { sourceEventId: 'evt1', policyVersion: 'v1', entry: balancedEntry('e2', 'evt1') }, 'OPEN'))
    .toEqual({ ok: false, reason: 'DUPLICATE_SOURCE_POSTING' });
  const fresh = await postJournalEntry([], { sourceEventId: 'evt2', policyVersion: 'v1', entry: balancedEntry('e3', 'evt2') }, 'OPEN');
  expect(fresh.ok).toBe(true);
});

test('invoice totals derive from lines, never edited totals', () => {
  const lines = [{
    id: 'l1', invoiceId: 'i', description: 'Puja', quantity: 1,
    unitAmount: m(100000), lineSubtotal: m(100000), discountAmount: m(-10000),
    taxAmount: m(16200), lineTotal: m(106200), sourceType: 'quote', sourceId: 'q',
  }];
  const totals = invoiceTotals(lines, INR);
  expect(totals.total.amountMinor).toBe(106200);
});

test('receivable allocation never over-applies', () => {
  const rec = { id: 'r', invoiceId: 'i', originalAmount: m(118000), outstandingAmount: m(118000), status: 'OPEN' as const };
  expect(allocatePayment(rec, 'pay1', m(50000), '2026-09-30T00:00:00.000Z')?.status).toBe('PARTIALLY_SETTLED');
  expect(allocatePayment(rec, 'pay1', m(200000), '2026-09-30T00:00:00.000Z')).toBeNull();
});

test('payables hold/release with ownership and approval gates', () => {
  const payable = { id: 'p', partnerId: 'ptr', settlementId: 's', amount: m(80000), status: 'APPROVED' as const, agreementVersion: 'v2' };
  expect(holdPayable(payable, 'dispute')?.status).toBe('ON_HOLD');
  expect(holdPayable({ ...payable, status: 'PAID' as const }, 'x')).toBeNull();
  expect(releasePayable({ ...payable, status: 'ON_HOLD' as const })).not.toBeNull();
  expect(validatePayout(payable, m(80000), 'ptr')).toBeNull();
  expect(validatePayout(payable, m(80000), 'other')).toBe('PARTNER_MISMATCH');
  expect(validatePayout(payable, m(90000), 'ptr')).toBe('PAYOUT_OVERRUN');
});

test('refunds guard cumulative totals; credits consume atomically', () => {
  const done = [{ id: 'r1', paymentId: 'p', requestedAmount: m(18000), reasonCode: 'x', status: 'COMPLETED' as const, idempotencyKey: 'k', createdAt: '2026-09-30T00:00:00.000Z' }];
  expect(cumulativeRefunded(done)).toBe(18000);
  const req = { id: 'r2', paymentId: 'p', requestedAmount: m(200000), reasonCode: 'x', status: 'REQUESTED' as const, idempotencyKey: 'k2', createdAt: '2026-09-30T00:00:00.000Z' };
  expect(canRefund(req, 118000, done, INR)).toBe('REFUND_OVERRUN');
  expect(canRefund({ ...req, requestedAmount: m(100000) }, 118000, done, INR)).toBeNull();
  const credit = { id: 'c', customerId: 'u', sourceType: 'refund', sourceId: 'r', amount: m(50000), remaining: m(50000), status: 'ACTIVE' as const };
  expect(consumeCredit(credit, 50000, '2026-09-30T00:00:00.000Z')?.status).toBe('EXHAUSTED');
  expect(consumeCredit(credit, 60000, '2026-09-30T00:00:00.000Z')).toBeNull();
});

test('reconciliation classifies exact, missing, and mismatched pairs', () => {
  expect(reconcileAmounts('j', m(118000), m(118000), 'v1', '2026-09-30T00:00:00.000Z').outcome).toBe('MATCHED');
  expect(reconcileAmounts('j', m(118000), m(100000), 'v1', '2026-09-30T00:00:00.000Z').outcome).toBe('MISMATCH');
  expect(reconcileAmounts('j', null, m(100000), 'v1', '2026-09-30T00:00:00.000Z').outcome).toBe('MISSING_A');
  expect(reconcileAmounts('j', m(118000), { amountMinor: 118000, currency: 'USD' }, 'v1', '2026-09-30T00:00:00.000Z').outcome).toBe('MISMATCH');
});
