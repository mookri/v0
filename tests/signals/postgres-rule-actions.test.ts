// B6 persistence — PostgresRuleActionStore against the statement-level
// fake: the contract behaviors the engine relies on (idempotent put,
// decision transitions, delivery markers with attempt counting, the three
// list queries) hold with the production SQL shapes.

import { describe, expect, it } from 'vitest';
import type { RuleActionRecord } from '@navdhya/analytics/src/rules/contract';
import { PostgresRuleActionStore } from '@infra/rules/PostgresRuleActionStore';
import { FakeRuleActionsClient } from './helpers/fake-rule-actions-client';

const AT = Date.parse('2026-10-13T12:00:00.000Z');

function record(overrides: Partial<RuleActionRecord> = {}): RuleActionRecord {
  return {
    actionRequestId: 'checkout_abandonment@v1:session:s_1:evt_a',
    ruleId: 'checkout_abandonment',
    ruleVersion: 1,
    subjectKey: 'session:s_1',
    triggeringEventIds: ['evt_a'],
    decision: 'pending_window',
    evidence: 'windowed 30m from trigger evt_a',
    requestedAtMs: AT,
    evaluateAtMs: AT + 30 * 60 * 1000,
    decidedAtMs: null,
    deliveryStatus: 'scheduled',
    deliveredAtMs: null,
    attempts: 0,
    ...overrides,
  };
}

function store() {
  const db = new FakeRuleActionsClient();
  return { db, store: new PostgresRuleActionStore(db) };
}

describe('PostgresRuleActionStore (B6)', () => {
  it('put → get round-trips the full record incl. triggering event IDs', async () => {
    const { store: s } = store();
    expect(await s.put(record())).toBe('inserted');
    const fetched = await s.get(record().actionRequestId);
    expect(fetched).toEqual(record());
  });

  it('put is idempotent: a replayed insert reports duplicate, original untouched', async () => {
    const { store: s } = store();
    await s.put(record());
    expect(await s.put(record({ evidence: 'second write must not land' }))).toBe('duplicate');
    expect((await s.get(record().actionRequestId))?.evidence).toBe('windowed 30m from trigger evt_a');
  });

  it('update transitions a pending record to fired', async () => {
    const { store: s } = store();
    await s.put(record());
    const fired = record({
      decision: 'fired',
      decidedAtMs: AT + 31 * 60 * 1000,
      deliveryStatus: 'pending',
      evidence: 'window elapsed; authoritative ok',
    });
    await s.update(fired);
    const fetched = await s.get(record().actionRequestId);
    expect(fetched).toMatchObject({ decision: 'fired', deliveryStatus: 'pending' });
  });

  it('markDelivered / markFailed update status and count attempts', async () => {
    const { store: s } = store();
    await s.put(record({ decision: 'fired', deliveryStatus: 'pending', evaluateAtMs: null }));
    const id = record().actionRequestId;

    await s.markDelivered(id, AT + 1000);
    expect(await s.get(id)).toMatchObject({ deliveryStatus: 'delivered', deliveredAtMs: AT + 1000, attempts: 1 });

    const { store: s2 } = store();
    await s2.put(record({ decision: 'fired', deliveryStatus: 'pending', evaluateAtMs: null }));
    await s2.markFailed(id, AT + 1000);
    expect(await s2.get(id)).toMatchObject({ deliveryStatus: 'failed', attempts: 1 });
  });

  it('listFired filters by rule, subject, and time floor', async () => {
    const { store: s } = store();
    await s.put(record({ decision: 'fired', deliveryStatus: 'pending', evaluateAtMs: null, requestedAtMs: AT }));
    await s.put(
      record({
        actionRequestId: 'other_subject',
        subjectKey: 'session:s_2',
        decision: 'fired',
        deliveryStatus: 'pending',
        evaluateAtMs: null,
      }),
    );
    await s.put(record({ actionRequestId: 'suppressed_one', decision: 'suppressed_cooldown' }));

    const fired = await s.listFired('checkout_abandonment', 'session:s_1', AT - 1);
    expect(fired.map((r) => r.actionRequestId)).toEqual([record().actionRequestId]);

    expect(await s.listFired('checkout_abandonment', 'session:s_1', AT + 1)).toHaveLength(0);
  });

  it('listPending + listPendingDue select windowed records, due ones ordered', async () => {
    const { store: s } = store();
    const later = record({ actionRequestId: 'later', evaluateAtMs: AT + 60 * 60 * 1000 });
    const sooner = record({ actionRequestId: 'sooner', evaluateAtMs: AT + 10 * 60 * 1000 });
    await s.put(later);
    await s.put(sooner);
    await s.put(record({ actionRequestId: 'already_fired', decision: 'fired', evaluateAtMs: null }));

    const pending = await s.listPending('checkout_abandonment', 'session:s_1');
    expect(pending.map((r) => r.actionRequestId)).toEqual(['sooner', 'later']);

    const due = await s.listPendingDue(AT + 15 * 60 * 1000);
    expect(due.map((r) => r.actionRequestId)).toEqual(['sooner']);

    const allDue = await s.listPendingDue(AT + 2 * 60 * 60 * 1000);
    expect(allDue.map((r) => r.actionRequestId)).toEqual(['sooner', 'later']);
  });

  it('get on an unknown id returns null', async () => {
    const { store: s } = store();
    expect(await s.get('missing')).toBeNull();
  });
});
