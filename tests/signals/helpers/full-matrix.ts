// B9 full-matrix load harness (plan §1: "Load tests must include retries,
// duplicate delivery, downstream failure, queue backlog and recovery — not
// just clean ingestion").
//
// The B3 harness (helpers/collector-load.ts) proved the CLIENT leg —
// collector → ingestion → raw sink at 10k+ eps with its own fault window.
// This harness proves the P0 TRUTH PATH under the same matrix at 10,000
// committed domain events:
//
//   emitters → outbox (tx seam) → publisher → Pub/Sub (fake) → ingestion
//   consumer → sink (insertId dedup) → Level-1 reconciliation
//
// Fault matrix (one dimension per §1 bullet):
//   - retries: transient transport failures for a window of publisher
//     cycles → release → re-claim next cycle (attempts recorded);
//   - duplicate delivery: crash-replay cycles (accept-then-throw) →
//     at-least-once redelivery → sink insertId dedup keeps one row;
//   - downstream failure + backlog: during the fault window records pile
//     up unpublished (backlogPeak) — never dropped, never dead-lettered;
//   - recovery: after the window heals the backlog drains to zero-lag
//     inside the run, and Level-1 reconciliation closes the matrix:
//     every committed ID either persisted exactly once or is in the gap
//     report (ok=true with empty gaps is the hard assertion).
//
// Throughput/latency are RECORDED as evidence (§1 numbers) with sanity
// floors asserted; correctness invariants are the hard gates — wall-clock
// rates are machine-load dependent, not a contract of this suite.

import { MemoryOutboxStore } from '@infra/messaging/outbox/MemoryOutboxStore';
import {
  emitBookingConfirmed,
  emitPaymentFailed,
  emitPaymentSucceeded,
} from '@navdhya/analytics/src/server/emitters';
import { reconcileLevel1, type Level1Report } from '@navdhya/analytics/src/server/reconciliation/level1';
import type { PublishedMessage } from '@services/functions/outbox-publisher/src/publisher';
import { runPublisherOnce } from '@services/functions/outbox-publisher/src/publisher';
import { ingestBatch } from '@services/functions/ingestion/src/ingest';
import { MemoryRawSink, type RawSignalRow } from '@services/functions/ingestion/src/raw-sink';
import type { SignalEnvelope } from '@navdhya/analytics/src/contracts/envelope';

export interface MatrixConfig {
  /** Domain events committed through the tx-outbox seam. */
  totalEvents: number;
  publisherBatch: number;
  /** Publisher cycles answering with a transient transport failure. */
  faultCycles: number;
  /** Crash-replay cycles: accept-then-throw (duplicate deliveries). */
  crashReplayCycles: number;
  /** Attempts ceiling — high enough that drill faults never dead-letter. */
  maxAttempts: number;
  /** Extra cycles allowed after the publish phase to prove zero-lag. */
  maxRecoveryCycles: number;
}

export const DEFAULT_MATRIX_CONFIG: MatrixConfig = {
  totalEvents: 10_000,
  publisherBatch: 50,
  faultCycles: 20,
  crashReplayCycles: 10,
  maxAttempts: 50,
  maxRecoveryCycles: 200,
};

export interface Percentiles {
  count: number;
  p50: number;
  p95: number;
  p99: number;
  max: number;
}

export interface MatrixResult {
  committed: number;
  publishAcks: number;
  transientFaults: number;
  /** Redeliveries of an already-seen envelope ID at the consumer. */
  duplicateDeliveries: number;
  /** Schema rejections at the ingestion consumer (must be 0 for P0 events). */
  consumerRejections: number;
  deadLettered: number;
  /** Rows in the sink — exactly one per committed envelope ID. */
  sinkRows: number;
  /** Max unpublished backlog observed across cycles (recovery ⇒ drains to 0). */
  backlogPeak: number;
  /** Final unpublished backlog — must be 0 (recovery to zero-lag). */
  backlogFinal: number;
  /** Cycles beyond the publish phase needed to reach zero-lag. */
  recoveryCycles: number;
  /** Outbox-append → sink-put per event (warehouse freshness evidence). */
  latencyMs: Percentiles;
  /** Committed events per wall-clock second across the whole run. */
  eps: number;
  wallMs: number;
  reconcile: Level1Report;
}

function nearestRank(sorted: number[], p: number): number {
  if (sorted.length === 0) return 0;
  const rank = Math.max(1, Math.ceil((p / 100) * sorted.length));
  return sorted[Math.min(rank, sorted.length) - 1]!;
}

export async function runTruthPathMatrix(
  config: MatrixConfig = DEFAULT_MATRIX_CONFIG,
): Promise<MatrixResult> {
  const store = new MemoryOutboxStore();
  const sink = new MemoryRawSink();
  const appendAtMs = new Map<string, number>();

  const started = performance.now();

  // ── Commit phase: business writes through the sanctioned emitter seam.
  for (let index = 0; index < config.totalEvents; index += 1) {
    const ctx = { aggregateId: `agg_${index}` };
    let envelope: SignalEnvelope;
    if (index % 3 === 0) {
      envelope = await emitPaymentSucceeded(store, ctx, {
        value: 100 + index,
        currency: 'INR',
        transaction_ref: `rzp_${index}`,
      });
    } else if (index % 3 === 1) {
      envelope = await emitPaymentFailed(store, ctx, {
        value: 100 + index,
        currency: 'INR',
        transaction_ref: `rzp_${index}`,
        failure_code: 'BANK_DECLINED',
      });
    } else {
      envelope = await emitBookingConfirmed(store, ctx, {
        value: 100 + index,
        currency: 'INR',
        service_category: 'puja',
        city: 'Pune',
      });
    }
    appendAtMs.set(envelope.id, performance.now());
  }
  const committedIds = store.snapshot().map((record) => record.id);

  // ── Publisher loop with the fault matrix.
  const latencies: number[] = [];
  const deliveredIds = new Set<string>(); // consumer-side delivery ledger
  let transientFaults = 0;
  let publishAcks = 0;
  let duplicateDeliveries = 0;
  let consumerRejections = 0;
  let deadLettered = 0;
  let backlogPeak = 0;

  // Sink wrapper records per-event freshness: append → durable put. Only
  // the FIRST put of an insertId is timed — duplicate redeliveries (crash
  // replay) are absorbed by dedup and are not fresh deliveries.
  const timedIds = new Set<string>();
  const timedSink = {
    put: async (rows: RawSignalRow[]): Promise<void> => {
      await sink.put(rows);
      const persistedAt = performance.now();
      for (const row of rows) {
        if (timedIds.has(row.insertId)) continue;
        timedIds.add(row.insertId);
        const enqueuedAt = appendAtMs.get(row.insertId);
        if (enqueuedAt !== undefined) latencies.push(persistedAt - enqueuedAt);
      }
    },
  };

  const publishCycles = Math.ceil(config.totalEvents / config.publisherBatch);
  const crashStart = Math.floor(publishCycles / 2);
  const crashEnd = crashStart + config.crashReplayCycles;
  const totalCycles = publishCycles + config.maxRecoveryCycles;

  let cycle = 0;
  for (; cycle < totalCycles; cycle += 1) {
    const inFault = cycle < config.faultCycles;
    const inCrash = cycle >= crashStart && cycle < crashEnd;
    const acceptedThisCycle: PublishedMessage[] = [];

    const result = await runPublisherOnce(
      store,
      {
        publish: async (message: PublishedMessage): Promise<void> => {
          if (inFault) {
            transientFaults += 1;
            throw new Error('pubsub unavailable (drill)');
          }
          // Accepted by Pub/Sub…
          acceptedThisCycle.push(message);
          if (inCrash) {
            // …then the worker dies before acking: at-least-once redelivery.
            throw new Error('CRASH_BEFORE_ACK');
          }
        },
      },
      {
        batch: config.publisherBatch,
        maxAttempts: config.maxAttempts,
        onDeadLettered: () => {
          deadLettered += 1;
        },
      },
    );
    publishAcks += result.published;

    // Push-consumer leg: ingest this cycle's accepted messages as one batch.
    if (acceptedThisCycle.length > 0) {
      for (const message of acceptedThisCycle) {
        if (deliveredIds.has(message.id)) duplicateDeliveries += 1;
        deliveredIds.add(message.id);
      }
      // Delivered WITH duplicates on purpose — the sink's insertId dedup is
      // the exactly-once authority, not the consumer's bookkeeping.
      const ingest = await ingestBatch(
        acceptedThisCycle.map((message) => message.payload),
        { sink: timedSink, source: 'server' },
      );
      if (!ingest.ok) consumerRejections += ingest.rejected.length;
    }

    const backlog = config.totalEvents - publishAcks - deadLettered;
    backlogPeak = Math.max(backlogPeak, backlog);
    if (publishAcks >= config.totalEvents - deadLettered) break;
  }

  const recoveryCycles = cycle >= publishCycles ? cycle - publishCycles + 1 : 0;
  const backlogFinal = config.totalEvents - publishAcks - deadLettered;
  const wallMs = performance.now() - started;
  const sortedLatency = latencies.slice().sort((a, b) => a - b);

  const reconcile = reconcileLevel1({
    committedIds,
    publishedIds: store
      .snapshot()
      .filter((record) => record.published)
      .map((record) => record.id),
    persistedIds: sink.all().map((row) => row.insertId),
  });

  return {
    committed: committedIds.length,
    publishAcks,
    transientFaults,
    duplicateDeliveries,
    consumerRejections,
    deadLettered,
    sinkRows: sink.size(),
    backlogPeak,
    backlogFinal,
    recoveryCycles,
    latencyMs: {
      count: sortedLatency.length,
      p50: nearestRank(sortedLatency, 50),
      p95: nearestRank(sortedLatency, 95),
      p99: nearestRank(sortedLatency, 99),
      max: sortedLatency.length > 0 ? sortedLatency[sortedLatency.length - 1]! : 0,
    },
    eps: wallMs > 0 ? (config.totalEvents * 1000) / wallMs : 0,
    wallMs,
    reconcile,
  };
}
