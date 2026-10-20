// B4 acceptance — key-event parity for a scripted booking journey:
//   GA4 shows exactly one `purchase` (server → MP), one `purchase_viewed`
//   (browser → collector raw + GTM dataLayer), one `booking_confirmed`
//   (server → MP) — no duplicates, no gaps, and no destination receiving
//   an event by two paths (plan §0.3 rule 2).
//
// Publisher mechanics (claim/publish/ack) are covered by the B2 truth-path
// suite; here the emitter → ingestion leg is exercised directly and the
// three REPORTING paths are counted per destination.

import { describe, expect, it } from 'vitest';
import { MemoryOutboxStore } from '@infra/messaging/outbox/MemoryOutboxStore';
import {
  buildMpBatch,
  sendMpBatch,
} from '@navdhya/analytics/src/adapters/ga4-measurement-protocol';
import { pushGtmEvent, type DataLayerPush } from '@navdhya/analytics/src/adapters/ga4-data-layer';
import { createAnalyticsClient } from '@navdhya/analytics/src/client/analytics-client';
import { MemoryOutbox } from '@navdhya/analytics/src/client/outbox';
import { handleCollectorRequest } from '@navdhya/analytics/src/collector/pipeline';
import { emitBookingConfirmed, emitPaymentSucceeded } from '@navdhya/analytics/src/server/emitters';
import type { SignalEnvelope } from '@navdhya/analytics/src/contracts/envelope';
import { handleIngestRequest } from '@services/functions/ingestion/src/main';
import { MemoryRawSink } from '@services/functions/ingestion/src/raw-sink';
import { MemoryNonceStore, signWorkerRequest } from '@services/functions/ingestion/src/worker-auth';
import {
  COLLECTOR_SECRET,
  batchBody,
  collectorDeps,
  consentToken,
} from './helpers/collector-fixtures';

const CLIENT_ID = 'hmac_uid_journey';

function countNames(rows: Array<{ json: Record<string, unknown> }>): Record<string, number> {
  const counts: Record<string, number> = {};
  for (const row of rows) {
    const name = String(row.json['name']);
    counts[name] = (counts[name] ?? 0) + 1;
  }
  return counts;
}

/** Server leg: emitters → (signed) ingestion with source=server. */
async function serverLeg(sink: MemoryRawSink): Promise<SignalEnvelope[]> {
  const outbox = new MemoryOutboxStore();
  const ctx = (aggregateId: string) => ({ aggregateId, consent: { analytics: true, ads: false } });
  const payment = await emitPaymentSucceeded(outbox, ctx('pay_j1'), {
    value: 1499,
    currency: 'INR',
    transaction_ref: 'TX_J1',
  });
  const booking = await emitBookingConfirmed(outbox, ctx('bk_j1'), {
    value: 1499,
    currency: 'INR',
    service_category: 'puja',
  });

  const nonceStore = new MemoryNonceStore();
  let nonce = 0;
  for (const envelope of [payment, booking]) {
    const body = JSON.stringify({ events: [envelope] });
    const auth = await signWorkerRequest(COLLECTOR_SECRET, body, {
      timestampSec: Math.floor(Date.now() / 1000),
      nonce: `parity_${(nonce += 1)}`,
    });
    const response = await handleIngestRequest(
      body,
      auth,
      { sink, source: 'server', nowMs: Date.now, workerSecret: COLLECTOR_SECRET, nonceStore },
    );
    expect(response.status).toBe(200);
  }
  return [payment, booking];
}

/** Browser leg: SDK → REAL collector pipeline → REAL ingestion → sink. */
async function browserLeg(sink: MemoryRawSink): Promise<{ tracked: boolean; flushed: number }> {
  const token = await consentToken();
  const { deps } = collectorDeps({
    fetchImpl: async (_url, init) => {
      const result = await handleIngestRequest(
        init.body,
        init.headers['x-signals-auth'],
        { sink, source: 'shadow', nowMs: Date.now, workerSecret: COLLECTOR_SECRET, nonceStore: new MemoryNonceStore() },
        { sourceHeader: init.headers['x-signals-source'] },
      );
      return { status: result.status, text: async () => JSON.stringify(result.body) };
    },
  });

  const client = createAnalyticsClient({
    enabled: true,
    surface: 'web',
    appVersion: '1.0.0',
    consent: () => ({ analytics: 'GRANTED', ads: 'DENIED' }),
    outbox: new MemoryOutbox(),
    source: 'shadow',
    getConsentToken: () => token,
    send: async (batch) => {
      const response = await handleCollectorRequest(
        { bodyText: batchBody(batch, { consentToken: token }), contentLength: null, clientIp: '203.0.113.50' },
        deps,
      );
      if (response.status !== 200) throw new Error(`collector ${response.status}`);
    },
    onWarn: () => {},
  });

  const tracked = client.track('purchase_viewed', { value: 1499, item_count: 3 });
  const result = await client.flush();
  return { tracked, flushed: result.sent };
}

describe('key-event parity (B4)', () => {
  it('scripted booking journey: one purchase, one purchase_viewed, one booking_confirmed — no duplicates, no gaps', async () => {
    const serverSink = new MemoryRawSink();
    const browserSink = new MemoryRawSink();

    // 1. Server truth (P0) → private warehouse, source=server.
    const serverEnvelopes = await serverLeg(serverSink);
    expect(countNames(serverSink.all())).toEqual({ payment_succeeded: 1, booking_confirmed: 1 });

    // 2. Server truth → GA4 via MP: payment_succeeded reports as `purchase`.
    const mpBatch = buildMpBatch(serverEnvelopes, { resolveClientId: () => CLIENT_ID });
    const mpNames = mpBatch.events.map((event) => event.name);
    expect(mpNames).toEqual(['purchase', 'booking_confirmed']);
    const mpReceipt = await sendMpBatch(mpBatch, {
      measurementId: 'G-PARITY',
      apiSecret: 'secret',
      fetchImpl: (async () => ({ status: 204 })) as unknown as typeof fetch,
    });
    expect(mpReceipt.receipt).toBe(true); // receipt only — never "processed"

    // 3. Browser confirmation view → collector raw (shadow) + GTM dataLayer.
    const browser = await browserLeg(browserSink);
    expect(browser).toEqual({ tracked: true, flushed: 1 });
    expect(countNames(browserSink.all())).toEqual({ purchase_viewed: 1 });

    const dataLayer: DataLayerPush[] = [];
    expect(pushGtmEvent(dataLayer, 'purchase_viewed', { value: 1499, item_count: 3 }).pushed).toBe(true);
    expect(pushGtmEvent(dataLayer, 'purchase', { value: 1499, currency: 'INR' }).pushed).toBe(false); // server-owned
    expect(countEntries(dataLayer)).toEqual({ purchase_viewed: 1 });

    // ── The parity table itself: per destination, exactly the expected counts.
    const ga4ViaMp = countList(mpNames);
    expect(ga4ViaMp).toEqual({ purchase: 1, booking_confirmed: 1 });
    expect(ga4ViaMp['purchase_viewed']).toBeUndefined(); // browser-owned — never via MP

    const ga4ViaGtm = countEntries(dataLayer);
    expect(ga4ViaGtm).toEqual({ purchase_viewed: 1 });
    expect(ga4ViaGtm['purchase']).toBeUndefined(); // server-owned — never via GTM

    const raw = { ...countNames(serverSink.all()), ...countNames(browserSink.all()) };
    expect(raw).toEqual({ payment_succeeded: 1, booking_confirmed: 1, purchase_viewed: 1 });
  });
});

function countEntries(dataLayer: DataLayerPush[]): Record<string, number> {
  const counts: Record<string, number> = {};
  for (const entry of dataLayer) {
    const name = String(entry['event']);
    if (name.startsWith('consent_')) continue;
    counts[name] = (counts[name] ?? 0) + 1;
  }
  return counts;
}

function countList(names: string[]): Record<string, number> {
  const counts: Record<string, number> = {};
  for (const name of names) counts[name] = (counts[name] ?? 0) + 1;
  return counts;
}
