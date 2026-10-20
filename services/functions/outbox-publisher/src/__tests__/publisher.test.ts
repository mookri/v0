// B2 acceptance: publisher integrity under failure, crash and replay.

import { describe, expect, it } from 'vitest';
import type { OutboxRecord, OutboxStore } from '@navdhya/contracts/src/messaging/outbox';
import { MemoryOutboxStore } from '@infra/messaging/outbox/MemoryOutboxStore';
import { runPublisherOnce, type EventTransport, type PublishedMessage } from '../publisher';

async function seed(store: OutboxStore, n: number): Promise<void> {
  for (let i = 1; i <= n; i++) {
    await store.append({
      id: `evt_${i}`,
      aggregateId: `agg_${i}`,
      type: 'payment_succeeded',
      payload: { seq: i },
      occurredAt: `2026-10-12T00:00:0${i}.000Z`,
    });
  }
}

interface RecordingTransport extends EventTransport {
  seen: PublishedMessage[];
  failFrom?: number; // call indexes >= this fail WITHOUT acceptance
  failAt?: number[]; // exact call indexes fail WITHOUT acceptance
  acceptThenThrowFrom?: number; // accepted by transport, then crash before ack
}

function recordingTransport(): RecordingTransport {
  const seen: PublishedMessage[] = [];
  let calls = 0;
  return {
    seen,
    async publish(message) {
      const callIndex = calls++; // advances on every attempt, accepted or not
      if (this.acceptThenThrowFrom !== undefined && callIndex >= this.acceptThenThrowFrom) {
        seen.push(message); // accepted…
        throw new Error('CRASH_BEFORE_ACK'); // …then process dies before markPublished
      }
      if (this.failAt?.includes(callIndex) === true || (this.failFrom !== undefined && callIndex >= this.failFrom)) {
        throw new Error('PUBSUB_UNAVAILABLE');
      }
      seen.push(message);
    },
  };
}

describe('runPublisherOnce', () => {
  it('publishes oldest-first and marks published only after transport acceptance', async () => {
    const store = new MemoryOutboxStore();
    await seed(store, 3);
    const transport = recordingTransport();

    const res = await runPublisherOnce(store, transport, { batch: 50 });

    expect(res).toEqual({ published: 3, released: 0, deadLettered: 0 });
    expect(transport.seen.map((m) => m.id)).toEqual(['evt_1', 'evt_2', 'evt_3']);
    expect(store.snapshot().every((r) => r.published)).toBe(true);
  });

  it('never acks a record the transport rejected — it is released for retry', async () => {
    const store = new MemoryOutboxStore();
    await seed(store, 3);
    const transport = recordingTransport();
    transport.failAt = [0]; // first call fails, the rest are healthy

    const run1 = await runPublisherOnce(store, transport, { batch: 50 });
    expect(run1).toEqual({ published: 2, released: 1, deadLettered: 0 });
    const failed = store.snapshot().find((r) => r.id === 'evt_1')!;
    expect(failed.published).toBe(false);
    expect(failed.claimedAt).toBeNull(); // released → immediately retryable
    expect(failed.attempts).toBe(1);
    expect(store.snapshot().filter((r) => r.published)).toHaveLength(2);

    // Transport heals → the released record completes; attempts count BOTH tries.
    delete transport.failAt;
    const run2 = await runPublisherOnce(store, transport, { batch: 50 });
    expect(run2).toEqual({ published: 1, released: 0, deadLettered: 0 });
    expect(store.snapshot().find((r) => r.id === 'evt_1')!.attempts).toBe(2);
    expect(store.snapshot().every((r) => r.published)).toBe(true);
  });

  it('kill mid-batch: partial progress persists, the rest completes next run — nothing lost', async () => {
    const store = new MemoryOutboxStore();
    await seed(store, 4);
    const transport = recordingTransport();
    transport.failFrom = 2; // records 0,1 accepted; transport dies from record 2 on

    const run1 = await runPublisherOnce(store, transport);
    expect(run1).toEqual({ published: 2, released: 2, deadLettered: 0 });

    delete transport.failFrom;
    const run2 = await runPublisherOnce(store, transport);
    expect(run2).toEqual({ published: 2, released: 0, deadLettered: 0 });

    const ids = transport.seen.map((m) => m.id);
    expect(new Set(ids).size).toBe(4); // every event delivered
    expect(store.snapshot().every((r) => r.published)).toBe(true);
  });

  it('lease-expiry redelivery: an abandoned claim (crashed worker) is redelivered, attempts keep counting', async () => {
    let nowMs = 1_000_000;
    const store = new MemoryOutboxStore(() => nowMs);
    await seed(store, 2);
    const transport = recordingTransport();

    // Worker claims, publishes… then dies WITHOUT marking (ack lost).
    transport.acceptThenThrowFrom = 0;
    const abandoned = await store.claim(2, { leaseMs: 30_000 });
    expect(abandoned.map((r) => r.id)).toEqual(['evt_1', 'evt_2']);
    for (const r of abandoned) {
      // Accepted by the transport, then the process crashes before ack —
      // the throw IS the simulated crash; acceptance already happened.
      await transport.publish({ id: r.id, type: r.type, payload: r.payload }).catch(() => {});
    }
    const dupBeforeExpiry = transport.seen.length;

    // While the lease is live, no other worker may steal the records.
    const blocked = await runPublisherOnce(store, transport, { leaseMs: 30_000 });
    expect(blocked.published).toBe(0);

    // Lease expires → redelivery → duplicate publish is expected (at-least-once);
    // dedup to exactly-once happens at the sink (envelope-ID insertId).
    nowMs += 31_000;
    delete transport.acceptThenThrowFrom;
    const replay = await runPublisherOnce(store, transport, { leaseMs: 30_000 });
    expect(replay.published).toBe(2);
    expect(transport.seen.length).toBeGreaterThan(dupBeforeExpiry); // duplicates happened…
    expect(store.snapshot().every((r) => r.published)).toBe(true); // …and were reconciled to acked
    expect(store.snapshot().map((r) => r.attempts)).toEqual([2, 2]); // 2 claims each
  });

  it('attempts ceiling → dead-letter + alert hook; dead records are never claimed again', async () => {
    const store = new MemoryOutboxStore();
    await store.append({
      id: 'poison',
      aggregateId: 'agg_p',
      type: 'payment_succeeded',
      payload: {},
      occurredAt: '2026-10-12T00:00:00.000Z',
    });
    const transport = recordingTransport();
    transport.failFrom = 0; // always fails
    const deadLettered: OutboxRecord[] = [];

    const opts = { maxAttempts: 2, onDeadLettered: (r: OutboxRecord) => void deadLettered.push(r) };

    const run1 = await runPublisherOnce(store, transport, opts);
    expect(run1).toEqual({ published: 0, released: 1, deadLettered: 0 });

    const run2 = await runPublisherOnce(store, transport, opts);
    expect(run2).toEqual({ published: 0, released: 0, deadLettered: 1 });
    expect(deadLettered.map((r) => r.id)).toEqual(['poison']);
    expect(deadLettered[0]!.attempts).toBe(2);

    // Terminal: not claimable, not resurrected.
    const run3 = await runPublisherOnce(store, transport, opts);
    expect(run3).toEqual({ published: 0, released: 0, deadLettered: 0 });
    expect(store.snapshot()[0]!.deadLettered).toBe(true);
  });

  it('handles an empty outbox without transport calls', async () => {
    const store = new MemoryOutboxStore();
    const transport = recordingTransport();
    const res = await runPublisherOnce(store, transport);
    expect(res).toEqual({ published: 0, released: 0, deadLettered: 0 });
    expect(transport.seen).toHaveLength(0);
  });
});
