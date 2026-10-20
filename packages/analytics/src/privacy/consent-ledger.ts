// Consent ledger (plan B7, acceptance bullet 4): the authoritative record of
// consent WITHDRAWALS for fan-out suppression. Per-event consent rides the
// envelope (B3/B4 gates); this ledger is the subject-level backstop — once a
// withdrawal lands, every fan-out boundary refuses new sends for that
// (subject, purpose) regardless of what a client claims later.
//
// Honesty notes:
//   - The guard returns a result object (never a bare boolean, never a
//     thrown error): callers must surface WHY fan-out was blocked.
//   - Withdrawals are recorded with the withdrawal timestamp + request ID
//     so the audit trail can prove WHEN suppression started.

export type ConsentPurpose = 'analytics' | 'ads';

/** SLO: a processed consent withdrawal must block fan-out within this
 *  window (plan B7 acceptance bullet 4). */
export const WITHDRAWAL_BLOCK_SLO_MS = 15 * 60 * 1000;

export interface ConsentWithdrawal {
  subjectHash: string;
  purpose: ConsentPurpose;
  withdrawnAtMs: number;
  requestId: string;
}

export interface ConsentLedgerStore {
  withdraw(withdrawal: ConsentWithdrawal): Promise<void>;
  /** Null when no withdrawal is recorded (consent not withdrawn via ledger). */
  get(subjectHash: string, purpose: ConsentPurpose): Promise<ConsentWithdrawal | null>;
  /** Every withdrawal for a subject (audit view). */
  list(subjectHash: string): Promise<ConsentWithdrawal[]>;
}

export class MemoryConsentLedger implements ConsentLedgerStore {
  private readonly rows = new Map<string, ConsentWithdrawal>();

  private key(subjectHash: string, purpose: ConsentPurpose): string {
    return `${subjectHash}::${purpose}`;
  }

  async withdraw(withdrawal: ConsentWithdrawal): Promise<void> {
    // First withdrawal wins — later repeats do not rewrite history.
    const key = this.key(withdrawal.subjectHash, withdrawal.purpose);
    if (!this.rows.has(key)) this.rows.set(key, { ...withdrawal });
  }

  async get(subjectHash: string, purpose: ConsentPurpose): Promise<ConsentWithdrawal | null> {
    return this.rows.get(this.key(subjectHash, purpose)) ?? null;
  }

  async list(subjectHash: string): Promise<ConsentWithdrawal[]> {
    return [...this.rows.values()].filter((row) => row.subjectHash === subjectHash);
  }
}

export interface FanoutGuardResult {
  ok: boolean;
  reason: string;
}

/** The fan-out boundary check: a withdrawn (subject, purpose) may never be
 *  messaged/measured again. Consent-granted evidence from an old event does
 *  NOT override a ledger withdrawal — the ledger is authoritative. */
export async function assertFanoutAllowed(
  ledger: ConsentLedgerStore,
  subjectHash: string,
  purpose: ConsentPurpose,
): Promise<FanoutGuardResult> {
  const withdrawal = await ledger.get(subjectHash, purpose);
  if (withdrawal) {
    return {
      ok: false,
      reason: `consent '${purpose}' withdrawn for subject at ${new Date(withdrawal.withdrawnAtMs).toISOString()} (request ${withdrawal.requestId}) — fan-out blocked`,
    };
  }
  return { ok: true, reason: 'no withdrawal on record' };
}
