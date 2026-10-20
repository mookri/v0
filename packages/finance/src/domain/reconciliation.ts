import { MoneyMinor } from './money';

export type LedgerPeriodStatus = 'OPEN' | 'SOFT_CLOSED' | 'CLOSED' | 'LOCKED';

export interface LedgerPeriod {
  id: string;
  periodStart: string;
  periodEnd: string;
  status: LedgerPeriodStatus;
}

export function canPostToPeriod(status: LedgerPeriodStatus): boolean {
  return status === 'OPEN';
}

export type ReconciliationOutcome = 'MATCHED' | 'PARTIAL_MATCH' | 'MISMATCH' | 'MISSING_A' | 'MISSING_B' | 'DUPLICATE';

export interface ReconciliationResult {
  jobId: string;
  outcome: ReconciliationOutcome;
  amountA?: MoneyMinor;
  amountB?: MoneyMinor;
  policyVersion: string;
  evaluatedAt: string;
}

export function reconcileAmounts(
  jobId: string,
  a: MoneyMinor | null,
  b: MoneyMinor | null,
  policyVersion: string,
  nowIso: string,
): ReconciliationResult {
  if (!a) return { jobId, outcome: 'MISSING_A', amountB: b ?? undefined, policyVersion, evaluatedAt: nowIso };
  if (!b) return { jobId, outcome: 'MISSING_B', amountA: a, policyVersion, evaluatedAt: nowIso };
  if (a.currency !== b.currency) return { jobId, outcome: 'MISMATCH', amountA: a, amountB: b, policyVersion, evaluatedAt: nowIso };
  if (a.amountMinor === b.amountMinor) return { jobId, outcome: 'MATCHED', amountA: a, amountB: b, policyVersion, evaluatedAt: nowIso };
  return { jobId, outcome: 'MISMATCH', amountA: a, amountB: b, policyVersion, evaluatedAt: nowIso };
}

export type FinanceExceptionType =
  | 'PAYMENT_MISMATCH' | 'SETTLEMENT_MISMATCH' | 'INVOICE_MISMATCH'
  | 'REFUND_MISMATCH' | 'LEDGER_MISMATCH' | 'TAX_MISMATCH' | 'UNKNOWN';

export interface FinanceException {
  id: string;
  type: FinanceExceptionType;
  status: 'OPEN' | 'INVESTIGATING' | 'RESOLVED' | 'CLOSED';
  severity: 'LOW' | 'NORMAL' | 'HIGH' | 'CRITICAL';
  monetaryMinor?: number;
  currency?: string;
  openedAt: string;
}
