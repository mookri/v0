// B6 acceptance — rule engine (plan §B6): the five acceptance properties
// end-to-end over the real starter rules + memory store + fake boundaries:
//   1. rule replay → one action, second attempt detected as duplicate
//   2. cancel-path: checkout_started + booking_confirmed in window → no reminder
//   3. consent/frequency guardrails: denial, cooldown, daily cap suppress
//   4. idempotency: retried to the FCM/outbox boundary → effect occurs once
//   5. every fired action explainable from its persisted record alone

import { describe, expect, it } from 'vitest';
import type { SignalEnvelope } from '@navdhya/analytics/src/contracts/envelope';
import { RuleEngine, type AuthoritativeCheckPort } from '@navdhya/analytics/src/rules/engine';
import { MemoryRuleActionStore, dispatchAction } from '@navdhya/analytics/src/rules/store';
import type { RuleActionRecord } from '@navdhya/analytics/src/rules/contract';
import {
  BOOKING_CONFIRMATION_WORKFLOW,
  CHECKOUT_ABANDONMENT,
  PAYMENT_FAILURE_RETRY_SUPPORT,
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
    session: { id: 'sess_acc', seq: 1 },
    actor: { uidHash: null, anonId: null },
    consent: { analytics: true, ads: true },
    context: { routeTemplate: '/checkout' },
    params: {},
    ...overrides,
  };
}

function harness(bookingConfirmed: boolean) {
  const store = new MemoryRuleActionStore();
  let nowMs = BASE_MS;
  const authoritative: AuthoritativeCheckPort = {
    check: async ({ rule }) =>
      rule.authoritativeCheck === 'booking_exists' && bookingConfirmed
        ? { suppress: true, conclusive: true, evidence: 'booking exists for subject (authoritative)' }
        : { suppress: false, conclusive: true, evidence: 'no conversion recorded' },
  };
  const engine = new RuleEngine({ store, nowMs: () => nowMs, authoritative });
  return { store, engine, advance: (ms: number) => (nowMs += ms) };
}

describe('rule engine acceptance (B6)', () => {
  it('1. REPLAY: same triggering event IDs → one action; the second attempt is a detected duplicate', async () => {
    const { store, engine } = harness(false);
    const trigger = envelope('evt_replay', 'booking_confirmed');

    const first = await engine.handleTrigger(BOOKING_CONFIRMATION_WORKFLOW, trigger);
    expect(first.outcome).toBe('fired');
    const second = await engine.handleTrigger(BOOKING_CONFIRMATION_WORKFLOW, trigger);
    expect(second.outcome).toBe('duplicate_detected');
    expect(second.actionRequestId).toBe(first.actionRequestId);

    const all = await store.listFired('booking_confirmation_workflow', 'session:sess_acc', 0);
    expect(all).toHaveLength(1);
  });

  it('2. CANCEL-PATH: checkout_started + booking_confirmed within the window → no reminder', async () => {
    // Path A — authoritative state at due time (the robust path).
    const converted = harness(true);
    const scheduled = await converted.engine.handleTrigger(
      CHECKOUT_ABANDONMENT,
      envelope('evt_co_a', 'checkout_started'),
    );
    expect(scheduled.outcome).toBe('pending_window');
    converted.advance(30 * 60 * 1000 + 1);
    const [dueDecision] = await converted.engine.sweepDue([CHECKOUT_ABANDONMENT]);
    expect(dueDecision!.outcome).toBe('suppressed_authoritative');
    expect(dueDecision!.record?.deliveryStatus).toBe('none');
    expect(await converted.store.listPending('checkout_abandonment', 'session:sess_acc')).toHaveLength(0);

    // Path B — opportunistic cancel event for the correlated subject.
    const { engine, store } = harness(false);
    await engine.handleTrigger(CHECKOUT_ABANDONMENT, envelope('evt_co_b', 'checkout_started'));
    const cancel = await engine.handleCancel(
      CHECKOUT_ABANDONMENT,
      envelope('evt_bk_b', 'booking_confirmed'),
    );
    expect(cancel.outcome).toBe('suppressed_cancel');
    expect(await store.listPending('checkout_abandonment', 'session:sess_acc')).toHaveLength(0);
  });

  it('3. GUARDRAILS: consent denial, cooldown, and daily cap each suppress', async () => {
    const { store, engine, advance } = harness(false);

    const denied = await engine.handleTrigger(
      CHECKOUT_ABANDONMENT,
      envelope('evt_g1', 'checkout_started', { consent: { analytics: true, ads: false } }),
    );
    expect(denied.outcome).toBe('suppressed_consent');

    // Cooldown + daily cap on the payment-failure rule (15-min cooldown,
    // cap 3/day) through the same engine and subject.
    advance(60 * 1000);
    const fire1 = await engine.handleTrigger(
      PAYMENT_FAILURE_RETRY_SUPPORT,
      envelope('evt_g2', 'payment_failed'),
    );
    expect(fire1.outcome).toBe('fired');
    advance(5 * 60 * 1000);
    const cooldownHit = await engine.handleTrigger(
      PAYMENT_FAILURE_RETRY_SUPPORT,
      envelope('evt_g3', 'payment_failed'),
    );
    expect(cooldownHit.outcome).toBe('suppressed_cooldown');

    // Daily cap: step past cooldowns until the cap refuses.
    const outcomes: string[] = [];
    for (let i = 0; i < 3; i += 1) {
      advance(16 * 60 * 1000);
      const decision = await engine.handleTrigger(
        PAYMENT_FAILURE_RETRY_SUPPORT,
        envelope(`evt_cap_${i}`, 'payment_failed'),
      );
      outcomes.push(decision.outcome);
    }
    expect(outcomes).toEqual(['fired', 'fired', 'suppressed_daily_cap']);

    const suppressed = [denied, cooldownHit].map((d) => store.get(d.actionRequestId!));
    const records = await Promise.all(suppressed);
    for (const record of records) {
      expect(record?.deliveryStatus).toBe('none'); // suppressed → nothing to dispatch
      expect(record?.evidence.length).toBeGreaterThan(0);
    }
  });

  it('4. IDEMPOTENCY: retried to the FCM/outbox boundary → the downstream effect occurs once', async () => {
    const { store, engine } = harness(false);
    const fired = await engine.handleTrigger(
      BOOKING_CONFIRMATION_WORKFLOW,
      envelope('evt_idem', 'booking_confirmed'),
    );
    let effects = 0;
    const deps = { store, nowMs: () => BASE_MS, send: async () => { effects += 1; } };

    // Cloud Tasks-style retries of the same action request:
    const delivery = await dispatchAction(deps, fired.actionRequestId!);
    const retry1 = await dispatchAction(deps, fired.actionRequestId!);
    const retry2 = await dispatchAction(deps, fired.actionRequestId!);

    expect(delivery.status).toBe('delivered');
    expect(retry1).toMatchObject({ status: 'duplicate_suppressed', effectRan: false });
    expect(retry2).toMatchObject({ status: 'duplicate_suppressed', effectRan: false });
    expect(effects).toBe(1);
  });

  it('5. EXPLAINABILITY: a fired action record alone answers who/why/when', async () => {
    const { store, engine } = harness(false);
    const fired = await engine.handleTrigger(
      BOOKING_CONFIRMATION_WORKFLOW,
      envelope('evt_exp', 'booking_confirmed'),
    );
    await dispatchAction({ store, nowMs: () => BASE_MS, send: async () => {} }, fired.actionRequestId!);

    const record: RuleActionRecord = (await store.get(fired.actionRequestId!))!;
    expect(record).toMatchObject({
      ruleId: 'booking_confirmation_workflow',
      ruleVersion: 1,
      decision: 'fired',
      triggeringEventIds: ['evt_exp'],
      deliveryStatus: 'delivered',
      attempts: 1,
    });
    expect(record.actionRequestId).toContain('booking_confirmation_workflow@v1');
    expect(record.actionRequestId).toContain('evt_exp');
    expect(record.evidence).toContain('evt_exp'); // the WHY, from the record alone
    expect(record.deliveredAtMs).toBe(BASE_MS);
  });
});
