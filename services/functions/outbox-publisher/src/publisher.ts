// Outbox → Pub/Sub publisher (plan B2, item 3).
//
// Semantics:
//   - claim() with lease → publish → markPublished (ack ONLY after Pub/Sub
//     acceptance — never ack-on-scheduled-work).
//   - Transient transport failure → release() → immediate retry next run;
//     record.attempts (incremented at claim) bounds poison records.
//   - Attempts ceiling → markDeadLettered() + onDeadLettered hook (the
//     dead-letter topic + alert seam). Terminal: never claimed again.
//   - Process crash between publish and markPublished → lease expires →
//     redelivery; the DUPLICATE publish is absorbed downstream by
//     envelope-ID dedup at the sink (insertId) — effects stay exactly-once
//     even though delivery is at-least-once.

import type { OutboxRecord, OutboxStore } from '@navdhya/contracts/src/messaging/outbox';

export interface PublishedMessage {
  /** Stable event ID (envelope ULID) — the end-to-end dedup key. */
  id: string;
  type: string;
  payload: unknown;
}

export interface EventTransport {
  publish(message: PublishedMessage): Promise<void>;
}

export interface PublisherOptions {
  /** Max records per claim cycle. Default 50. */
  batch?: number;
  /** Lease duration ms; expired claims are redelivered. Default 30s. */
  leaseMs?: number;
  /** attempts ceiling before dead-lettering. Default 5. */
  maxAttempts?: number;
  /** Dead-letter topic + alert seam, invoked once per dead-lettered record. */
  onDeadLettered?: (record: OutboxRecord) => Promise<void> | void;
}

export interface PublisherResult {
  published: number;
  released: number;
  deadLettered: number;
}

export async function runPublisherOnce(
  store: OutboxStore,
  transport: EventTransport,
  options: PublisherOptions = {},
): Promise<PublisherResult> {
  const batch = options.batch ?? 50;
  const leaseMs = options.leaseMs ?? 30_000;
  const maxAttempts = options.maxAttempts ?? 5;
  const result: PublisherResult = { published: 0, released: 0, deadLettered: 0 };

  const records = await store.claim(batch, { leaseMs });
  for (const record of records) {
    try {
      await transport.publish({ id: record.id, type: record.type, payload: record.payload });
      await store.markPublished(record.id); // positive acceptance → ack
      result.published += 1;
    } catch {
      // attempts was incremented at claim — it counts attempts STARTED.
      if (record.attempts >= maxAttempts) {
        await store.markDeadLettered(record.id);
        result.deadLettered += 1;
        try {
          await options.onDeadLettered?.(record);
        } catch {
          // Alert transport failure must not resurrect a terminal record;
          // the gap report (Level-1) is the standing safety net.
        }
      } else {
        await store.release(record.id);
        result.released += 1;
      }
    }
  }
  return result;
}
