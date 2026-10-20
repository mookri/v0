// B2 acceptance, end to end: emitter → outbox → publisher → ingestion →
// signals_raw. Proves the evaluation criterion directly:
//   "every committed domain event is either persisted EXACTLY ONCE in
//    signals_raw or is visible in the gap report."
// Duplicate publishes from crash-replay are expected (at-least-once) and
// must collapse to one row per envelope ID at the sink.

import { describe, expect, it } from 'vitest';
import { MemoryOutboxStore } from '@infra/messaging/outbox/MemoryOutboxStore';
import {
  emitBookingConfirmed,
  emitPaymentFailed,
  emitPaymentSucceeded,
} from '@navdhya/analytics/src/server/emitters';
import { runPublisherOnce, type PublishedMessage } from '@services/functions/outbox-publisher/src/publisher';
import { ingestBatch, type RejectedEvent } from '@services/functions/ingestion/src/ingest';
import { MemoryRawSink } from '@services/functions/ingestion/src/raw-sink';
import { reconcileLevel1 } from '@navdhya/analytics/src/server/reconciliation/level1';

// Fake Pub/Sub: acceptance array with crash injection.
function pubsubTransport() {
  const accepted: PublishedMessage[] = [];
  let calls = 0;
  let acceptThenThrowFrom: number | undefined;
  return {
    accepted,
    crashFrom(index: number) {
      acceptThenThrowFrom = index;
    },
    heal() {
      acceptThenThrowFrom = undefined;
    },
    transport: {
      async publish(message: PublishedMessage) {
        const call = calls++;
        if (acceptThenThrowFrom !== undefined && call >= acceptThenThrowFrom) {
          accepted.push(message); // accepted…
          throw new Error('CRASH_BEFORE_ACK'); // …then worker dies pre-ack
        }
        accepted.push(message);
      },
    },
  };
}

async function seedWithEmitters(store: MemoryOutboxStore): Promise<string[]> {
  await emitPaymentSucceeded(store, { aggregateId: 'pay_1' }, {
    value: 5100,
    currency: 'INR',
    transaction_ref: 'rzp_1',
  });
  await emitPaymentFailed(store, { aggregateId: 'pay_2' }, {
    value: 2500,
    currency: 'INR',
    transaction_ref: 'rzp_2',
    failure_code: 'BANK_DECLINED',
  });
  await emitBookingConfirmed(store, { aggregateId: 'book_1' }, {
    value: 5100,
    currency: 'INR',
    service_category: 'puja',
    city: 'Pune',
  });
  return store.snapshot().map((r) => r.id);
}

describe('truth path end to end (B2)', () => {
  it('crash-replay duplicates collapse to exactly one row per envelope ID', async () => {
    const store = new MemoryOutboxStore();
    const committedIds = await seedWithEmitters(store);
    expect(committedIds).toHaveLength(3);

    const pubsub = pubsubTransport();
    pubsub.crashFrom(1); // first record acks; the rest are accepted then crash

    const run1 = await runPublisherOnce(store, pubsub.transport);
    expect(run1).toEqual({ published: 1, released: 2, deadLettered: 0 });

    pubsub.heal();
    const run2 = await runPublisherOnce(store, pubsub.transport);
    expect(run2).toEqual({ published: 2, released: 0, deadLettered: 0 });
    expect(store.snapshot().every((r) => r.published)).toBe(true);

    // Pub/Sub now carries DUPLICATE deliveries (at-least-once) — expected.
    expect(pubsub.accepted.length).toBeGreaterThan(3);
    expect(new Set(pubsub.accepted.map((m) => m.id)).size).toBe(3);

    // Ingestion: one durable put; dedup by insertId → one row per envelope.
    const sink = new MemoryRawSink();
    const result = await ingestBatch(
      pubsub.accepted.map((m) => m.payload),
      { sink, source: 'server' },
    );
    expect(result.ok).toBe(true);
    expect(sink.size()).toBe(3); // exactly once per envelope ID
    expect(sink.all().map((r) => r.insertId).sort()).toEqual([...committedIds].sort());

    // Level-1 reconciliation over the same ID space: clean.
    const report = reconcileLevel1({
      committedIds,
      publishedIds: store.snapshot().filter((r) => r.published).map((r) => r.id),
      persistedIds: sink.all().map((r) => r.insertId),
    });
    expect(report.ok).toBe(true);
    expect(report.missingPublished).toEqual([]);
    expect(report.missingPersisted).toEqual([]);
    expect(report.duplicatedPersisted).toEqual([]);
  });

  it('an unknown event name is rejected to the dead-letter seam and shows up in the gap report', async () => {
    const store = new MemoryOutboxStore();
    const committedIds = await seedWithEmitters(store);

    // A poison record enters the outbox with a structurally-valid envelope
    // whose name is not in the registry (schema drift or a bad deploy).
    const poisonId = 'POISON000000000000000000001';
    await store.append({
      id: poisonId,
      aggregateId: 'agg_x',
      type: 'never_registered',
      payload: {
        id: poisonId,
        name: 'never_registered',
        version: 1,
        occurredAt: '2026-10-12T10:00:00.000Z',
        surface: 'web',
        appVersion: '1.0.0',
        session: { id: 'sess_p', seq: 1 },
        actor: { uidHash: null, anonId: null },
        consent: { analytics: false, ads: false },
        context: { routeTemplate: '/' },
        params: {},
      },
      occurredAt: '2026-10-12T10:00:00.000Z',
    });

    const pubsub = pubsubTransport();
    const published = await runPublisherOnce(store, pubsub.transport);
    expect(published.published).toBe(4);

    const sink = new MemoryRawSink();
    const deadLettered: Array<{ rejected: RejectedEvent; envelope: unknown }> = [];
    const result = await ingestBatch(
      pubsub.accepted.map((m) => m.payload),
      {
        sink,
        source: 'server',
        onDeadLetter: (rejected, envelope) => void deadLettered.push({ rejected, envelope }),
      },
    );

    // Never silently dropped, never inserted — and batchmates deferred.
    expect(result.ok).toBe(false);
    expect(result.persisted).toBe(0);
    expect(result.rejected).toHaveLength(1);
    expect(result.rejected[0]!.errors.join(' ')).toContain('unknown event');
    expect(deadLettered).toHaveLength(1);
    expect(sink.size()).toBe(0);

    // Retry without the poison: the valid three land.
    const retry = await ingestBatch(
      pubsub.accepted.filter((m) => m.id !== poisonId).map((m) => m.payload),
      { sink, source: 'server' },
    );
    expect(retry.ok).toBe(true);
    expect(sink.size()).toBe(3);

    // The poison is VISIBLE in the gap report (never persisted, accounted for).
    const report = reconcileLevel1({
      committedIds: [...committedIds, poisonId],
      publishedIds: store.snapshot().filter((r) => r.published).map((r) => r.id),
      persistedIds: sink.all().map((r) => r.insertId),
    });
    expect(report.ok).toBe(false);
    expect(report.missingPersisted).toEqual([poisonId]);
    expect(report.missingPublished).toEqual([]); // it WAS published — gap is at ingestion
  });

  it('never acks what the transport rejected: a fully-down Pub/Sub stalls without data loss', async () => {
    const store = new MemoryOutboxStore();
    const committedIds = await seedWithEmitters(store);

    const downTransport = {
      async publish(): Promise<void> {
        throw new Error('PUBSUB_UNAVAILABLE');
      },
    };
    const stalled = await runPublisherOnce(store, downTransport, { maxAttempts: 5 });
    expect(stalled).toEqual({ published: 0, released: 3, deadLettered: 0 });
    expect(store.snapshot().every((r) => !r.published)).toBe(true); // still owned by the outbox

    // Recovery: transport heals → everything completes, nothing lost.
    const pubsub = pubsubTransport();
    const recovered = await runPublisherOnce(store, pubsub.transport);
    expect(recovered.published).toBe(3);
    expect(pubsub.accepted.map((m) => m.id).sort()).toEqual([...committedIds].sort());
  });
});
