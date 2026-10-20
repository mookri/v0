// Postgres RuleActionStore (plan B6): the production persistence for rule
// decisions, implementing the same contract as the in-memory reference.
// Statement-level tested against a fake DbClient (tests/signals/
// postgres-rule-actions.test.ts) — the B2 outbox posture: no database
// credential in this repo, driver wiring lands with DATABASE_URL.
//
// Delivery-honesty alignment with dispatchAction(): markDelivered /
// markFailed bump `attempts`; a crash between a successful send and
// markDelivered can re-send (at-least-once), and the record shows it.

import type { RuleActionRecord, RuleDecision, DeliveryStatus } from '@navdhya/analytics/src/rules/contract';
import type { RuleActionStore } from '@navdhya/analytics/src/rules/store';
import type { DbClient } from '@infra/database/postgres/client';

interface RuleActionRow {
  action_request_id: string;
  rule_id: string;
  rule_version: number;
  subject_key: string;
  triggering_event_ids: unknown;
  decision: string;
  evidence: string;
  requested_at_ms: string | number;
  evaluate_at_ms: string | number | null;
  decided_at_ms: string | number | null;
  delivery_status: string;
  delivered_at_ms: string | number | null;
  attempts: number;
}

const RETURNING_COLUMNS = `action_request_id, rule_id, rule_version, subject_key,
       triggering_event_ids, decision, evidence, requested_at_ms, evaluate_at_ms,
       decided_at_ms, delivery_status, delivered_at_ms, attempts`;

function asNumber(value: string | number | null): number | null {
  return value === null ? null : Number(value);
}

function mapRow(row: RuleActionRow): RuleActionRecord {
  let ids = row.triggering_event_ids;
  if (typeof ids === 'string') {
    try {
      ids = JSON.parse(ids);
    } catch {
      ids = [];
    }
  }
  return {
    actionRequestId: row.action_request_id,
    ruleId: row.rule_id,
    ruleVersion: row.rule_version,
    subjectKey: row.subject_key,
    triggeringEventIds: Array.isArray(ids) ? ids.map(String) : [],
    decision: row.decision as RuleDecision,
    evidence: row.evidence,
    requestedAtMs: Number(row.requested_at_ms),
    evaluateAtMs: asNumber(row.evaluate_at_ms),
    decidedAtMs: asNumber(row.decided_at_ms),
    deliveryStatus: row.delivery_status as DeliveryStatus,
    deliveredAtMs: asNumber(row.delivered_at_ms),
    attempts: row.attempts,
  };
}

export class PostgresRuleActionStore implements RuleActionStore {
  constructor(private readonly db: DbClient) {}

  async get(actionRequestId: string): Promise<RuleActionRecord | null> {
    const rows = await this.db.query<RuleActionRow>(
      `SELECT ${RETURNING_COLUMNS}
       FROM rule_actions
       WHERE action_request_id = $1`,
      [actionRequestId],
    );
    return rows.length > 0 ? mapRow(rows[0]!) : null;
  }

  async put(record: RuleActionRecord): Promise<'inserted' | 'duplicate'> {
    const rows = await this.db.query<RuleActionRow>(
      `INSERT INTO rule_actions (${RETURNING_COLUMNS})
       VALUES ($1, $2, $3, $4, $5, $6, $7, $8, $9, $10, $11, $12, $13)
       ON CONFLICT (action_request_id) DO NOTHING
       RETURNING action_request_id`,
      [
        record.actionRequestId,
        record.ruleId,
        record.ruleVersion,
        record.subjectKey,
        JSON.stringify(record.triggeringEventIds),
        record.decision,
        record.evidence,
        record.requestedAtMs,
        record.evaluateAtMs,
        record.decidedAtMs,
        record.deliveryStatus,
        record.deliveredAtMs,
        record.attempts,
      ],
    );
    return rows.length > 0 ? 'inserted' : 'duplicate';
  }

  async update(record: RuleActionRecord): Promise<void> {
    await this.db.query(
      `UPDATE rule_actions
       SET decision = $2, evidence = $3, evaluate_at_ms = $4, decided_at_ms = $5,
           delivery_status = $6, delivered_at_ms = $7, attempts = $8
       WHERE action_request_id = $1`,
      [
        record.actionRequestId,
        record.decision,
        record.evidence,
        record.evaluateAtMs,
        record.decidedAtMs,
        record.deliveryStatus,
        record.deliveredAtMs,
        record.attempts,
      ],
    );
  }

  async markDelivered(actionRequestId: string, atMs: number): Promise<void> {
    await this.db.query(
      `UPDATE rule_actions
       SET delivery_status = 'delivered', delivered_at_ms = $2, attempts = attempts + 1
       WHERE action_request_id = $1`,
      [actionRequestId, atMs],
    );
  }

  async markFailed(actionRequestId: string, _atMs: number): Promise<void> {
    await this.db.query(
      `UPDATE rule_actions
       SET delivery_status = 'failed', attempts = attempts + 1
       WHERE action_request_id = $1`,
      [actionRequestId],
    );
  }

  async listFired(
    ruleId: string,
    subjectKey: string,
    sinceMs: number,
  ): Promise<RuleActionRecord[]> {
    const rows = await this.db.query<RuleActionRow>(
      `SELECT ${RETURNING_COLUMNS}
       FROM rule_actions
       WHERE rule_id = $1 AND subject_key = $2 AND decision = 'fired'
         AND requested_at_ms >= $3
       ORDER BY requested_at_ms ASC`,
      [ruleId, subjectKey, sinceMs],
    );
    return rows.map(mapRow);
  }

  async listPending(ruleId: string, subjectKey: string): Promise<RuleActionRecord[]> {
    const rows = await this.db.query<RuleActionRow>(
      `SELECT ${RETURNING_COLUMNS}
       FROM rule_actions
       WHERE rule_id = $1 AND subject_key = $2 AND decision = 'pending_window'
       ORDER BY evaluate_at_ms ASC`,
      [ruleId, subjectKey],
    );
    return rows.map(mapRow);
  }

  async listPendingDue(nowMs: number): Promise<RuleActionRecord[]> {
    // Read-only claim: concurrent sweepers may both observe a due record,
    // but dispatchAction()'s delivered-status check keeps the downstream
    // effect single (the boundary is the idempotency authority).
    const rows = await this.db.query<RuleActionRow>(
      `SELECT ${RETURNING_COLUMNS}
       FROM rule_actions
       WHERE decision = 'pending_window' AND evaluate_at_ms IS NOT NULL
         AND evaluate_at_ms <= $1
       ORDER BY evaluate_at_ms ASC
       LIMIT 100`,
      [nowMs],
    );
    return rows.map(mapRow);
  }
}
