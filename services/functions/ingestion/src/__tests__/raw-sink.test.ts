// signals_raw sink: insertId dedup, enforced source, BigQuery adapter shape.

import { describe, expect, it, vi } from 'vitest';
import {
  BigQueryRawSink,
  MemoryRawSink,
  SIGNAL_SOURCES,
  toRawRow,
  type RawSignalRow,
} from '../raw-sink';
import { validEnvelope } from './fixtures';

describe('toRawRow', () => {
  it('keys rows by envelope ID and carries the enforced source column', () => {
    const row = toRawRow(validEnvelope(), { source: 'server', receivedAt: '2026-10-12T10:00:01.000Z' });
    expect(row.insertId).toBe('FIXTURE000000000000000000A1');
    expect(row.json.source).toBe('server');
    expect(row.json.name).toBe('page_view');
    expect(row.json.session_seq).toBe(1);
    expect(row.json.consent_analytics).toBe(true);
    expect(row.json.received_at).toBe('2026-10-12T10:00:01.000Z');
    expect(row.json.event_date).toBe('2026-10-12'); // partition key (plan B5)
  });

  it('rejects an invalid source before anything reaches the sink', () => {
    expect(() => toRawRow(validEnvelope(), { source: 'vibes' as never, receivedAt: 'x' })).toThrow(
      'invalid source',
    );
    expect(SIGNAL_SOURCES).toEqual(['first_party', 'server', 'shadow', 'ga4_export']);
  });
});

describe('MemoryRawSink', () => {
  it('dedups by insertId — same ID twice, one row', async () => {
    const sink = new MemoryRawSink();
    const row: RawSignalRow = { insertId: 'a', json: { id: 'a', source: 'server' } };
    await sink.put([row]);
    await sink.put([{ ...row, json: { id: 'a', source: 'server', tampered: true } }]);
    expect(sink.size()).toBe(1);
    expect(sink.all()[0]!.json.tampered).toBeUndefined(); // first write wins
  });
});

describe('BigQueryRawSink', () => {
  function fakeFetch(response: { ok: boolean; status?: number; json?: unknown }) {
    const calls: Array<{ url: string; init: RequestInit }> = [];
    const fn = vi.fn(async (url: string, init: RequestInit) => {
      calls.push({ url, init });
      return { ok: response.ok, status: response.status ?? 200, json: async () => response.json ?? {} } as Response;
    }) as unknown as typeof fetch;
    return { calls, fn };
  }

  const row = () => toRawRow(validEnvelope(), { source: 'first_party', receivedAt: 'r' });

  it('streaming-inserts with insertId + bearer auth', async () => {
    const { calls, fn } = fakeFetch({ ok: true, json: {} });
    const sink = new BigQueryRawSink({
      projectId: 'p',
      dataset: 'signals_raw',
      table: 'events',
      accessToken: async () => 'tok',
      fetchFn: fn,
    });

    await sink.put([row()]);

    expect(calls[0]!.url).toBe(
      'https://bigquery.googleapis.com/bigquery/v2/projects/p/datasets/signals_raw/tables/events/insertAll',
    );
    expect((calls[0]!.init.headers as Record<string, string>).Authorization).toBe('Bearer tok');
    const body = JSON.parse(String(calls[0]!.init.body)) as { rows: Array<{ insertId: string }> };
    expect(body.rows[0]!.insertId).toBe('FIXTURE000000000000000000A1');
  });

  it('throws on transport failure, partial insertErrors, and invalid source (no ack)', async () => {
    const down = fakeFetch({ ok: false, status: 403 });
    const s1 = new BigQueryRawSink({ projectId: 'p', dataset: 'd', table: 't', accessToken: async () => 'x', fetchFn: down.fn });
    await expect(s1.put([row()])).rejects.toThrow('BQ_INSERT_FAILED:403');

    const partial = fakeFetch({ ok: true, json: { insertErrors: [{ index: 0 }] } });
    const s2 = new BigQueryRawSink({ projectId: 'p', dataset: 'd', table: 't', accessToken: async () => 'x', fetchFn: partial.fn });
    await expect(s2.put([row()])).rejects.toThrow('BQ_INSERT_ERRORS:1');

    const { calls, fn } = fakeFetch({ ok: true, json: {} });
    const s3 = new BigQueryRawSink({ projectId: 'p', dataset: 'd', table: 't', accessToken: async () => 'x', fetchFn: fn });
    await expect(s3.put([{ insertId: 'x', json: { source: 'vibes' } }])).rejects.toThrow('invalid source');
    expect(calls).toHaveLength(0); // rejected before any network call
  });

  it('requires the target config', () => {
    expect(() => new BigQueryRawSink({ projectId: '', dataset: 'd', table: 't', accessToken: async () => 'x' })).toThrow(
      'BQ_TARGET_MISSING',
    );
  });
});
