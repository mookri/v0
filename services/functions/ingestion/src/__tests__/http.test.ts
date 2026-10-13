// HTTP semantics: 401 auth, 400 rejection with per-index errors, 200 only
// after durable acceptance.

import { describe, expect, it } from 'vitest';
import { handleIngestRequest } from '../main';
import { MemoryRawSink } from '../raw-sink';
import { MemoryNonceStore, signWorkerRequest } from '../worker-auth';
import { validEnvelope } from './fixtures';

const SECRET = 'worker-shared-secret';

function deps() {
  return {
    sink: new MemoryRawSink(),
    source: 'first_party' as const,
    workerSecret: SECRET,
    nonceStore: new MemoryNonceStore(),
  };
}

async function signed(body: string, nonce: string): Promise<string> {
  return signWorkerRequest(SECRET, body, { timestampSec: Math.floor(Date.now() / 1000), nonce });
}

describe('handleIngestRequest', () => {
  it('200: durable-acceptance ack only after the sink put', async () => {
    const d = deps();
    const body = JSON.stringify({ events: [validEnvelope()] });
    const out = await handleIngestRequest(body, await signed(body, 'n1'), d);
    expect(out).toEqual({ status: 200, body: { ok: true, persisted: 1 } });
    expect(d.sink.size()).toBe(1);
  });

  it('401: missing or invalid auth never reaches the sink', async () => {
    const d = deps();
    const body = JSON.stringify({ events: [validEnvelope()] });
    expect((await handleIngestRequest(body, null, d)).status).toBe(401);
    expect((await handleIngestRequest(body, 't=1,n=x', d)).status).toBe(401);
    const wrongSecret = await signWorkerRequest('other-secret', body, {
      timestampSec: Math.floor(Date.now() / 1000),
      nonce: 'n2',
    });
    expect(await handleIngestRequest(body, wrongSecret, d)).toEqual({
      status: 401,
      body: { ok: false, error: 'bad_signature' },
    });
    expect(d.sink.putCalls).toBe(0);
  });

  it('400: schema rejection returns per-index errors for the sender to drop + retry', async () => {
    const d = deps();
    const poison = { ...validEnvelope(), name: 'unknown_event' };
    const body = JSON.stringify({ events: [validEnvelope(), poison] });
    const out = await handleIngestRequest(body, await signed(body, 'n3'), d);
    expect(out.status).toBe(400);
    const b = out.body as { ok: boolean; rejected: Array<{ index: number }>; deferred: number };
    expect(b.ok).toBe(false);
    expect(b.rejected[0]!.index).toBe(1);
    expect(b.deferred).toBe(1);
    expect(d.sink.putCalls).toBe(0);
  });

  it('400: malformed JSON', async () => {
    const d = deps();
    const body = '{oops';
    const out = await handleIngestRequest(body, await signed(body, 'n4'), d);
    expect(out).toEqual({ status: 400, body: { ok: false, error: 'invalid_json' } });
  });

  it('replayed request → 401 (nonce store rejects the second delivery)', async () => {
    const d = deps();
    const body = JSON.stringify({ events: [validEnvelope()] });
    const header = await signed(body, 'n_replay');
    expect((await handleIngestRequest(body, header, d)).status).toBe(200);
    expect((await handleIngestRequest(body, header, d)).status).toBe(401);
    expect(d.sink.size()).toBe(1);
  });
});

describe('per-request source provenance (B3)', () => {
  it('honours a valid x-signals-source header and stamps rows with it', async () => {
    const d = deps();
    const body = JSON.stringify({ events: [validEnvelope()] });
    const out = await handleIngestRequest(body, await signed(body, 'n_src1'), d, {
      sourceHeader: 'shadow',
    });
    expect(out.status).toBe(200);
    expect(d.sink.all()[0]!.json['source']).toBe('shadow');
  });

  it('rejects an invalid source header instead of coercing it', async () => {
    const d = deps();
    const body = JSON.stringify({ events: [validEnvelope()] });
    const out = await handleIngestRequest(body, await signed(body, 'n_src2'), d, {
      sourceHeader: 'totally_not_a_source',
    });
    expect(out).toEqual({ status: 400, body: { ok: false, error: 'invalid_source' } });
    expect(d.sink.putCalls).toBe(0);
  });

  it('falls back to the server-configured source when the header is absent or empty', async () => {
    for (const [sourceHeader, nonce] of [
      [undefined, 'n_src3'],
      ['', 'n_src4'],
    ] as Array<[string | undefined, string]>) {
      const d = deps();
      const body = JSON.stringify({ events: [validEnvelope()] });
      const out = await handleIngestRequest(body, await signed(body, nonce), d, { sourceHeader });
      expect(out.status).toBe(200);
      expect(d.sink.all()[0]!.json['source']).toBe('first_party');
    }
  });
});
