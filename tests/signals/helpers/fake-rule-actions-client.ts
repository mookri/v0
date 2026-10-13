// Statement-level fake for the B6 rule-actions path. It understands EXACTLY
// the SQL shapes PostgresRuleActionStore issues (same posture as the B2
// fake-pg-client): insert-on-conflict, decision transitions, delivery
// markers, and the three list queries. If the real SQL drifts, the shape
// assertions in the tests fail here first. Anything unrecognized throws.

import type { DbClient } from '@infra/database/postgres/client';

export interface FakeRuleActionRow {
  action_request_id: string;
  rule_id: string;
  rule_version: number;
  subject_key: string;
  triggering_event_ids: string;
  decision: string;
  evidence: string;
  requested_at_ms: number;
  evaluate_at_ms: number | null;
  decided_at_ms: number | null;
  delivery_status: string;
  delivered_at_ms: number | null;
  attempts: number;
}

export class FakeRuleActionsClient implements DbClient {
  readonly rows = new Map<string, FakeRuleActionRow>();

  async query<T>(text: string, params: unknown[] = []): Promise<T[]> {
    const sql = text.replace(/\s+/g, ' ').trim();

    if (sql.startsWith('INSERT INTO rule_actions')) {
      const [
        actionRequestId,
        ruleId,
        ruleVersion,
        subjectKey,
        idsJson,
        decision,
        evidence,
        requestedAtMs,
        evaluateAtMs,
        decidedAtMs,
        deliveryStatus,
        deliveredAtMs,
        attempts,
      ] = params as [
        string,
        string,
        number,
        string,
        string,
        string,
        string,
        number,
        number | null,
        number | null,
        string,
        number | null,
        number,
      ];
      if (this.rows.has(actionRequestId)) return []; // ON CONFLICT DO NOTHING
      this.rows.set(actionRequestId, {
        action_request_id: actionRequestId,
        rule_id: ruleId,
        rule_version: ruleVersion,
        subject_key: subjectKey,
        triggering_event_ids: idsJson,
        decision,
        evidence,
        requested_at_ms: requestedAtMs,
        evaluate_at_ms: evaluateAtMs,
        decided_at_ms: decidedAtMs,
        delivery_status: deliveryStatus,
        delivered_at_ms: deliveredAtMs,
        attempts,
      });
      return [{ action_request_id: actionRequestId }] as T[];
    }

    if (sql.startsWith(`UPDATE rule_actions SET delivery_status = 'delivered'`)) {
      const [id, atMs] = params as [string, number];
      const row = this.mustGet(id);
      row.delivery_status = 'delivered';
      row.delivered_at_ms = atMs;
      row.attempts += 1;
      return [];
    }

    if (sql.startsWith(`UPDATE rule_actions SET delivery_status = 'failed'`)) {
      const [id] = params as [string];
      const row = this.mustGet(id);
      row.delivery_status = 'failed';
      row.attempts += 1;
      return [];
    }

    if (sql.startsWith('UPDATE rule_actions SET decision')) {
      const [id, decision, evidence, evaluateAtMs, decidedAtMs, deliveryStatus, deliveredAtMs, attempts] =
        params as [string, string, string, number | null, number | null, string, number | null, number];
      const row = this.mustGet(id);
      row.decision = decision;
      row.evidence = evidence;
      row.evaluate_at_ms = evaluateAtMs;
      row.decided_at_ms = decidedAtMs;
      row.delivery_status = deliveryStatus;
      row.delivered_at_ms = deliveredAtMs;
      row.attempts = attempts;
      return [];
    }

    if (sql.startsWith('SELECT')) {
      if (sql.includes('WHERE action_request_id = $1')) {
        const row = this.rows.get(params[0] as string);
        return (row ? [{ ...row }] : []) as T[];
      }
      if (sql.includes('evaluate_at_ms <=')) {
        const [nowMs] = params as [number];
        return [...this.rows.values()]
          .filter(
            (row) =>
              row.decision === 'pending_window' &&
              row.evaluate_at_ms !== null &&
              row.evaluate_at_ms <= nowMs,
          )
          .sort((a, b) => a.evaluate_at_ms! - b.evaluate_at_ms!)
          .slice(0, 100)
          .map((row) => ({ ...row })) as T[];
      }
      if (sql.includes(`decision = 'fired'`)) {
        const [ruleId, subjectKey, sinceMs] = params as [string, string, number];
        return [...this.rows.values()]
          .filter(
            (row) =>
              row.rule_id === ruleId &&
              row.subject_key === subjectKey &&
              row.decision === 'fired' &&
              row.requested_at_ms >= sinceMs,
          )
          .sort((a, b) => a.requested_at_ms - b.requested_at_ms)
          .map((row) => ({ ...row })) as T[];
      }
      if (sql.includes(`decision = 'pending_window'`)) {
        const [ruleId, subjectKey] = params as [string, string];
        return [...this.rows.values()]
          .filter(
            (row) =>
              row.rule_id === ruleId &&
              row.subject_key === subjectKey &&
              row.decision === 'pending_window',
          )
          .sort((a, b) => (a.evaluate_at_ms ?? 0) - (b.evaluate_at_ms ?? 0))
          .map((row) => ({ ...row })) as T[];
      }
    }

    throw new Error(`FakeRuleActionsClient: unrecognized SQL shape: ${sql.slice(0, 140)}`);
  }

  private mustGet(id: string): FakeRuleActionRow {
    const row = this.rows.get(id);
    if (!row) throw new Error(`FakeRuleActionsClient: row not found: ${id}`);
    return row;
  }
}
