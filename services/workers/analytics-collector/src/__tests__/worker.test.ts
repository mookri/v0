// Worker adapter contract (plan B3b): same status map as the Edge route —
// the pipeline is shared, these tests prove the adapter wiring.

import { describe, expect, it } from 'vitest';
import { issueConsentToken } from '@navdhya/analytics/src/contracts/consent';
import { newEventId, type SignalEnvelope } from '@navdhya/analytics/src/contracts/envelope';
import { handleCollectorWorker, type AnalyticsCollectorEnv, type WorkerOverrides } from '../worker';

const SECRET = 'worker-test-secret';
const NOW_SEC = 1_780_000_000;

function envelope(name = 'page_view', params: Record<string, string | number | boolean> = { page_type: 'home' }): SignalEnvelope {
  return {
    id: newEventId(),
    name,
    version: 1,
    occurredAt: '2026-10-12T10:00:00.000Z',
    surface: 'web',
    appVersion: '1.0.0',
    session: { id: `sess_${name}`, seq: 1 },
    actor: { uidHash: null, anonId: null },
    consent: { analytics: true, ads: false },
    context: { routeTemplate: '/' },
    params,
  };
}

async function consentToken(analytics = true): Promise<string> {
  return issueConsentToken(
    { v: 1, sub: null, analytics, ads: false, iat: NOW_SEC - 60, exp: NOW_SEC + 300 },
    SECRET,
  );
}

const ENV: AnalyticsCollectorEnv = {
  SIGNALS_HMAC_SECRET: SECRET,
  SIGNALS_CONSENT_TOKEN_SECRET: SECRET,
  SIGNALS_INGEST_URL: 'https://ingestion.test/ingest',
};

interface Captured {
  url: string;
  headers: Record<string, string>;
  body: string;
}

function capturingFetch(status = 200, responseBody = '{"ok":true,"persisted":1}') {
  const calls: Captured[] = [];
  const overrides: WorkerOverrides = {
    fetchImpl: async (url, init) => {
      calls.push({ url, headers: init.headers, body: init.body });
      return { status, text: async () => responseBody };
    },
    nowSec: () => NOW_SEC,
    nonce: () => 'fixed-nonce',
  };
  return { calls, overrides };
}

function post(body: unknown, init: RequestInit = {}): Request {
  return new Request('https://collector.test/', {
    method: 'POST',
    body: typeof body === 'string' ? body : JSON.stringify(body),
    headers: { 'content-type': 'application/json', ...(init.headers ?? {}) },
    ...init,
  });
}

describe('analytics-collector worker adapter (B3b)', () => {
  it('forwards a valid consented batch and returns the durable ack as 200', async () => {
    const { calls, overrides } = capturingFetch();
    const response = await handleCollectorWorker(
      post({ events: [envelope()], consentToken: await consentToken() }),
      ENV,
      overrides,
    );

    expect(response.status).toBe(200);
    expect(calls).toHaveLength(1);
    expect(calls[0]!.url).toBe(ENV.SIGNALS_INGEST_URL);
    expect(calls[0]!.headers['x-signals-auth']).toMatch(/^t=\d+,n=fixed-nonce,v1=[0-9a-f]{64}$/);
    expect(calls[0]!.headers['x-signals-source']).toBe('shadow'); // B3 default
    expect(JSON.parse(calls[0]!.body).events).toHaveLength(1);
    expect(await response.json()).toMatchObject({ ok: true, source: 'shadow' });
  });

  it('rejects non-POST with 405 before touching anything', async () => {
    const { calls, overrides } = capturingFetch();
    const response = await handleCollectorWorker(
      new Request('https://collector.test/', { method: 'GET' }),
      ENV,
      overrides,
    );
    expect(response.status).toBe(405);
    expect(calls).toHaveLength(0);
  });

  it('rejects a declared oversize body with 413 before reading it', async () => {
    const { calls, overrides } = capturingFetch();
    const response = await handleCollectorWorker(
      post('{}', { headers: { 'content-length': String(70 * 1024) } }),
      ENV,
      overrides,
    );
    expect(response.status).toBe(413);
    expect(calls).toHaveLength(0);
  });

  it('fails closed with 503 when secrets are unset', async () => {
    const { calls, overrides } = capturingFetch();
    const response = await handleCollectorWorker(post({ events: [envelope()] }), {}, overrides);
    expect(response.status).toBe(503);
    expect(calls).toHaveLength(0);
  });

  it('returns 403 and forwards nothing without valid consent evidence', async () => {
    const { calls, overrides } = capturingFetch();
    const response = await handleCollectorWorker(
      post({ events: [envelope()], consentToken: 'garbage.token' }),
      ENV,
      overrides,
    );
    expect(response.status).toBe(403);
    expect(calls).toHaveLength(0);
  });

  it('maps an upstream failure to 503 — never an unbacked 200', async () => {
    const { calls, overrides } = capturingFetch(500, 'upstream boom');
    const response = await handleCollectorWorker(
      post({ events: [envelope()], consentToken: await consentToken() }),
      ENV,
      overrides,
    );
    expect(response.status).toBe(503);
    expect(calls).toHaveLength(1); // attempted, not acked
  });

  it('stamps an explicit env source and rejects an invalid one', async () => {
    const { calls, overrides } = capturingFetch();
    await handleCollectorWorker(
      post({ events: [envelope()], consentToken: await consentToken(), source: 'first_party' }),
      { ...ENV, SIGNALS_SOURCE_DEFAULT: 'ga4_export' }, // configured, but batch declares first_party
      overrides,
    );
    expect(calls[0]!.headers['x-signals-source']).toBe('first_party');
    expect(calls[0]!.body).toContain('page_view');

    const rejected = await handleCollectorWorker(
      post({ events: [envelope()], consentToken: await consentToken(), source: 'not_a_source' }),
      ENV,
      overrides,
    );
    expect(rejected.status).toBe(400);
  });
});
