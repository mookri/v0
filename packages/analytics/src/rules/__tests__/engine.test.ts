// Rule engine unit tests (plan B6): idempotent replay, consent/frequency
// guards, window semantics, authoritative suppression, dispatch-once.

import { describe, expect, it } from 'vitest';
import type { SignalEnvelope } from '@navdhya/analytics/src/contracts/envelope';
import { RuleEngine, type AuthoritativeCheckPort } from '@navdhya/analytics/src/rules/engine';
import { MemoryRuleActionStore, dispatchAction } from '@navdhya/analytics/src/rules/store';
import type { RuleDefinition } from '@navdhya/analytics/src/rules/contract';
import {
  BOOKING_CONFIRMATION_WORKFLOW,
  CHECKOUT_ABANDONMENT,
  KYC_PENDING_REMINDER,
  PAYMENT_FAILURE_RETRY_SUPPORT,
  STARTER_RULES,
} from '@navdhya/analytics/src/rules/starter-rules';

const BASE_MS = Date.parse('2026-10-13T12:00:00.000Z');

function envelope(id: string, name: string, overrides: Partial<SignalEnvelope> = {}): SignalEnvelope {
  return {
    id,
    name,
    version: 1,
    occurredAt: new Date(BASE_MS).toISOString(),
    surface: 'customer',
    appVersion: '1.0.0',
    session: { id: 'sess_1', seq: 1 },
    actor: { uidHash: null, anonId: null },
    consent: { analytics: true, ads: true },
    context: { routeTemplate: '/checkout' },
    params: {},
    ...overrides,
  };
}

function fakeAuthoritative(
  answers: Record<string, { suppress: boolean; conclusive: boolean; evidence: string }>,
): AuthoritativeCheckPort {
  return {
    check: async ({ rule }) =>
      answers[rule.authoritativeCheck ?? 'none'] ?? {
        suppress: false,
        conclusive: true,
        evidence: 'no relevant state',
      },
    };
}

function harness(rule: RuleDefinition, answers: Record<string, { suppress: boolean; conclusive: boolean; evidence: string }> = {}) {
  const store = new MemoryRuleActionStore();
  let nowMs = BASE_MS;
  const engine = new RuleEngine({
    store,
    nowMs: () => nowMs,
    authoritative: fakeAuthoritative(answers),
  });
  return { store, engine, advance: (ms: number) => (nowMs += ms), now: () => nowMs };
}

describe('rule engine (B6)', () => {
  it('fires an immediate transactional rule and persists the full record', async () => {
    const { store, engine } = harness(BOOKING_CONFIRMATION_WORKFLOW);
    const decision = await engine.handleTrigger(
      BOOKING_CONFIRMATION_WORKFLOW,
      envelope('evt_1', 'booking_confirmed'),
    );

    expect(decision.outcome).toBe('fired');
    const record = await store.get(decision.actionRequestId!);
    expect(record).toMatchObject({
      ruleId: 'booking_confirmation_workflow',
      ruleVersion: 1,
      decision: 'fired',
      deliveryStatus: 'pending',
      triggeringEventIds: ['evt_1'],
      attempts: 0,
    });
    expect(record!.evidence).toContain('evt_1');
    expect(record!.decidedAtMs).toBe(BASE_MS);
  });

  it('REPLAY: same triggering event → duplicate_detected, one record, no second action', async () => {
    const { store, engine } = harness(BOOKING_CONFIRMATION_WORKFLOW);
    const event = envelope('evt_1', 'booking_confirmed');
    const first = await engine.handleTrigger(BOOKING_CONFIRMATION_WORKFLOW, event);
    const replay = await engine.handleTrigger(BOOKING_CONFIRMATION_WORKFLOW, event);

    expect(replay.outcome).toBe('duplicate_detected');
    expect(replay.actionRequestId).toBe(first.actionRequestId);
    expect(replay.record?.decision).toBe('fired'); // original decision untouched
    expect(replay.evidence).toContain('replay');

    const fired = await store.listFired('booking_confirmation_workflow', 'session:sess_1', 0);
    expect(fired).toHaveLength(1); // still exactly one action
  });

  it('consent gate: denied ads consent suppresses checkout messaging', async () => {
    const { store, engine } = harness(CHECKOUT_ABANDONMENT);
    const decision = await engine.handleTrigger(
      CHECKOUT_ABANDONMENT,
      envelope('evt_c', 'checkout_started', { consent: { analytics: true, ads: false } }),
    );

    expect(decision.outcome).toBe('suppressed_consent');
    const record = await store.get(decision.actionRequestId!);
    expect(record?.decision).toBe('suppressed_consent');
    expect(record?.evidence).toContain("consent 'ads' not granted");
    expect(record?.deliveryStatus).toBe('none');
  });

  it('cooldown: a second fire for the same subject inside the window is suppressed', async () => {
    const { store, engine, advance } = harness(PAYMENT_FAILURE_RETRY_SUPPORT);
    const first = await engine.handleTrigger(
      PAYMENT_FAILURE_RETRY_SUPPORT,
      envelope('evt_p1', 'payment_failed'),
    );
    expect(first.outcome).toBe('fired');

    advance(5 * 60 * 1000); // 5 min later, still inside the 15-min cooldown
    const second = await engine.handleTrigger(
      PAYMENT_FAILURE_RETRY_SUPPORT,
      envelope('evt_p2', 'payment_failed'),
    );
    expect(second.outcome).toBe('suppressed_cooldown');
    expect((await store.get(second.actionRequestId!))?.evidence).toContain('cooldown');

    advance(11 * 60 * 1000); // past the cooldown → eligible again
    const third = await engine.handleTrigger(
      PAYMENT_FAILURE_RETRY_SUPPORT,
      envelope('evt_p3', 'payment_failed'),
    );
    expect(third.outcome).toBe('fired');
  });

  it('daily cap: the fourth fire for a subject on one UTC day is suppressed', async () => {
    const { engine, advance } = harness(PAYMENT_FAILURE_RETRY_SUPPORT);
    for (let i = 0; i < 3; i += 1) {
      const decision = await engine.handleTrigger(
        PAYMENT_FAILURE_RETRY_SUPPORT,
        envelope(`evt_cap_${i}`, 'payment_failed'),
      );
      expect(decision.outcome).toBe('fired');
      advance(16 * 60 * 1000); // step past the cooldown each time
    }
    const fourth = await engine.handleTrigger(
      PAYMENT_FAILURE_RETRY_SUPPORT,
      envelope('evt_cap_4', 'payment_failed'),
    );
    expect(fourth.outcome).toBe('suppressed_daily_cap');
    expect(fourth.record?.evidence).toContain('daily cap 3');
  });

  it('cancel-path (authoritative): booking confirmed in-window → no reminder at due time', async () => {
    const { engine, advance } = harness(CHECKOUT_ABANDONMENT, {
      booking_exists: { suppress: true, conclusive: true, evidence: 'booking BK-1 confirmed at due time' },
    });
    const scheduled = await engine.handleTrigger(
      CHECKOUT_ABANDONMENT,
      envelope('evt_co', 'checkout_started'),
    );
    expect(scheduled.outcome).toBe('pending_window');
    expect(scheduled.record?.evaluateAtMs).toBe(BASE_MS + 30 * 60 * 1000);

    advance(30 * 60 * 1000 + 1);
    const [decision] = await engine.sweepDue([CHECKOUT_ABANDONMENT]);
    expect(decision!.outcome).toBe('suppressed_authoritative');
    expect(decision!.record?.evidence).toContain('BK-1');
    expect(decision!.record?.deliveryStatus).toBe('none');
  });

  it('cancel-path (opportunistic): cancel event for the pending subject suppresses immediately', async () => {
    const { engine, store } = harness(CHECKOUT_ABANDONMENT);
    await engine.handleTrigger(CHECKOUT_ABANDONMENT, envelope('evt_co', 'checkout_started'));
    const cancel = await engine.handleCancel(
      CHECKOUT_ABANDONMENT,
      envelope('evt_bk', 'booking_confirmed'), // same session subject in this fixture
    );
    expect(cancel.outcome).toBe('suppressed_cancel');
    expect(await store.listPending('checkout_abandonment', 'session:sess_1')).toHaveLength(0);

    const noMatch = await engine.handleCancel(
      CHECKOUT_ABANDONMENT,
      envelope('evt_bk2', 'booking_confirmed', { session: { id: 'sess_other', seq: 1 } }),
    );
    expect(noMatch.outcome).toBe('cancel_no_match');
  });

  it('windowed fire: KYC reminder fires when still pending, suppressed when approved', async () => {
    const stillPending = harness(KYC_PENDING_REMINDER, {
      kyc_pending: { suppress: false, conclusive: true, evidence: 'KYC still pending' },
    });
    await stillPending.engine.handleTrigger(
      KYC_PENDING_REMINDER,
      envelope('evt_k1', 'kyc_submitted', { actor: { uidHash: 'uh_1', anonId: null } }),
    );
    stillPending.advance(24 * 60 * 60 * 1000 + 1);
    const [fired] = await stillPending.engine.sweepDue([KYC_PENDING_REMINDER]);
    expect(fired!.outcome).toBe('fired');
    expect(fired!.record?.deliveryStatus).toBe('pending');
    expect(fired!.record?.evidence).toContain('KYC still pending');

    const approved = harness(KYC_PENDING_REMINDER, {
      kyc_pending: { suppress: true, conclusive: true, evidence: 'KYC approved at due time' },
    });
    await approved.engine.handleTrigger(
      KYC_PENDING_REMINDER,
      envelope('evt_k2', 'kyc_submitted', { actor: { uidHash: 'uh_2', anonId: null } }),
    );
    approved.advance(24 * 60 * 60 * 1000 + 1);
    const [suppressed] = await approved.engine.sweepDue([KYC_PENDING_REMINDER]);
    expect(suppressed!.outcome).toBe('suppressed_authoritative');
    expect(suppressed!.record?.deliveryStatus).toBe('none');
  });

  it('inconclusive authoritative check still fires (never silently blocks) and records why', async () => {
    const { engine, advance } = harness(KYC_PENDING_REMINDER, {
      kyc_pending: { suppress: true, conclusive: false, evidence: 'KYC store unavailable' },
    });
    await engine.handleTrigger(
      KYC_PENDING_REMINDER,
      envelope('evt_k3', 'kyc_submitted', { actor: { uidHash: 'uh_3', anonId: null } }),
    );
    advance(24 * 60 * 60 * 1000 + 1);
    const [decision] = await engine.sweepDue([KYC_PENDING_REMINDER]);
    expect(decision!.outcome).toBe('fired');
    expect(decision!.record?.evidence).toContain('KYC store unavailable');
  });

  it('dispatch: retried to the boundary → the downstream effect runs exactly once', async () => {
    const { store, engine } = harness(BOOKING_CONFIRMATION_WORKFLOW);
    const fired = await engine.handleTrigger(
      BOOKING_CONFIRMATION_WORKFLOW,
      envelope('evt_d', 'booking_confirmed'),
    );
    let effects = 0;
    const deps = { store, nowMs: () => BASE_MS, send: async () => { effects += 1; } };

    const first = await dispatchAction(deps, fired.actionRequestId!);
    expect(first).toMatchObject({ status: 'delivered', effectRan: true });

    const retry = await dispatchAction(deps, fired.actionRequestId!);
    expect(retry).toMatchObject({ status: 'duplicate_suppressed', effectRan: false });
    expect(effects).toBe(1); // effect occurred once

    const record = await store.get(fired.actionRequestId!);
    expect(record).toMatchObject({ deliveryStatus: 'delivered', attempts: 1 });
  });

  it('dispatch failure marks the record and a later retry can re-attempt', async () => {
    const { store, engine } = harness(BOOKING_CONFIRMATION_WORKFLOW);
    const fired = await engine.handleTrigger(
      BOOKING_CONFIRMATION_WORKFLOW,
      envelope('evt_f', 'booking_confirmed'),
    );
    let calls = 0;
    let effects = 0;
    const deps = {
      store,
      nowMs: () => BASE_MS,
      send: async () => {
        calls += 1;
        if (calls === 1) throw new Error('fcm down');
        effects += 1;
      },
    };

    const failed = await dispatchAction(deps, fired.actionRequestId!);
    expect(failed).toMatchObject({ status: 'failed', effectRan: false });
    expect((await store.get(fired.actionRequestId!))?.deliveryStatus).toBe('failed');

    const retried = await dispatchAction(deps, fired.actionRequestId!);
    expect(retried).toMatchObject({ status: 'delivered', effectRan: true });
    expect(effects).toBe(1);
  });

  it('sweep leaves records of retired rules untouched and reports rule_missing', async () => {
    const { store, engine, advance } = harness(CHECKOUT_ABANDONMENT);
    const scheduled = await engine.handleTrigger(
      CHECKOUT_ABANDONMENT,
      envelope('evt_r', 'checkout_started'),
    );
    advance(31 * 60 * 1000);
    const [decision] = await engine.sweepDue([]); // rule definition no longer loaded
    expect(decision!.outcome).toBe('rule_missing');
    const record = await store.get(scheduled.actionRequestId!);
    expect(record?.decision).toBe('pending_window'); // untouched, left for triage
  });

  it('every starter rule is self-consistent (unique id, owner, version ≥ 1, gates declared)', () => {
    const ids = new Set<string>();
    for (const rule of STARTER_RULES) {
      expect(ids.has(rule.id), `duplicate rule id ${rule.id}`).toBe(false);
      ids.add(rule.id);
      expect(rule.version).toBeGreaterThanOrEqual(1);
      expect(rule.owner).toBeTruthy();
      expect(rule.dailyCap).toBeGreaterThanOrEqual(0);
      expect(rule.subjectKey(envelope('evt_x', rule.triggerEvent))).not.toBeNull();
    }
    expect(STARTER_RULES).toHaveLength(6);
  });
});
