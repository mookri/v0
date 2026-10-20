// B3 acceptance — interim first-party collector (edge contract, decision #5).
// Acceptance items from the plan, proven in-process:
//   - oversize batch (61 KiB+, 26 events) → 413/400, no partial accept
//   - unknown event → 400 naming the offender; valid batchmates not forwarded
//   - replay of an identical batch → transport 200 twice, ZERO duplicate rows
//     in signals_raw (insertId dedup verified through real ingestion code)
//   - consent-token rejection → 403, event not forwarded
//   - transport failure / upstream 5xx / upstream 401 → 503, never an
//     unbacked 200 (200 is only ever the durable-acceptance ack)
// Plus the cross-package contract: signatures produced by the collector
// (packages/analytics) verify at the ingestion service (services/functions)
// — byte-compatible with decision #6.

import { describe, expect, it, vi } from 'vitest';
import { handleCollectorRequest } from '@navdhya/analytics/src/collector/pipeline';
import { issueConsentToken } from '@navdhya/analytics/src/contracts/consent';
import { newEventId } from '@navdhya/analytics/src/contracts/envelope';
import { handleIngestRequest } from '@services/functions/ingestion/src/main';
import { MemoryRawSink } from '@services/functions/ingestion/src/raw-sink';
import { MemoryNonceStore, verifyWorkerAuth } from '@services/functions/ingestion/src/worker-auth';
import { POST as batchRoute } from '@apps/web/app/api/ae/v1/batch/route';
import {
  COLLECTOR_SECRET,
  batchBody,
  clientEnvelope,
  collectorDeps,
  consentToken,
} from './helpers/collector-fixtures';

describe('collector pipeline acceptance (B3a)', () => {
  it('forwards a valid consented batch and only answers 200 on the durable ack', async () => {
    const { deps, forwards } = collectorDeps();
    const response = await handleCollectorRequest(
      {
        bodyText: batchBody([clientEnvelope()], { consentToken: await consentToken() }),
        contentLength: null,
        clientIp: '203.0.113.1',
      },
      deps,
    );

    expect(response.status).toBe(200);
    expect(response.body).toMatchObject({ ok: true, persisted: 1, source: 'shadow' });
    expect(forwards).toHaveLength(1);
    expect(forwards[0]!.headers['x-signals-auth']).toMatch(/^t=\d+,n=nonce_\d+,v1=[0-9a-f]{64}$/);
    expect(forwards[0]!.headers['x-signals-source']).toBe('shadow');
    expect(JSON.parse(forwards[0]!.body).events).toHaveLength(1);
  });

  it('cross-contract: its forwarded signature verifies at the ingestion service', async () => {
    const { deps, forwards } = collectorDeps();
    await handleCollectorRequest(
      {
        bodyText: batchBody([clientEnvelope()], { consentToken: await consentToken() }),
        contentLength: null,
        clientIp: '203.0.113.2',
      },
      deps,
    );
    const forward = forwards[0]!;
    const verdict = await verifyWorkerAuth(forward.headers['x-signals-auth'], forward.body, {
      secret: COLLECTOR_SECRET,
      nonceStore: new MemoryNonceStore(),
      nowSec: Math.floor(Date.now() / 1000),
    });
    expect(verdict).toEqual({ ok: true, timestampSec: expect.any(Number) });
  });

  it('REPLAY: identical batch twice → 200 both times, zero duplicate rows', async () => {
    const sink = new MemoryRawSink();
    const nonceStore = new MemoryNonceStore();
    const { deps } = collectorDeps({
      // Real ingestion path behind a fake transport — the whole truth chain.
      fetchImpl: async (_url, init) => {
        const result = await handleIngestRequest(
          init.body,
          init.headers['x-signals-auth'],
          {
            sink,
            source: 'shadow',
            nowMs: Date.now,
            workerSecret: COLLECTOR_SECRET,
            nonceStore,
          },
          { sourceHeader: init.headers['x-signals-source'] },
        );
        return { status: result.status, text: async () => JSON.stringify(result.body) };
      },
    });

    const envelope = clientEnvelope({ id: newEventId(), session: { id: 'sess_replay', seq: 1 } });
    const body = batchBody([envelope], { consentToken: await consentToken() });
    const request = { bodyText: body, contentLength: null, clientIp: '203.0.113.3' };

    const first = await handleCollectorRequest(request, deps);
    const second = await handleCollectorRequest(request, deps);

    expect(first.status).toBe(200);
    expect(second.status).toBe(200); // transport idempotent — replay is safe
    expect(sink.size()).toBe(1); // insertId dedup: exactly one row per envelope ID
    expect(sink.all()[0]!.json['source']).toBe('shadow');
  });

  it('oversize: declared >64 KiB → 413 before any work; 26 events → 400, no partial accept', async () => {
    const { deps, forwards } = collectorDeps();
    const oversize = await handleCollectorRequest(
      { bodyText: 'x'.repeat(70 * 1024), contentLength: null, clientIp: '203.0.113.4' },
      deps,
    );
    expect(oversize.status).toBe(413);

    const declared = await handleCollectorRequest(
      { bodyText: '{}', contentLength: String(70 * 1024), clientIp: '203.0.113.4' },
      deps,
    );
    expect(declared.status).toBe(413);

    const token = await consentToken();
    const twentySix = Array.from({ length: 26 }, () => clientEnvelope());
    const tooMany = await handleCollectorRequest(
      { bodyText: batchBody(twentySix, { consentToken: token }), contentLength: null, clientIp: '203.0.113.4' },
      deps,
    );
    expect(tooMany.status).toBe(400);
    expect(tooMany.body).toMatchObject({ error: 'batch_too_large', count: 26, max: 25 });
    expect(forwards).toHaveLength(0); // nothing forwarded, nothing partially accepted
  });

  it('unknown event → 400 naming the offender; valid batchmates are NOT forwarded', async () => {
    const { deps, forwards } = collectorDeps();
    const response = await handleCollectorRequest(
      {
        bodyText: batchBody(
          [clientEnvelope({ session: { id: 'sess_offender', seq: 1 } }), clientEnvelope({ name: 'totally_bogus_event', session: { id: 'sess_offender', seq: 2 } })],
          { consentToken: await consentToken() },
        ),
        contentLength: null,
        clientIp: '203.0.113.5',
      },
      deps,
    );

    expect(response.status).toBe(400);
    const rejected = response.body['rejected'] as Array<{ index: number; name: string; errors: string[] }>;
    expect(rejected).toHaveLength(1);
    expect(rejected[0]!.index).toBe(1);
    expect(rejected[0]!.name).toBe('totally_bogus_event');
    expect(rejected[0]!.errors.join(' ')).toContain('totally_bogus_event');
    expect(response.body['deferred']).toBe(1); // batchmate reported, never silently accepted
    expect(forwards).toHaveLength(0);
  });

  it('delivery-policy gates: server P0 and internal_only ad events may not enter', async () => {
    const { deps, forwards } = collectorDeps();
    const token = await consentToken();

    const serverEvent = await handleCollectorRequest(
      {
        bodyText: batchBody(
          [clientEnvelope({ name: 'payment_succeeded', params: { value: 1, currency: 'INR', transaction_ref: 'TX1' } })],
          { consentToken: token },
        ),
        contentLength: null,
        clientIp: '203.0.113.6',
      },
      deps,
    );
    expect(serverEvent.status).toBe(400);
    expect(JSON.stringify(serverEvent.body)).toContain('server-produced');

    const adEvent = await handleCollectorRequest(
      { bodyText: batchBody([clientEnvelope({ name: 'ad_click', params: {} })], { consentToken: token }), contentLength: null, clientIp: '203.0.113.6' },
      deps,
    );
    expect(adEvent.status).toBe(400);
    expect(JSON.stringify(adEvent.body)).toContain('not collector-eligible');

    const wrongSurface = await handleCollectorRequest(
      { bodyText: batchBody([clientEnvelope({ name: 'kyc_submitted', surface: 'web', params: {} })], { consentToken: token }), contentLength: null, clientIp: '203.0.113.6' },
      deps,
    );
    expect(wrongSurface.status).toBe(400);
    expect(JSON.stringify(wrongSurface.body)).toContain('not permitted on surface');
    expect(forwards).toHaveLength(0);
  });

  it('consent is the evidence: missing / forged / expired / denied → 403, nothing forwarded', async () => {
    const { deps, forwards } = collectorDeps();
    const event = () => [clientEnvelope({ session: { id: 'sess_consent', seq: 1 } })];

    const missing = await handleCollectorRequest(
      { bodyText: batchBody(event()), contentLength: null, clientIp: '203.0.113.7' },
      deps,
    );
    expect(missing.status).toBe(403);
    expect(missing.body['error']).toBe('consent_required');

    const forged = await handleCollectorRequest(
      { bodyText: batchBody(event(), { consentToken: 'forged.token.value' }), contentLength: null, clientIp: '203.0.113.7' },
      deps,
    );
    expect(forged.status).toBe(403);
    expect(forged.body['error']).toBe('consent_rejected');

    const nowSec = Math.floor(Date.now() / 1000);
    const expired = await issueConsentToken(
      { v: 1, sub: null, analytics: true, ads: false, iat: nowSec - 600, exp: nowSec - 10 },
      COLLECTOR_SECRET,
    );
    const expiredResponse = await handleCollectorRequest(
      { bodyText: batchBody(event(), { consentToken: expired }), contentLength: null, clientIp: '203.0.113.7' },
      deps,
    );
    expect(expiredResponse.status).toBe(403);

    const denied = await handleCollectorRequest(
      { bodyText: batchBody(event(), { consentToken: await consentToken(COLLECTOR_SECRET, { analytics: false }) }), contentLength: null, clientIp: '203.0.113.7' },
      deps,
    );
    expect(denied.status).toBe(403);
    expect(denied.body['error']).toBe('consent_denied');

    expect(forwards).toHaveLength(0); // never forwarded in any rejection path
  });

  it('data minimization: forbidden properties are stripped BEFORE the forward', async () => {
    const { deps, forwards } = collectorDeps();
    const response = await handleCollectorRequest(
      {
        bodyText: batchBody(
          [clientEnvelope({ params: { page_type: 'checkout', email: 'leak@example.com', USER_ID: 'u-9' } })],
          { consentToken: await consentToken() },
        ),
        contentLength: null,
        clientIp: '203.0.113.8',
      },
      deps,
    );

    expect(response.status).toBe(200);
    expect(response.body['stripped']).toBe(2);
    const forwardedParams = JSON.parse(forwards[0]!.body).events[0].params as Record<string, unknown>;
    expect(forwardedParams).toEqual({ page_type: 'checkout' });
  });

  it('rate limits per session and per IP → 429 with Retry-After', async () => {
    const { FixedWindowRateLimiter } = await import('@navdhya/analytics/src/collector/rate-limit');
    const { deps, forwards } = collectorDeps({
      sessionLimiter: new FixedWindowRateLimiter({ limit: 1, windowMs: 60_000 }),
      ipLimiter: new FixedWindowRateLimiter({ limit: 1, windowMs: 60_000 }),
    });
    const token = await consentToken();
    const request = (sessionId: string, ip: string) => ({
      bodyText: batchBody([clientEnvelope({ session: { id: sessionId, seq: 1 } })], { consentToken: token }),
      contentLength: null,
      clientIp: ip,
    });

    expect((await handleCollectorRequest(request('sess_a', '203.0.113.9'), deps)).status).toBe(200);
    const sessionDenied = await handleCollectorRequest(request('sess_a', '203.0.113.9'), deps);
    expect(sessionDenied.status).toBe(429);
    expect(sessionDenied.headers?.['Retry-After']).toBeDefined();

    // Different session, same IP → the IP bucket still allows once…
    expect((await handleCollectorRequest(request('sess_b', '203.0.113.10'), deps)).status).toBe(200);
    // …then denies.
    expect((await handleCollectorRequest(request('sess_c', '203.0.113.10'), deps)).status).toBe(429);
    expect(forwards.filter((f) => f !== undefined)).toHaveLength(2);
  });

  it('no durable ack → 503 (network error, upstream 5xx, upstream 401); never an unbacked 200', async () => {
    const token = await consentToken();
    const request = (ip: string) => ({
      bodyText: batchBody([clientEnvelope({ session: { id: `sess_${ip}`, seq: 1 } })], { consentToken: token }),
      contentLength: null,
      clientIp: ip,
    });

    const down = collectorDeps();
    down.controls.throwNext = true;
    const networkError = await handleCollectorRequest(request('203.0.113.11'), down.deps);
    expect(networkError.status).toBe(503);
    expect(networkError.body['error']).toBe('upstream_unavailable');

    const boom = collectorDeps();
    boom.controls.failNextWith(500, 'upstream exploded');
    expect((await handleCollectorRequest(request('203.0.113.12'), boom.deps)).status).toBe(503);

    const authMismatch = collectorDeps();
    authMismatch.controls.failNextWith(401, JSON.stringify({ ok: false, error: 'bad_signature' }));
    const unauthorized = await handleCollectorRequest(request('203.0.113.13'), authMismatch.deps);
    expect(unauthorized.status).toBe(503);
    expect(unauthorized.body['error']).toBe('upstream_auth_failed');
  });

  it('schema rejection at ingestion passes through as 400 with the offender', async () => {
    const failing = collectorDeps();
    failing.controls.failNextWith(
      400,
      JSON.stringify({ ok: false, error: 'invalid_events', rejected: [{ index: 0, name: 'page_view', errors: ['schema: page_type required'] }] }),
    );
    const response = await handleCollectorRequest(
      {
        bodyText: batchBody([clientEnvelope()], { consentToken: await consentToken() }),
        contentLength: null,
        clientIp: '203.0.113.14',
      },
      failing.deps,
    );
    expect(response.status).toBe(400);
    expect(JSON.stringify(response.body)).toContain('schema: page_type required');
  });

  it('invalid source claim → 400; missing secrets → 503 fail-closed', async () => {
    const { deps } = collectorDeps();
    const token = await consentToken();
    const invalid = await handleCollectorRequest(
      { bodyText: batchBody([clientEnvelope()], { consentToken: token, source: 'not_a_source' }), contentLength: null, clientIp: '203.0.113.15' },
      deps,
    );
    expect(invalid.status).toBe(400);
    expect(invalid.body['error']).toBe('invalid_source');

    for (const broken of [
      { consentSecret: null },
      { hmacSecret: null },
      { ingestUrl: null },
    ] as Array<Partial<typeof deps>>) {
      const response = await handleCollectorRequest(
        { bodyText: batchBody([clientEnvelope()], { consentToken: token }), contentLength: null, clientIp: '203.0.113.15' },
        { ...deps, ...broken },
      );
      expect(response.status).toBe(503);
      expect(response.body['error']).toBe('collector_misconfigured');
    }
  });
});

describe('collector route adapter (apps/web edge, B3a)', () => {
  const ENV_KEYS = ['SIGNALS_HMAC_SECRET', 'SIGNALS_CONSENT_TOKEN_SECRET', 'SIGNALS_INGEST_URL'] as const;
  const previous: Record<string, string | undefined> = {};

  const stubUpstreamFetch = () =>
    vi.stubGlobal(
      'fetch',
      vi.fn(async () => ({ status: 200, text: async () => '{"ok":true,"persisted":1}' })),
    );

  it.beforeAll(() => {
    for (const key of ENV_KEYS) previous[key] = process.env[key];
    process.env.SIGNALS_HMAC_SECRET = COLLECTOR_SECRET;
    process.env.SIGNALS_CONSENT_TOKEN_SECRET = COLLECTOR_SECRET;
    process.env.SIGNALS_INGEST_URL = 'https://ingestion.test/ingest';
  });

  it.afterAll(() => {
    for (const key of ENV_KEYS) {
      if (previous[key] === undefined) delete process.env[key];
      else process.env[key] = previous[key];
    }
    vi.unstubAllGlobals();
  });

  function routeRequest(body: string, ip: string, headers: Record<string, string> = {}): Request {
    return new Request('http://localhost/api/ae/v1/batch', {
      method: 'POST',
      body,
      headers: { 'content-type': 'application/json', 'x-forwarded-for': ip, ...headers },
    });
  }

  it('wires env, IP extraction and the pipeline into a Response (200 on durable ack)', async () => {
    stubUpstreamFetch();
    const response = await batchRoute(
      routeRequest(
        batchBody([clientEnvelope({ session: { id: 'sess_route_ok', seq: 1 } })], { consentToken: await consentToken() }),
        '198.51.100.1',
      ),
    );
    expect(response.status).toBe(200);
    const body = (await response.json()) as Record<string, unknown>;
    expect(body).toMatchObject({ ok: true, source: 'shadow' });
  });

  it('fails closed with 503 when server secrets are not configured', async () => {
    stubUpstreamFetch();
    const saved = process.env.SIGNALS_HMAC_SECRET;
    delete process.env.SIGNALS_HMAC_SECRET;
    try {
      const response = await batchRoute(
        routeRequest(batchBody([clientEnvelope({ session: { id: 'sess_route_503', seq: 1 } })]), '198.51.100.2'),
      );
      expect(response.status).toBe(503);
    } finally {
      process.env.SIGNALS_HMAC_SECRET = saved;
    }
  });

  it('refuses an oversize declared body with 413 and an oversize actual body too', async () => {
    stubUpstreamFetch();
    const declared = await batchRoute(
      routeRequest('{}', '198.51.100.3', { 'content-length': String(70 * 1024) }),
    );
    expect(declared.status).toBe(413);

    const actual = await batchRoute(
      routeRequest('x'.repeat(70 * 1024), '198.51.100.4'),
    );
    expect(actual.status).toBe(413);
  });
});
