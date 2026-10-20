// Client SDK delivery paths (plan B3): primary fetch-with-ack, unload
// sendBeacon best-effort, per-batch consent token + provenance source, and
// the delivery metric defined as acknowledged ÷ eligible-attempted with
// beacon sends EXCLUDED from the denominator (§0.3 rule 6).

import { afterEach, describe, expect, it, vi } from 'vitest';
import { issueConsentToken, verifyConsentToken } from '@navdhya/analytics/src/contracts/consent';
import type { SignalEnvelope } from '@navdhya/analytics/src/contracts/envelope';
import { createAnalyticsClient, type DeliveryMetrics } from '@navdhya/analytics/src/client/analytics-client';
import { MemoryOutbox } from '@navdhya/analytics/src/client/outbox';

const NOW_SEC = 1_780_000_000;
const SECRET = 'consent-secret';

function grantedConsent() {
  return { analytics: 'GRANTED' as const, ads: 'DENIED' as const };
}

async function token(analytics = true): Promise<string> {
  return issueConsentToken(
    { v: 1, sub: null, analytics, ads: false, iat: NOW_SEC - 60, exp: NOW_SEC + 300 },
    SECRET,
  );
}

interface CapturedCall {
  url: string;
  body: string;
}

function capturingSend() {
  const batches: Array<{ body: string }> = [];
  let failNext = 0;
  return {
    batches,
    failNextTimes(n: number) {
      failNext = n;
    },
    send: async (batch: SignalEnvelope[]): Promise<void> => {
      if (failNext > 0) {
        failNext -= 1;
        throw new Error('collector 503');
      }
      batches.push({ body: JSON.stringify({ events: batch }) });
    },
  };
}

afterEach(() => {
  vi.unstubAllGlobals();
});

describe('client delivery paths (B3)', () => {
  it('primary fetch path sends {events, source, consentToken} and acks only on 200', async () => {
    const outbox = new MemoryOutbox();
    const calls: CapturedCall[] = [];
    vi.stubGlobal(
      'fetch',
      vi.fn(async (url: RequestInfo | URL, init?: RequestInit) => {
        calls.push({ url: String(url), body: String(init?.body) });
        return new Response(JSON.stringify({ ok: true, persisted: 1 }), { status: 200 });
      }),
    );

    const client = createAnalyticsClient({
      enabled: true,
      surface: 'web',
      appVersion: '1.0.0',
      collectorUrl: '/api/ae/v1/batch',
      consent: grantedConsent,
      outbox,
      source: 'shadow',
      getConsentToken: () => token(),
      onWarn: () => {},
    });

    expect(client.track('page_view', { page_type: 'home' })).toBe(true);
    await vi.waitFor(async () => expect(await outbox.size()).toBe(1));

    const result = await client.flush();
    expect(result).toEqual({ sent: 1, failed: 0 });
    expect(await outbox.size()).toBe(0); // positive ack → removed

    expect(calls).toHaveLength(1);
    expect(calls[0]!.url).toBe('/api/ae/v1/batch');
    const body = JSON.parse(calls[0]!.body) as {
      events: SignalEnvelope[];
      source: string;
      consentToken: string;
    };
    expect(body.events).toHaveLength(1);
    expect(body.events[0]!.name).toBe('page_view');
    expect(body.source).toBe('shadow');
    const verified = await verifyConsentToken(body.consentToken, SECRET, NOW_SEC);
    expect(verified?.analytics).toBe(true);

    expect(client.metrics()).toMatchObject({ acknowledged: 1, eligibleAttempted: 1 });
    expect(client.deliveryRatio()).toBe(1);
  });

  it('failed flush keeps events queued and counts both sides of the metric', async () => {
    const outbox = new MemoryOutbox();
    const harness = capturingSend();
    const client = createAnalyticsClient({
      enabled: true,
      surface: 'web',
      appVersion: '1.0.0',
      consent: grantedConsent,
      outbox,
      send: harness.send,
      onWarn: () => {},
    });

    expect(client.track('page_view', { page_type: 'home' })).toBe(true);
    await vi.waitFor(async () => expect(await outbox.size()).toBe(1));

    harness.failNextTimes(1);
    const first = await client.flush();
    expect(first).toEqual({ sent: 0, failed: 1 });
    expect(await outbox.size()).toBe(1); // NOT acked — remains queued

    const second = await client.flush();
    expect(second).toEqual({ sent: 1, failed: 0 });
    expect(await outbox.size()).toBe(0);

    const metrics: DeliveryMetrics = client.metrics();
    expect(metrics).toEqual({
      eligibleAttempted: 2,
      acknowledged: 1,
      failed: 1,
      beaconBestEffort: 0,
      shed: 0,
    });
    expect(client.deliveryRatio()).toBeCloseTo(0.5);
  });

  it('beacon path is best-effort: never acks, never enters the SLO denominator', async () => {
    const outbox = new MemoryOutbox();
    const beacons: string[] = [];
    const client = createAnalyticsClient({
      enabled: true,
      surface: 'web',
      appVersion: '1.0.0',
      consent: grantedConsent,
      outbox,
      collectorUrl: '/api/ae/v1/batch',
      send: async () => {},
      beacon: (_url, body) => {
        beacons.push(body);
        return true;
      },
      autoBeacon: false,
      onWarn: () => {},
    });

    expect(client.track('page_view', { page_type: 'home' })).toBe(true);
    await vi.waitFor(async () => expect(await outbox.size()).toBe(1));

    const offered = await client.flushBeacon();
    expect(offered).toBe(1);
    expect(beacons).toHaveLength(1);
    expect(beacons[0]).toContain('page_view');

    // Unacknowledged: still queued for the next page (dedup absorbs replay).
    expect(await outbox.size()).toBe(1);
    const metrics = client.metrics();
    expect(metrics.beaconBestEffort).toBe(1);
    expect(metrics.eligibleAttempted).toBe(0); // excluded from the SLO denominator
    expect(client.deliveryRatio()).toBeNull();

    // The primary path still owns the ack:
    const flush = await client.flush();
    expect(flush.sent).toBe(1);
    expect(client.metrics().eligibleAttempted).toBe(1);
  });

  it('rejected events never reach attempted metrics (denylist stays a denylist)', async () => {
    const outbox = new MemoryOutbox();
    const harness = capturingSend();
    const client = createAnalyticsClient({
      enabled: true,
      surface: 'web',
      appVersion: '1.0.0',
      consent: grantedConsent, // GRANTED — so only registry rules can reject
      outbox,
      send: harness.send,
      onWarn: () => {},
    });

    expect(client.track('payment_succeeded', { value: 1, currency: 'INR', transaction_ref: 'x' })).toBe(false); // server-owned
    expect(client.track('not_a_real_event', {})).toBe(false); // unknown
    expect(await outbox.size()).toBe(0);
    expect(client.metrics().eligibleAttempted).toBe(0);
  });
});
