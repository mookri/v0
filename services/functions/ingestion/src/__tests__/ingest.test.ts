// B2 acceptance: schema rejection, all-or-nothing, dedup under replay,
// durable-ack semantics.

import { describe, expect, it } from 'vitest';
import { ingestBatch, type RejectedEvent } from '../ingest';
import { MemoryRawSink } from '../raw-sink';
import { validEnvelope } from './fixtures';

describe('ingestBatch', () => {
  it('persists a valid batch and stamps received_at + enforced source', async () => {
    const sink = new MemoryRawSink();
    const events = [
      validEnvelope(),
      validEnvelope({ id: 'FIXTURE000000000000000000A2', session: { id: 's', seq: 2 } }),
    ];
    const result = await ingestBatch(events, { sink, source: 'first_party', nowMs: () => 1_780_000_000_000 });

    expect(result).toEqual({ ok: true, persisted: 2, rejected: [], deferred: 0 });
    expect(sink.size()).toBe(2);
    const row = sink.all()[0]!;
    expect(row.insertId).toBe('FIXTURE000000000000000000A1');
    expect(row.json.received_at).toBe('2026-05-28T20:26:40.000Z'); // ISO of nowMs 1_780_000_000_000
    expect(row.json.source).toBe('first_party');
    expect(JSON.parse(String(row.json.params_json))).toEqual({ page_type: 'catalog' });
  });

  it('REJECTS unknown event names — never inserted, explicitly dead-lettered', async () => {
    const sink = new MemoryRawSink();
    const deadLettered: Array<{ rejected: RejectedEvent; envelope: unknown }> = [];
    const unknown = validEnvelope({ name: 'not_in_registry' });

    const result = await ingestBatch([unknown], {
      sink,
      source: 'first_party',
      onDeadLetter: (rejected, envelope) => void deadLettered.push({ rejected, envelope }),
    });

    expect(result.ok).toBe(false);
    expect(result.persisted).toBe(0);
    expect(result.rejected[0]!.errors.join(' ')).toContain('unknown event');
    expect(sink.size()).toBe(0); // never inserted
    expect(deadLettered).toHaveLength(1); // never silently dropped
    expect(deadLettered[0]!.envelope).toBe(unknown);
  });

  it('REJECTS structurally invalid envelopes and param/schema violations', async () => {
    const sink = new MemoryRawSink();
    const noSeq = validEnvelope();
    delete (noSeq.session as { seq?: number }).seq;
    const badParams = validEnvelope({
      id: 'FIXTURE000000000000000000A3',
      params: { page_type: 42 as unknown as string },
    });

    const result = await ingestBatch([noSeq, badParams], { sink, source: 'server' });

    expect(result.ok).toBe(false);
    expect(result.rejected).toHaveLength(2);
    expect(result.rejected[0]!.errors.join(' ')).toContain('session.seq');
    expect(result.rejected[1]!.errors.join(' ')).toContain('expected string');
    expect(sink.size()).toBe(0);
  });

  it('all-or-nothing: valid batchmates are deferred, not silently persisted', async () => {
    const sink = new MemoryRawSink();
    const events = [validEnvelope(), validEnvelope({ id: 'FIXTURE000000000000000000A4', name: 'bogus_event' })];

    const result = await ingestBatch(events, { sink, source: 'first_party' });

    expect(result.ok).toBe(false);
    expect(result.persisted).toBe(0);
    expect(result.deferred).toBe(1); // valid event explicitly reported for retry
    expect(sink.size()).toBe(0);
  });

  it('duplicate delivery → one row per envelope ID (insertId dedup) and a truthful ack', async () => {
    const sink = new MemoryRawSink();
    const events = [validEnvelope()];

    const first = await ingestBatch(events, { sink, source: 'first_party' });
    const replay = await ingestBatch(events, { sink, source: 'first_party' });

    expect(first).toEqual({ ok: true, persisted: 1, rejected: [], deferred: 0 });
    expect(replay).toEqual({ ok: true, persisted: 1, rejected: [], deferred: 0 }); // still durably accepted
    expect(sink.size()).toBe(1); // exactly one row
    expect(sink.putCalls).toBe(2); // dedup happened AT the sink, not by hiding the retry
  });

  it('rejects a non-array batch', async () => {
    const sink = new MemoryRawSink();
    const result = await ingestBatch('not-an-array', { sink, source: 'first_party' });
    expect(result.ok).toBe(false);
    expect(result.rejected[0]!.errors[0]).toContain('must be an array');
    expect(sink.putCalls).toBe(0);
  });
});
