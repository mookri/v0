// packages/analytics/src/client/__tests__/transport.test.ts
// B1 acceptance: batcher bounds (size + latency) and retry/backoff policy.

import { describe, expect, it } from 'vitest';
import { createBatcher } from '@navdhya/analytics/src/client/batcher';
import { nextBackoffMs } from '@navdhya/analytics/src/client/retry-policy';
import type { SignalEnvelope } from '@navdhya/analytics/src/contracts/envelope';

function env(seq: number): SignalEnvelope {
  return {
    id: `ID${seq}`,
    name: 'page_view',
    version: 1,
    occurredAt: '2026-10-12T10:00:00.000Z',
    surface: 'web',
    appVersion: '1.0.0-test',
    session: { id: 'sess_b', seq },
    actor: { uidHash: null, anonId: 'anon_b' },
    consent: { analytics: true, ads: false },
    context: { routeTemplate: '/' },
    params: { page_type: 'catalog' },
  };
}

describe('batcher', () => {
  it('splits into batches of at most maxBatch on flush', async () => {
    const sent: number[] = [];
    const failures: SignalEnvelope[][] = [];
    const b = createBatcher({
      maxBatch: 25,
      send: async (batch) => void sent.push(batch.length),
      onFailed: (batch) => void failures.push(batch),
    });
    for (let i = 0; i < 30; i++) b.push(env(i + 1));
    expect(b.buffered()).toBe(30);
    await b.flush();
    expect(sent).toEqual([25, 5]);
    expect(b.buffered()).toBe(0);
    expect(failures).toEqual([]);
  });

  it('routes failed sends to onFailed — never silently drops', async () => {
    const failures: SignalEnvelope[][] = [];
    const b = createBatcher({
      maxBatch: 10,
      send: async () => {
        throw new Error('down');
      },
      onFailed: (batch) => void failures.push(batch),
    });
    b.push(env(1));
    b.push(env(2));
    await b.flush();
    expect(failures.flat().map((e) => e.session.seq)).toEqual([1, 2]);
    expect(b.buffered()).toBe(0); // removed from buffer; re-queue is the outbox owner's job (peek/ack)
  });

  it('flushes within maxWaitMs latency bound', async () => {
    const sent: number[] = [];
    const b = createBatcher({
      maxBatch: 25,
      maxWaitMs: 10,
      send: async (batch) => void sent.push(batch.length),
      onFailed: () => {},
    });
    b.push(env(1));
    expect(sent).toEqual([]);
    await new Promise((r) => setTimeout(r, 40));
    expect(sent).toEqual([1]);
  });
});

describe('retry policy (exponential + full jitter)', () => {
  it('grows exponentially with jitter at the top of the range', () => {
    expect(nextBackoffMs(0, { random: () => 1 })).toBe(500);
    expect(nextBackoffMs(1, { random: () => 1 })).toBe(1000);
    expect(nextBackoffMs(3, { random: () => 1 })).toBe(4000);
  });

  it('caps at maxMs', () => {
    expect(nextBackoffMs(20, { random: () => 1 })).toBe(30_000);
  });

  it('jitter scales within [floor, 1] of the computed delay', () => {
    expect(nextBackoffMs(0, { random: () => 0, jitter: 0.5 })).toBe(250);
    expect(nextBackoffMs(0, { random: () => 0, jitter: 1 })).toBe(0);
  });

  it('rejects invalid attempts', () => {
    expect(() => nextBackoffMs(-1)).toThrow();
    expect(() => nextBackoffMs(1.5)).toThrow();
  });
});
