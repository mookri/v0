// Rule action store (plan B6 bullet 1: "persisted per action") + the
// idempotent dispatch boundary (acceptance: an action request retried to the
// FCM/outbox boundary produces the downstream effect exactly once).
//
// Delivery honesty (same stance as B2's outbox): a crash between a successful
// `send` and `markDelivered` can re-send — that is the at-least-once window,
// and it is visible (`attempts` climbs) rather than hidden. Once a record is
// marked `delivered`, any retry is detected and skipped without an effect.

import type { RuleActionRecord } from './contract';

export interface RuleActionStore {
  /** Null when the idempotency key was never seen. */
  get(actionRequestId: string): Promise<RuleActionRecord | null>;
  /** Idempotent insert (first record for an id wins — Postgres
   *  ON CONFLICT DO NOTHING semantics). */
  put(record: RuleActionRecord): Promise<'inserted' | 'duplicate'>;
  /** Decision transition for an existing record (pending → fired/cancel/…). */
  update(record: RuleActionRecord): Promise<void>;
  markDelivered(actionRequestId: string, atMs: number): Promise<void>;
  markFailed(actionRequestId: string, atMs: number): Promise<void>;
  /** Fired records for (rule, subject) since `sinceMs` — cooldown + cap. */
  listFired(ruleId: string, subjectKey: string, sinceMs: number): Promise<RuleActionRecord[]>;
  /** Windowed records for (rule, subject) still awaiting evaluation. */
  listPending(ruleId: string, subjectKey: string): Promise<RuleActionRecord[]>;
  /** Due windowed records across all rules — the sweep's work queue. */
  listPendingDue(nowMs: number): Promise<RuleActionRecord[]>;
}

export class MemoryRuleActionStore implements RuleActionStore {
  private readonly rows = new Map<string, RuleActionRecord>();

  async get(actionRequestId: string): Promise<RuleActionRecord | null> {
    return this.rows.get(actionRequestId) ?? null;
  }

  async put(record: RuleActionRecord): Promise<'inserted' | 'duplicate'> {
    if (this.rows.has(record.actionRequestId)) return 'duplicate';
    this.rows.set(record.actionRequestId, { ...record });
    return 'inserted';
  }

  async update(record: RuleActionRecord): Promise<void> {
    if (!this.rows.has(record.actionRequestId)) {
      throw new Error(`rule action not found: ${record.actionRequestId}`);
    }
    this.rows.set(record.actionRequestId, { ...record });
  }

  async markDelivered(actionRequestId: string, atMs: number): Promise<void> {
    const record = this.rows.get(actionRequestId);
    if (!record) throw new Error(`rule action not found: ${actionRequestId}`);
    record.deliveryStatus = 'delivered';
    record.deliveredAtMs = atMs;
    record.attempts += 1;
  }

  async markFailed(actionRequestId: string, atMs: number): Promise<void> {
    const record = this.rows.get(actionRequestId);
    if (!record) throw new Error(`rule action not found: ${actionRequestId}`);
    record.deliveryStatus = 'failed';
    record.attempts += 1;
    void atMs;
  }

  async listFired(
    ruleId: string,
    subjectKey: string,
    sinceMs: number,
  ): Promise<RuleActionRecord[]> {
    return [...this.rows.values()].filter(
      (record) =>
        record.ruleId === ruleId &&
        record.subjectKey === subjectKey &&
        record.decision === 'fired' &&
        record.requestedAtMs >= sinceMs,
    );
  }

  async listPending(ruleId: string, subjectKey: string): Promise<RuleActionRecord[]> {
    return [...this.rows.values()].filter(
      (record) =>
        record.ruleId === ruleId &&
        record.subjectKey === subjectKey &&
        record.decision === 'pending_window',
    );
  }

  async listPendingDue(nowMs: number): Promise<RuleActionRecord[]> {
    return [...this.rows.values()].filter(
      (record) =>
        record.decision === 'pending_window' &&
        record.evaluateAtMs !== null &&
        record.evaluateAtMs <= nowMs,
    );
  }
}

export interface DispatchDeps {
  store: RuleActionStore;
  nowMs: () => number;
  /** The FCM/outbox boundary. Injected — the real sender is driver-gated. */
  send: (record: RuleActionRecord) => Promise<void>;
}

export interface DispatchOutcome {
  status: 'delivered' | 'duplicate_suppressed' | 'failed';
  /** True only when the downstream effect actually ran. */
  effectRan: boolean;
  attempts: number;
}

/**
 * Fire-and-confirm with idempotency at the boundary: a record already marked
 * `delivered` (e.g. a retried Cloud Tasks request) returns
 * `duplicate_suppressed` WITHOUT invoking `send` — the acceptance property
 * "retried to the FCM/outbox boundary → downstream effect occurs once".
 */
export async function dispatchAction(
  deps: DispatchDeps,
  actionRequestId: string,
): Promise<DispatchOutcome> {
  const record = await deps.store.get(actionRequestId);
  if (!record) throw new Error(`rule action not found: ${actionRequestId}`);
  if (record.decision !== 'fired') {
    return { status: 'duplicate_suppressed', effectRan: false, attempts: record.attempts };
  }
  if (record.deliveryStatus === 'delivered') {
    return { status: 'duplicate_suppressed', effectRan: false, attempts: record.attempts };
  }

  try {
    await deps.send(record);
    await deps.store.markDelivered(actionRequestId, deps.nowMs());
    return { status: 'delivered', effectRan: true, attempts: record.attempts + 1 };
  } catch {
    await deps.store.markFailed(actionRequestId, deps.nowMs());
    return { status: 'failed', effectRan: false, attempts: record.attempts + 1 };
  }
}
