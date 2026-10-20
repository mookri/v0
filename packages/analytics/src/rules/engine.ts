// The small idempotent rule engine (plan B6): trigger → guards → immediate
// fire or windowed schedule → authoritative evaluation at due time →
// idempotent dispatch. Every decision is persisted with its evidence.
//
// Decision order on a trigger (documented because replays depend on it):
//   1. idempotency  — same (rule, version, subject, event IDs) already
//      recorded → duplicate_detected, no mutation (replays never re-fire)
//   2. consent      — required consent missing on the triggering event
//   3. frequency    — cooldown, then daily cap
//   4. window       — delayMs > 0 → pending_window (sweep evaluates later)
//   5. fire         — authoritative check first when the rule declares one
//
// Suppressed decisions are persisted too: "why didn't X fire?" must be
// answerable from the records a year later (evaluation criterion).

import type { SignalEnvelope } from '@navdhya/analytics/src/contracts/envelope';
import {
  buildActionRequestId,
  type RuleActionRecord,
  type RuleDecision,
  type RuleDefinition,
} from './contract';
import type { RuleActionStore } from './store';

/** Injected port resolving a rule's authoritativeCheck label against the
 *  real business system (driver-gated). `conclusive: false` means the check
 *  could not answer — the engine then fires (never silently blocks on an
 *  unknown state) and records why. */
export interface AuthoritativeCheckPort {
  check(input: {
    rule: RuleDefinition;
    record: Pick<RuleActionRecord, 'subjectKey' | 'requestedAtMs'>;
  }): Promise<{ suppress: boolean; conclusive: boolean; evidence: string }>;
}

export interface RuleEngineDeps {
  store: RuleActionStore;
  nowMs: () => number;
  authoritative: AuthoritativeCheckPort;
}

export type EngineOutcome =
  | RuleDecision
  | 'no_subject' // no derivable subject key — rule does not apply
  | 'cancel_no_match' // cancel event arrived with nothing pending
  | 'rule_missing'; // due record whose definition no longer exists

export interface EngineDecision {
  outcome: EngineOutcome;
  actionRequestId: string | null;
  /** The persisted record behind this decision (null for no_subject). */
  record: RuleActionRecord | null;
  evidence: string;
}

function iso(ms: number): string {
  return new Date(ms).toISOString();
}

function startOfUtcDay(ms: number): number {
  const date = new Date(ms);
  return Date.UTC(date.getUTCFullYear(), date.getUTCMonth(), date.getUTCDate());
}

export class RuleEngine {
  constructor(private readonly deps: RuleEngineDeps) {}

  async handleTrigger(rule: RuleDefinition, event: SignalEnvelope): Promise<EngineDecision> {
    if (event.name !== rule.triggerEvent) {
      throw new Error(`rule ${rule.id} expects trigger event ${rule.triggerEvent}, got ${event.name}`);
    }

    const subjectKey = rule.subjectKey(event);
    if (subjectKey === null) {
      return { outcome: 'no_subject', actionRequestId: null, record: null, evidence: 'no derivable subject key' };
    }

    const nowMs = this.deps.nowMs();
    const actionRequestId = buildActionRequestId(rule, subjectKey, [event.id]);

    // 1 ─ idempotency: a replay of the same triggering event is a duplicate.
    const existing = await this.deps.store.get(actionRequestId);
    if (existing) {
      return {
        outcome: 'duplicate_detected',
        actionRequestId,
        record: existing,
        evidence: `replay of trigger ${event.id}; first decision was ${existing.decision}`,
      };
    }

    const base: RuleActionRecord = {
      actionRequestId,
      ruleId: rule.id,
      ruleVersion: rule.version,
      subjectKey,
      triggeringEventIds: [event.id],
      decision: 'fired',
      evidence: '',
      requestedAtMs: nowMs,
      evaluateAtMs: null,
      decidedAtMs: null,
      deliveryStatus: 'none',
      deliveredAtMs: null,
      attempts: 0,
    };

    // 2 ─ consent gate (on the triggering event's own consent state).
    if (rule.requiresConsent !== null && !event.consent[rule.requiresConsent]) {
      const record: RuleActionRecord = {
        ...base,
        decision: 'suppressed_consent',
        decidedAtMs: nowMs,
        evidence: `consent '${rule.requiresConsent}' not granted on trigger ${event.id}`,
      };
      await this.deps.store.put(record);
      return { outcome: 'suppressed_consent', actionRequestId, record, evidence: record.evidence };
    }

    // 3 ─ frequency: cooldown first, then the daily cap.
    if (rule.cooldownMs > 0) {
      const recent = await this.deps.store.listFired(rule.id, subjectKey, nowMs - rule.cooldownMs);
      if (recent.length > 0) {
        const record: RuleActionRecord = {
          ...base,
          decision: 'suppressed_cooldown',
          decidedAtMs: nowMs,
          evidence: `fired ${recent.length}× inside cooldown ${rule.cooldownMs}ms (latest ${iso(recent[0]!.requestedAtMs)})`,
        };
        await this.deps.store.put(record);
        return { outcome: 'suppressed_cooldown', actionRequestId, record, evidence: record.evidence };
      }
    }
    if (rule.dailyCap > 0) {
      const today = await this.deps.store.listFired(rule.id, subjectKey, startOfUtcDay(nowMs));
      if (today.length >= rule.dailyCap) {
        const record: RuleActionRecord = {
          ...base,
          decision: 'suppressed_daily_cap',
          decidedAtMs: nowMs,
          evidence: `daily cap ${rule.dailyCap} reached for subject (fired ${today.length}× today)`,
        };
        await this.deps.store.put(record);
        return { outcome: 'suppressed_daily_cap', actionRequestId, record, evidence: record.evidence };
      }
    }

    // 4 ─ windowed rules are scheduled; the sweep evaluates them when due.
    if (rule.delayMs > 0) {
      const record: RuleActionRecord = {
        ...base,
        decision: 'pending_window',
        evaluateAtMs: nowMs + rule.delayMs,
        evidence: `windowed ${rule.delayMs}ms from trigger ${event.id}; due ${iso(nowMs + rule.delayMs)}`,
      };
      await this.deps.store.put(record);
      return { outcome: 'pending_window', actionRequestId, record, evidence: record.evidence };
    }

    // 5 ─ immediate fire; authoritative check first when the rule declares one.
    const fired: RuleActionRecord = {
      ...base,
      decision: 'fired',
      decidedAtMs: nowMs,
      deliveryStatus: 'pending',
      evidence: `trigger ${event.id} passed consent+frequency gates; action ${rule.action.kind}`,
    };
    if (rule.authoritativeCheck !== null) {
      const check = await this.deps.authoritative.check({ rule, record: fired });
      if (check.conclusive && check.suppress) {
        const record: RuleActionRecord = {
          ...base,
          decision: 'suppressed_authoritative',
          decidedAtMs: nowMs,
          evidence: `authoritative check '${rule.authoritativeCheck}': ${check.evidence}`,
        };
        await this.deps.store.put(record);
        return { outcome: 'suppressed_authoritative', actionRequestId, record, evidence: record.evidence };
      }
      fired.evidence += `; authoritative '${rule.authoritativeCheck}': ${check.evidence}`;
    }
    await this.deps.store.put(fired);
    return { outcome: 'fired', actionRequestId, record: fired, evidence: fired.evidence };
  }

  /** Opportunistic cancel: a cancel event for a subject with a pending
   *  windowed action suppresses it immediately (the authoritative check at
   *  due time remains the robust path when correlation is impossible). */
  async handleCancel(rule: RuleDefinition, event: SignalEnvelope): Promise<EngineDecision> {
    if (rule.cancelEvent === null || event.name !== rule.cancelEvent) {
      throw new Error(`rule ${rule.id} has no cancel event; got ${event.name}`);
    }
    const subjectKey = rule.subjectKey(event);
    if (subjectKey === null) {
      return { outcome: 'no_subject', actionRequestId: null, record: null, evidence: 'no derivable subject key' };
    }

    const pending = await this.deps.store.listPending(rule.id, subjectKey);
    if (pending.length === 0) {
      return {
        outcome: 'cancel_no_match',
        actionRequestId: null,
        record: null,
        evidence: `cancel event ${event.id} matched no pending action for ${subjectKey}`,
      };
    }

    const nowMs = this.deps.nowMs();
    for (const record of pending) {
      const updated: RuleActionRecord = {
        ...record,
        decision: 'suppressed_cancel',
        decidedAtMs: nowMs,
        deliveryStatus: 'none',
        evidence: `cancel event ${event.id} (${event.name}) matched subject ${subjectKey} within window`,
      };
      await this.deps.store.update(updated);
    }
    return {
      outcome: 'suppressed_cancel',
      actionRequestId: pending[0]!.actionRequestId,
      record: pending[0]!,
      evidence: `suppressed ${pending.length} pending action(s) via cancel event ${event.id}`,
    };
  }

  /** Due sweep (the Cloud Tasks shape): evaluate every due windowed record
   *  against its authoritative check and fire or suppress it. */
  async sweepDue(
    rules: readonly RuleDefinition[],
    nowMs?: number,
  ): Promise<EngineDecision[]> {
    const at = nowMs ?? this.deps.nowMs();
    const due = await this.deps.store.listPendingDue(at);
    const byId = new Map(rules.map((rule) => [rule.id, rule]));
    const decisions: EngineDecision[] = [];

    for (const record of due) {
      const rule = byId.get(record.ruleId);
      if (!rule) {
        decisions.push({
          outcome: 'rule_missing',
          actionRequestId: record.actionRequestId,
          record,
          evidence: `record due since ${iso(record.evaluateAtMs ?? at)} but rule ${record.ruleId}@v${record.ruleVersion} is not loaded — left for triage`,
        });
        continue;
      }

      let evidence = `window elapsed at ${iso(at)} for trigger(s) ${record.triggeringEventIds.join(', ')}`;
      let decision: RuleDecision = 'fired';
      let deliveryStatus: RuleActionRecord['deliveryStatus'] = 'pending';

      if (rule.authoritativeCheck !== null) {
        const check = await this.deps.authoritative.check({ rule, record });
        evidence += `; authoritative '${rule.authoritativeCheck}': ${check.evidence}`;
        if (check.conclusive && check.suppress) {
          decision = 'suppressed_authoritative';
          deliveryStatus = 'none';
        }
      } else {
        evidence += '; no authoritative check required';
      }

      const updated: RuleActionRecord = {
        ...record,
        decision,
        deliveryStatus,
        decidedAtMs: at,
        evidence,
      };
      await this.deps.store.update(updated);
      decisions.push({ outcome: decision, actionRequestId: updated.actionRequestId, record: updated, evidence });
    }

    return decisions;
  }
}
