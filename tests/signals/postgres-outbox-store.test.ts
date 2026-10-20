// B2 item 1: PostgresOutboxStore — SQL shape, row mapping, lease/attempt
// math — asserted against the statement-level fake (see helpers/fake-pg-client).

import { describe, expect, it } from 'vitest';
import { PostgresOutboxStore } from '@infra/messaging/outbox/PostgresOutboxStore';
import { FakePgClient } from './helpers/fake-pg-client';

function makeStore(nowMs = 1_780_000_000_000) {
  const db = new FakePgClient();
  const clock = { nowMs };
  const store = new PostgresOutboxStore(db, () => clock.nowMs);
  return { db, clock, store };
}

async function appendThree(store: PostgresOutboxStore): Promise<void> {
  for (let i = 1; i <= 3; i++) {
    await store.append({
      id: `evt_${i}`,
      aggregateId: `agg_${i}`,
      type: 'payment_succeeded',
      payload: { seq: i },
      occurredAt: `2026-10-12T00:00:0${i}.000Z`,
    });
  }
}

describe('PostgresOutboxStore', () => {
  it('append uses a parameterized upsert (ON CONFLICT DO NOTHING = idempotent by ID)', async () => {
    const { db, store } = makeStore();
    await store.append({ id: 'evt_1', aggregateId: 'a', type: 't', payload: { x: 1 }, occurredAt: 'o' });
    await store.append({ id: 'evt_1', aggregateId: 'a', type: 't', payload: { x: 1 }, occurredAt: 'o' });

    const inserts = db.statements().filter((s) => s.startsWith('INSERT INTO outbox'));
    expect(inserts).toHaveLength(2);
    expect(inserts[0]).toContain('ON CONFLICT (id) DO NOTHING');
    expect(inserts[0]).toContain('$1'); // no string interpolation of values
    expect(db.outboxRows()).toHaveLength(1); // duplicate collapsed
    expect(db.outboxRows()[0]!.payload).toEqual({ x: 1 }); // jsonb round-trip
  });

  it('claim is FOR UPDATE SKIP LOCKED with [now, leaseExpiry, limit] params, oldest first', async () => {
    const { db, clock, store } = makeStore();
    await appendThree(store);

    const claimed = await store.claim(2, { leaseMs: 30_000 });

    const claimSql = db.statements().find((s) => s.includes('FOR UPDATE SKIP LOCKED'))!;
    expect(claimSql).toContain('NOT published');
    expect(claimSql).toContain('NOT dead_lettered');
    expect(claimSql).toContain('ORDER BY occurred_at ASC');
    const claimLog = db.log.find((entry) => entry.text.includes('FOR UPDATE SKIP LOCKED'))!;
    expect(claimLog.params).toEqual([
      new Date(clock.nowMs).toISOString(),
      new Date(clock.nowMs + 30_000).toISOString(),
      2,
    ]);

    expect(claimed.map((r) => r.id)).toEqual(['evt_1', 'evt_2']); // oldest first, bounded
    expect(claimed[0]!.attempts).toBe(1);
    expect(claimed[0]!.claimedAt).toBe(new Date(clock.nowMs).toISOString());
    expect(claimed[0]!.leaseExpiresAt).toBe(new Date(clock.nowMs + 30_000).toISOString());
    expect(claimed[0]!.published).toBe(false);
  });

  it('claimed records are invisible to a second claim until the lease expires (redelivery)', async () => {
    const { clock, store } = makeStore();
    await appendThree(store);

    const first = await store.claim(10, { leaseMs: 30_000 });
    expect(first).toHaveLength(3);

    // Lease live → nobody else can take them.
    expect(await store.claim(10, { leaseMs: 30_000 })).toHaveLength(0);

    // Lease expired → redelivery with attempts incremented again.
    clock.nowMs += 31_000;
    const replay = await store.claim(10, { leaseMs: 30_000 });
    expect(replay.map((r) => r.id)).toEqual(['evt_1', 'evt_2', 'evt_3']);
    expect(replay.map((r) => r.attempts)).toEqual([2, 2, 2]);
  });

  it('markPublished acks exactly once; release keeps attempts; dead-letter is terminal', async () => {
    const { db, clock, store } = makeStore();
    await appendThree(store);
    const [r1] = await store.claim(3, { leaseMs: 30_000 });

    await store.markPublished(r1!.id);
    const publishedRow = db.outboxRows().find((r) => r.id === 'evt_1')!;
    expect(publishedRow.published).toBe(true);
    expect(publishedRow.published_at).toBe(new Date(clock.nowMs).toISOString());
    expect(publishedRow.claimed_at).toBeNull();
    expect(publishedRow.lease_expires_at).toBeNull();

    // Release: back in the pool, attempts preserved.
    await store.release('evt_2');
    const releasedRow = db.outboxRows().find((r) => r.id === 'evt_2')!;
    expect(releasedRow.claimed_at).toBeNull();
    expect(releasedRow.attempts).toBe(1);

    // Dead-letter: terminal — never claimable again.
    await store.markDeadLettered('evt_3');
    clock.nowMs += 10 * 60_000;
    const afterDead = await store.claim(10, { leaseMs: 30_000 });
    expect(afterDead.map((r) => r.id)).toEqual(['evt_2']); // published + dead excluded
    const deadRow = db.outboxRows().find((r) => r.id === 'evt_3')!;
    expect(deadRow.dead_lettered).toBe(true);
    expect(deadRow.claimed_at).toBeNull();
  });

  it('release and markDeadLettered never touch a published record', async () => {
    const { db, store } = makeStore();
    await store.append({ id: 'evt_p', aggregateId: 'a', type: 't', payload: {}, occurredAt: 'o' });
    await store.markPublished('evt_p');

    await store.release('evt_p');
    await store.markDeadLettered('evt_p');

    const row = db.outboxRows()[0]!;
    expect(row.dead_lettered).toBe(false);
    expect(row.published).toBe(true);
  });

  it('rejects unknown statements loudly instead of silently succeeding', async () => {
    const db = new FakePgClient();
    await expect(db.query('DELETE FROM outbox')).rejects.toThrow('unrecognized statement');
  });
});
