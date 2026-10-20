// Ingestion core (plan B2, item 4): validate → stamp → sink.
//
// Fail-closed rules:
//   - Unknown event names and schema-invalid envelopes are REJECTED (never
//     silently dropped, never inserted) and surfaced to the dead-letter seam.
//   - All-or-nothing per batch: if ANY event is invalid nothing is persisted;
//     valid batchmates are reported as `deferred` so the sender can retry
//     without the rejected indexes (documented in plan B3).
//   - The durable-acceptance ack (`ok: true` AFTER sink.put resolves) is the
//     only success signal the collector may trust — no ack-on-schedule.
//   - Duplicate deliveries are absorbed at the sink by insertId (envelope
//     ID); persistence counts are therefore idempotent under replay.

import { validateEnvelope, type SignalEnvelope } from '@navdhya/analytics/src/contracts/envelope';
import { validateEventParams } from '@navdhya/analytics/src/registry/generated/validator';
import { toRawRow, type RawSink, type SignalSource } from './raw-sink';

export interface RejectedEvent {
  index: number;
  name?: unknown;
  errors: string[];
}

export interface IngestDeps {
  sink: RawSink;
  source: SignalSource;
  nowMs?: () => number;
  /** Dead-letter seam: every rejected event is reported here explicitly. */
  onDeadLetter?: (rejected: RejectedEvent, envelope: unknown) => Promise<void> | void;
}

export interface IngestResult {
  ok: boolean;
  persisted: number;
  rejected: RejectedEvent[];
  /** Valid events withheld by all-or-nothing — sender retries these later. */
  deferred: number;
}

export async function ingestBatch(events: unknown, deps: IngestDeps): Promise<IngestResult> {
  if (!Array.isArray(events)) {
    const rejected: RejectedEvent = { index: -1, errors: ['batch: events must be an array'] };
    await deps.onDeadLetter?.(rejected, events);
    return { ok: false, persisted: 0, rejected: [rejected], deferred: 0 };
  }

  const receivedAt = new Date(deps.nowMs?.() ?? Date.now()).toISOString();
  const rejected: RejectedEvent[] = [];
  const valid: Array<{ index: number; envelope: SignalEnvelope }> = [];

  events.forEach((candidate, index) => {
    const structural = validateEnvelope(candidate);
    if (structural.length > 0) {
      rejected.push({
        index,
        name: (candidate as { name?: unknown } | null)?.name,
        errors: structural,
      });
      return;
    }
    const envelope = candidate as SignalEnvelope;
    const paramErrors = validateEventParams(envelope.name, envelope.params);
    if (paramErrors.length > 0) {
      rejected.push({ index, name: envelope.name, errors: paramErrors });
      return;
    }
    valid.push({ index, envelope });
  });

  if (rejected.length > 0) {
    for (const item of rejected) {
      await deps.onDeadLetter?.(item, events[item.index]);
    }
    return { ok: false, persisted: 0, rejected, deferred: valid.length };
  }

  const rows = valid.map(({ envelope }) =>
    toRawRow(envelope, { source: deps.source, receivedAt }),
  );
  await deps.sink.put(rows); // durable acceptance — only now may we ack
  return { ok: true, persisted: rows.length, rejected: [], deferred: 0 };
}
