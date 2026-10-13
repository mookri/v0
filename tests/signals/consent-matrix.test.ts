// B4 acceptance — consent matrix:
//   analytics=denied → no GA4 tags (Consent Mode denied), collector 403s,
//   zero signals_raw rows from the browser, MP refuses the batch;
//   analytics=granted → all three paths deliver as configured.
// Deterministic on both branches — no timing, no network.

import { describe, expect, it } from 'vitest';
import { buildMpBatch } from '@navdhya/analytics/src/adapters/ga4-measurement-protocol';
import {
  CONSENT_MODE_DEFAULTS,
  consentModeUpdate,
  pushGtmEvent,
  type DataLayerPush,
} from '@navdhya/analytics/src/adapters/ga4-data-layer';
import { createAnalyticsClient } from '@navdhya/analytics/src/client/analytics-client';
import { MemoryOutbox } from '@navdhya/analytics/src/client/outbox';
import { handleCollectorRequest } from '@navdhya/analytics/src/collector/pipeline';
import { newEventId, type SignalEnvelope } from '@navdhya/analytics/src/contracts/envelope';
import { handleIngestRequest } from '@services/functions/ingestion/src/main';
import { MemoryRawSink } from '@services/functions/ingestion/src/raw-sink';
import { MemoryNonceStore } from '@services/functions/ingestion/src/worker-auth';
import {
  COLLECTOR_SECRET,
  batchBody,
  collectorDeps,
  consentToken,
} from './helpers/collector-fixtures';

function clientEnvelope(): SignalEnvelope {
  return {
    id: newEventId(),
    name: 'page_view',
    version: 1,
    occurredAt: new Date().toISOString(),
    surface: 'web',
    appVersion: '1.0.0',
    session: { id: 'sess_consent_matrix', seq: 1 },
    actor: { uidHash: null, anonId: null },
    consent: { analytics: true, ads: false },
    context: { routeTemplate: '/' },
    params: { page_type: 'checkout' },
  };
}

function serverEnvelopeWithConsent(analytics: boolean): SignalEnvelope {
  return {
    ...clientEnvelope(),
    name: 'payment_succeeded',
    appVersion: 'server',
    session: { id: 'srv_payment_succeeded_cm', seq: 1 },
    consent: { analytics, ads: false },
    params: { value: 999, currency: 'INR', transaction_ref: 'TX_CM' },
  };
}

/** Collector deps that persist through the REAL ingestion handler. */
function ingestionBackedDeps(sink: MemoryRawSink, forwards: { count: number }) {
  return collectorDeps({
    fetchImpl: async (_url, init) => {
      forwards.count += 1;
      const result = await handleIngestRequest(
        init.body,
        init.headers['x-signals-auth'],
        { sink, source: 'shadow', nowMs: Date.now, workerSecret: COLLECTOR_SECRET, nonceStore: new MemoryNonceStore() },
        { sourceHeader: init.headers['x-signals-source'] },
      );
      return { status: result.status, text: async () => JSON.stringify(result.body) };
    },
  });
}

describe('consent matrix (B4)', () => {
  it('DENIED: SDK drops at track; no delivery attempt is ever made', async () => {
    let deliveryAttempts = 0;
    const denied = createAnalyticsClient({
      enabled: true,
      surface: 'web',
      appVersion: '1.0.0',
      consent: () => ({ analytics: 'DENIED', ads: 'DENIED' }),
      outbox: new MemoryOutbox(),
      send: async () => {
        deliveryAttempts += 1;
      },
      beacon: () => {
        deliveryAttempts += 1;
        return true;
      },
      autoBeacon: false,
      onWarn: () => {},
    });

    expect(denied.track('page_view', { page_type: 'home' })).toBe(false);
    expect(await denied.flush()).toEqual({ sent: 0, failed: 0 });
    expect(await denied.flushBeacon()).toBe(0);
    expect(denied.metrics().eligibleAttempted).toBe(0);
    expect(deliveryAttempts).toBe(0);
  });

  it('DENIED: collector 403s (no/denied consent evidence) → nothing forwarded, zero rows', async () => {
    const sink = new MemoryRawSink();
    const forwards = { count: 0 };
    const { deps } = ingestionBackedDeps(sink, forwards);

    const noToken = await handleCollectorRequest(
      { bodyText: batchBody([clientEnvelope()]), contentLength: null, clientIp: '203.0.113.61' },
      deps,
    );
    expect(noToken.status).toBe(403);

    const deniedToken = await handleCollectorRequest(
      {
        bodyText: batchBody([clientEnvelope()], {
          consentToken: await consentToken(COLLECTOR_SECRET, { analytics: false }),
        }),
        contentLength: null,
        clientIp: '203.0.113.61',
      },
      deps,
    );
    expect(deniedToken.status).toBe(403);
    expect(forwards.count).toBe(0);
    expect(sink.size()).toBe(0);
  });

  it('DENIED: Consent Mode stays denied; MP refuses consent-less server events', () => {
    expect(CONSENT_MODE_DEFAULTS.analytics_storage).toBe('denied');
    expect(consentModeUpdate({ analytics: 'DENIED', ads: 'GRANTED' })['analytics_storage']).toBe('denied');
    expect(consentModeUpdate({ analytics: 'UNKNOWN', ads: 'UNKNOWN' })['analytics_storage']).toBe('denied');

    expect(() =>
      buildMpBatch([serverEnvelopeWithConsent(false)], { resolveClientId: () => 'hmac_uid_cm' }),
    ).toThrow(/lacks analytics consent/);
  });

  it('GRANTED: all three paths deliver — GTM entry, collector row, MP purchase', async () => {
    // GTM: consent update flips to granted; the event pushes.
    expect(consentModeUpdate({ analytics: 'GRANTED', ads: 'DENIED' })).toEqual({
      event: 'consent_update',
      analytics_storage: 'granted',
      ad_storage: 'denied',
    });
    const dataLayer: DataLayerPush[] = [];
    expect(pushGtmEvent(dataLayer, 'page_view', { page_type: 'home' }).pushed).toBe(true);
    expect(dataLayer).toEqual([{ event: 'page_view', page_type: 'home' }]);

    // Collector: granted consent token → forwarded → persisted (REAL ingestion).
    const sink = new MemoryRawSink();
    const forwards = { count: 0 };
    const { deps } = ingestionBackedDeps(sink, forwards);
    const accepted = await handleCollectorRequest(
      {
        bodyText: batchBody([clientEnvelope()], { consentToken: await consentToken() }),
        contentLength: null,
        clientIp: '203.0.113.62',
      },
      deps,
    );
    expect(accepted.status).toBe(200);
    expect(forwards.count).toBe(1);
    expect(sink.size()).toBe(1);
    expect(String(sink.all()[0]!.json['name'])).toBe('page_view');

    // MP: consented server event builds as GA4 `purchase`.
    const batch = buildMpBatch([serverEnvelopeWithConsent(true)], { resolveClientId: () => 'hmac_uid_cm' });
    expect(batch.events[0]!.name).toBe('purchase');
  });
});
