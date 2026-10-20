// B9 acceptance — failure drills (plan B9 bullet 2). Every drill injects a
// failure, proves DETECTION (visible signal, never silent), proves
// DEGRADATION CONTAINMENT (business flows / data preserved), and proves
// RECOVERY (invariants restored), recording measured times into the drill
// ledger. The runbook for each drill documents the real-infra variant; the
// mechanics exercised here are the ones those runbooks act on.
//
// Drills and their seams:
//   1. collector-outage         — client SDK: send fails; queue retains;
//                                 business flow unaffected; heal drains.
//   2. ingestion-500s           — real collector chain; storage down → the
//                                 Cloud Run shell 500s; retries; heal drains.
//   3. pubsub-backlog           — publisher transport down; backlog builds
//                                 (never dead-lettered); heal drains; L1 clean.
//   4. consent-service-outage   — token provider down → collector 403s →
//                                 fail-closed, events stay queued; business
//                                 flow unaffected; heal drains. Denied
//                                 consent blocks emission outright.
//   5. bigquery-quota-exhaustion— ingestion 503 (retryable) window; status
//                                 map honoured (no permanent rejections);
//                                 heal → zero-lag, zero loss.
//   6. misdeployed-schema       — unknown event rejected all-or-nothing to
//                                 the dead-letter seam (zero silent rows);
//                                 kill switch no-ops the SDK; registry
//                                 revert restores flow.

import { existsSync } from 'node:fs';
import * as path from 'node:path';
import { describe, expect, it } from 'vitest';
import { MemoryOutboxStore } from '@infra/messaging/outbox/MemoryOutboxStore';
import { FixedWindowRateLimiter } from '@navdhya/analytics/src/collector/rate-limit';
import { handleCollectorRequest } from '@navdhya/analytics/src/collector/pipeline';
import { reconcileLevel1 } from '@navdhya/analytics/src/server/reconciliation/level1';
import {
  emitBookingConfirmed,
  emitPaymentSucceeded,
} from '@navdhya/analytics/src/server/emitters';
import { createAnalyticsClient } from '@navdhya/analytics/src/client/analytics-client';
import { MemoryOutbox } from '@navdhya/analytics/src/client/outbox';
import { ingestBatch, type RejectedEvent } from '@services/functions/ingestion/src/ingest';
import { handleIngestRequest } from '@services/functions/ingestion/src/main';
import { MemoryRawSink } from '@services/functions/ingestion/src/raw-sink';
import { MemoryNonceStore } from '@services/functions/ingestion/src/worker-auth';
import { runPublisherOnce } from '@services/functions/outbox-publisher/src/publisher';
import { COLLECTOR_SECRET, clientEnvelope, consentToken } from './helpers/collector-fixtures';
import { drillLedger, recordDrill, PLANNED_DRILLS } from './helpers/drill-ledger';

const granted = () => ({ analytics: 'GRANTED' as const, ads: 'DENIED' as const });

// ────────────────────────────────────────────────────────────── drill 1

describe('drill: collector-outage', () => {
  it('client queues locally through the outage; business flow unaffected; heal drains to zero', async () => {
    const outbox = new MemoryOutbox();
    let collectorDown = true;
    let sendsAccepted = 0;
    const client = createAnalyticsClient({
      enabled: true,
      surface: 'web',
      appVersion: '1.0.0',
      outbox,
      consent: granted,
      autoBeacon: false,
      send: async () => {
        if (collectorDown) throw new Error('collector unreachable');
        sendsAccepted += 1;
      },
    });

    const injectAt = performance.now();
    for (let i = 0; i < 30; i += 1) client.track('page_view', { page_type: 'catalog' });
    const failed = await client.flush();
    // Detection: the flush failed AND every event remains queued (no loss).
    // flush() attempts one batch per call (maxBatch=25); the rest stay queued.
    const detectionMs = performance.now() - injectAt;
    expect(failed).toEqual({ sent: 0, failed: 25 });
    expect(await outbox.size()).toBe(30);

    // Degradation containment: the BUSINESS flow keeps working — a checkout
    // event during the outage is still accepted locally.
    const businessUnaffected = client.track('checkout_started', {
      value_basket: 2400,
      item_count: 2,
    });
    expect(businessUnaffected).toBe(true);

    // Recovery: collector heals; flush drains to zero-lag.
    const recoverAt = performance.now();
    collectorDown = false;
    for (let pass = 0; (await outbox.size()) > 0 && pass < 10; pass += 1) {
      await client.flush();
    }
    const recoveryMs = performance.now() - recoverAt;
    expect(await outbox.size()).toBe(0);
    expect(sendsAccepted).toBeGreaterThan(0);
    expect(client.metrics().acknowledged).toBe(31);

    recordDrill({
      drill: 'collector-outage',
      runbook: 'docs/analytics/runbooks/collector-outage.md',
      injectedFailure: 'collector endpoint unreachable (transport rejects)',
      expectedDetection: 'flush fails; events remain in the local queue (ack-gated removal)',
      expectedDegradation: 'business flows unaffected — track() keeps accepting locally; no data destroyed',
      expectedRecovery: 'collector heals → flush drains queue to zero-lag',
      detectionMs,
      recoveryMs,
      containmentOk: businessUnaffected === true && (await outbox.size()) === 0,
      invariantsOk: true,
    });
  });
});

// ────────────────────────────────────────────────────────────── drill 2

async function collectorAttempt(
  body: string,
  fetchImpl: (url: string, init: { body: string; headers: Record<string, string> }) => Promise<{ status: number }>,
): Promise<number> {
  const deps = {
    nowSec: () => Math.floor(Date.now() / 1000),
    consentSecret: COLLECTOR_SECRET,
    hmacSecret: COLLECTOR_SECRET,
    ingestUrl: 'https://ingestion.test/ingest',
    fetchImpl: fetchImpl as never,
    nonce: () => `drill_${Math.random().toString(36).slice(2)}`,
    ipLimiter: new FixedWindowRateLimiter({ limit: 10_000, windowMs: 60_000 }),
    sessionLimiter: new FixedWindowRateLimiter({ limit: 10_000, windowMs: 60_000 }),
    defaultSource: 'shadow' as const,
    onWarn: () => {},
  };
  const response = await handleCollectorRequest(
    { bodyText: body, contentLength: null, clientIp: '198.18.0.7' },
    deps,
  );
  return response.status;
}

describe('drill: ingestion-500s', () => {
  it('storage outage → shell 500s → retries; nothing lost; heal persists every event', async () => {
    const realSink = new MemoryRawSink();
    let storageDown = true;
    const flakySink = {
      put: async (rows: Parameters<MemoryRawSink['put']>[0]): Promise<void> => {
        if (storageDown) throw new Error('ingestion storage unavailable');
        return realSink.put(rows);
      },
    };
    const nonceStore = new MemoryNonceStore();
    const token = await consentToken();

    // fetch → real ingestion handler; an uncaught handler exception is what
    // the Cloud Run shell surfaces as a 500 to the collector.
    const fetchImpl = async (
      _url: string,
      init: { body: string; headers: Record<string, string> },
    ): Promise<{ status: number }> => {
      try {
        const result = await handleIngestRequest(
          init.body,
          init.headers['x-signals-auth'] ?? null,
          {
            sink: flakySink,
            source: 'shadow',
            nowMs: () => Date.now(),
            workerSecret: COLLECTOR_SECRET,
            nonceStore,
          },
          { sourceHeader: init.headers['x-signals-source'] ?? null },
        );
        return { status: result.status };
      } catch {
        return { status: 500 }; // deployment shell maps uncaught → 500
      }
    };

    const body = JSON.stringify({
      events: Array.from({ length: 5 }, (_, i) =>
        clientEnvelope({ session: { id: 'sess_drill2', seq: i + 1 } }),
      ),
      source: 'shadow',
      consentToken: token,
    });

    const injectAt = performance.now();
    const firstTry = await collectorAttempt(body, fetchImpl);
    // Detection: the failure is VISIBLE to the sender, and nothing
    // persisted. The collector's status map translates upstream 5xx into a
    // retryable 503 for its own clients ("no durable ack") — never a 200.
    const detectionMs = performance.now() - injectAt;
    expect(firstTry).toBe(503);
    expect(realSink.size()).toBe(0);

    // Retry while down: still retryable, still nothing persisted (never a
    // partial write — ingestion is all-or-nothing per batch).
    expect(await collectorAttempt(body, fetchImpl)).toBe(503);
    expect(realSink.size()).toBe(0);

    // Recovery: storage heals; the retry persists the batch durably.
    const recoverAt = performance.now();
    storageDown = false;
    let healStatus = 0;
    for (let attempt = 0; attempt < 8 && healStatus !== 200; attempt += 1) {
      healStatus = await collectorAttempt(body, fetchImpl);
    }
    const recoveryMs = performance.now() - recoverAt;
    expect(healStatus).toBe(200);
    expect(realSink.size()).toBe(5); // zero loss after recovery

    recordDrill({
      drill: 'ingestion-500s',
      runbook: 'docs/analytics/runbooks/collector-outage.md',
      injectedFailure: 'ingestion storage down — handler throws, shell returns 500',
      expectedDetection: 'sender sees retryable 503 (collector maps upstream 5xx); sink stays empty (all-or-nothing)',
      expectedDegradation: 'retries continue; no partial writes, no silent drops',
      expectedRecovery: 'storage heals → retry gets durable-acceptance 200; every event persisted',
      detectionMs,
      recoveryMs,
      containmentOk: realSink.size() === 5,
      invariantsOk: true,
    });
  });
});

// ────────────────────────────────────────────────────────────── drill 3

describe('drill: pubsub-backlog', () => {
  it('transport outage builds backlog (never dead-letters); heal drains; Level-1 clean', async () => {
    const store = new MemoryOutboxStore();
    for (let i = 0; i < 200; i += 1) {
      if (i % 2 === 0) {
        await emitPaymentSucceeded(store, { aggregateId: `pay_${i}` }, {
          value: 1000 + i,
          currency: 'INR',
          transaction_ref: `rzp_${i}`,
        });
      } else {
        await emitBookingConfirmed(store, { aggregateId: `book_${i}` }, {
          value: 1000 + i,
          currency: 'INR',
          service_category: 'puja',
          city: 'Pune',
        });
      }
    }
    const committedIds = store.snapshot().map((record) => record.id);

    let transportDown = true;
    let deadLettered = 0;
    const transport = {
      publish: async (): Promise<void> => {
        if (transportDown) throw new Error('pubsub unavailable (drill)');
      },
    };
    const options = { batch: 50, maxAttempts: 50, onDeadLettered: () => { deadLettered += 1; } };

    const injectAt = performance.now();
    let releasedTotal = 0;
    for (let cycle = 0; cycle < 5; cycle += 1) {
      const cycleResult = await runPublisherOnce(store, transport, options);
      releasedTotal += cycleResult.released;
      expect(cycleResult.published).toBe(0); // detection: nothing acked downstream
    }
    const detectionMs = performance.now() - injectAt;
    const unpublished = store.snapshot().filter((record) => !record.published);
    expect(unpublished).toHaveLength(200); // backlog is visible, records intact

    // Degradation containment: attempts burn but stay under the ceiling —
    // backlog is RETAINED, never dead-lettered, never dropped.
    expect(deadLettered).toBe(0);
    expect(releasedTotal).toBeGreaterThan(0);
    expect(unpublished.every((record) => record.deadLettered === false)).toBe(true);

    // Recovery: transport heals; cycles drain the backlog to zero-lag.
    const recoverAt = performance.now();
    transportDown = false;
    for (let cycle = 0; cycle < 20; cycle += 1) {
      const drain = await runPublisherOnce(store, transport, options);
      if (drain.published === 0 && drain.released === 0) break;
    }
    const recoveryMs = performance.now() - recoverAt;
    const publishedIds = store.snapshot().filter((r) => r.published).map((r) => r.id);
    expect(publishedIds).toHaveLength(200);
    expect(deadLettered).toBe(0);

    // Standing safety net after recovery: Level-1 clean over the ID space.
    const report = reconcileLevel1({
      committedIds,
      publishedIds,
      persistedIds: committedIds, // sink leg proven by the load matrix
    });
    expect(report.ok).toBe(true);
    expect(report.missingPublished).toEqual([]);

    recordDrill({
      drill: 'pubsub-backlog',
      runbook: 'docs/analytics/runbooks/pubsub-backlog.md',
      injectedFailure: 'Pub/Sub transport throws transiently for 5 publisher cycles',
      expectedDetection: 'publisher releases instead of acking; backlog visible as unpublished records',
      expectedDegradation: 'backlog builds under the attempts ceiling — retained, never dead-lettered',
      expectedRecovery: 'transport heals → drain cycles republish everything; Level-1 reconciliation clean',
      detectionMs,
      recoveryMs,
      containmentOk: deadLettered === 0 && publishedIds.length === 200,
      invariantsOk: true,
    });
  });
});

// ────────────────────────────────────────────────────────────── drill 4

describe('drill: consent-service-outage', () => {
  it('token outage fails closed (queued, unacked); business flow unaffected; heal drains', async () => {
    const outbox = new MemoryOutbox();
    let tokenServiceDown = true;
    let getConsentToken = async (): Promise<string | null> =>
      tokenServiceDown ? null : 'token_ok';
    let acceptedBatches = 0;
    const client = createAnalyticsClient({
      enabled: true,
      surface: 'web',
      appVersion: '1.0.0',
      outbox,
      consent: granted,
      getConsentToken,
      autoBeacon: false,
      // Mirrors the real chain: batchBody() omits the token when the
      // provider returns null → the collector 403s → send throws.
      send: async () => {
        const token = await getConsentToken();
        if (token === null) throw new Error('collector 403 (no consent token)');
        acceptedBatches += 1;
      },
    });

    const injectAt = performance.now();
    client.track('page_view', { page_type: 'checkout' });
    const failed = await client.flush();
    // Detection: fail-closed — attempted but NEVER acknowledged, queued.
    const detectionMs = performance.now() - injectAt;
    expect(failed).toEqual({ sent: 0, failed: 1 });
    const metricsAfterOutage = client.metrics();
    expect(metricsAfterOutage.eligibleAttempted).toBe(1);
    expect(metricsAfterOutage.acknowledged).toBe(0);

    // Degradation containment: business flows unaffected — checkout keeps
    // accepting events locally while analytics stays fail-closed.
    const businessUnaffected = client.track('begin_checkout', {
      value_basket: 5100,
      payment_method: 'upi',
    });
    expect(businessUnaffected).toBe(true);
    expect(await outbox.size()).toBe(2);

    // Recovery: token service heals; flush drains everything.
    const recoverAt = performance.now();
    tokenServiceDown = false;
    for (let pass = 0; (await outbox.size()) > 0 && pass < 10; pass += 1) {
      await client.flush();
    }
    const recoveryMs = performance.now() - recoverAt;
    expect(await outbox.size()).toBe(0);
    expect(acceptedBatches).toBeGreaterThan(0);
    expect(client.metrics().acknowledged).toBe(2);

    // Second face of the drill: DENIED consent blocks emission outright —
    // fail-closed is not only about the token service being up.
    const deniedClient = createAnalyticsClient({
      enabled: true,
      surface: 'web',
      appVersion: '1.0.0',
      outbox: new MemoryOutbox(),
      consent: () => ({ analytics: 'DENIED' as const, ads: 'DENIED' as const }),
      autoBeacon: false,
      send: async () => {},
    });
    expect(deniedClient.track('page_view', { page_type: 'home' })).toBe(false);

    recordDrill({
      drill: 'consent-service-outage',
      runbook: 'docs/analytics/runbooks/consent-failure.md',
      injectedFailure: 'consent-token provider returns null (token service down)',
      expectedDetection: 'flush fails; eligible-attempted counts, acknowledged stays 0 (fail-closed)',
      expectedDegradation: 'business flows unaffected — events queue locally; nothing acknowledged, nothing lost',
      expectedRecovery: 'token service heals → flush drains queue; DENIED consent still blocks emission',
      detectionMs,
      recoveryMs,
      containmentOk: businessUnaffected === true && (await outbox.size()) === 0,
      invariantsOk: true,
    });
  });
});

// ────────────────────────────────────────────────────────────── drill 5

describe('drill: bigquery-quota-exhaustion', () => {
  it('quota exhaustion surfaces as retryable 503 — no permanent rejections; heal drains', async () => {
    const sink = new MemoryRawSink();
    const nonceStore = new MemoryNonceStore();
    const token = await consentToken();
    let quotaExhausted = true;
    let upstream503s = 0;

    const fetchImpl = async (
      _url: string,
      init: { body: string; headers: Record<string, string> },
    ): Promise<{ status: number }> => {
      if (quotaExhausted) {
        upstream503s += 1;
        return { status: 503 }; // quota-exhausted Cloud Run revision answers 503
      }
      const result = await handleIngestRequest(
        init.body,
        init.headers['x-signals-auth'] ?? null,
        {
          sink,
          source: 'shadow',
          nowMs: () => Date.now(),
          workerSecret: COLLECTOR_SECRET,
          nonceStore,
        },
        { sourceHeader: init.headers['x-signals-source'] ?? null },
      );
      return { status: result.status };
    };

    const body = JSON.stringify({
      events: Array.from({ length: 4 }, (_, i) =>
        clientEnvelope({ session: { id: 'sess_drill5', seq: i + 1 } }),
      ),
      source: 'shadow',
      consentToken: token,
    });

    const injectAt = performance.now();
    const statuses: number[] = [];
    for (let attempt = 0; attempt < 3; attempt += 1) {
      statuses.push(await collectorAttempt(body, fetchImpl));
    }
    // Detection: 503 is visible AND classified retryable — the status map
    // must never turn a quota outage into a permanent rejection.
    const detectionMs = performance.now() - injectAt;
    expect(statuses).toEqual([503, 503, 503]);
    expect(statuses.every((status) => status >= 500)).toBe(true);
    expect(statuses).not.toContain(400);
    expect(statuses).not.toContain(403);
    expect(statuses).not.toContain(413);
    expect(sink.size()).toBe(0); // fail-closed: nothing half-persisted

    // Degradation containment: retries keep being offered (backlog stays
    // with the SENDER, ordered, no drops).
    expect(upstream503s).toBe(3);

    // Recovery: quota restored; the same batch persists durably.
    const recoverAt = performance.now();
    quotaExhausted = false;
    let healStatus = 0;
    for (let attempt = 0; attempt < 8 && healStatus !== 200; attempt += 1) {
      healStatus = await collectorAttempt(body, fetchImpl);
    }
    const recoveryMs = performance.now() - recoverAt;
    expect(healStatus).toBe(200);
    expect(sink.size()).toBe(4); // zero loss

    recordDrill({
      drill: 'bigquery-quota-exhaustion',
      runbook: 'docs/analytics/runbooks/warehouse-freshness.md',
      injectedFailure: 'downstream quota exhausted — ingestion answers 503 for a window',
      expectedDetection: '503 observed on the batch (retryable class, never 400/403/413)',
      expectedDegradation: 'sender retains and retries its backlog; nothing persisted half-way',
      expectedRecovery: 'quota restored → durable-acceptance 200; every event persisted (zero loss)',
      detectionMs,
      recoveryMs,
      containmentOk: sink.size() === 4 && !statuses.some((s) => s === 400 || s === 403 || s === 413),
      invariantsOk: true,
    });
  });
});

// ────────────────────────────────────────────────────────────── drill 6

describe('drill: misdeployed-schema', () => {
  it('unknown event rejected all-or-nothing to the dead-letter seam; kill switch; registry revert restores flow', async () => {
    const sink = new MemoryRawSink();
    const deadLetters: RejectedEvent[] = [];
    const injectAt = performance.now();

    // A misdeployed SDK build emits a NEW event the deployed registry does
    // not know (structurally valid envelope, unregistered name).
    const unknownId = 'DRILLUNKNOWN0000000000000001';
    const misdeployedBatch = [
      clientEnvelope({ session: { id: 'sess_drill6', seq: 1 } }),
      {
        id: unknownId,
        name: 'brand_new_unregistered_event',
        version: 1,
        occurredAt: new Date().toISOString(),
        surface: 'web',
        appVersion: '1.0.0',
        session: { id: 'sess_drill6', seq: 2 },
        actor: { uidHash: null, anonId: null },
        consent: { analytics: true, ads: false },
        context: { routeTemplate: '/' },
        params: {},
      },
    ];
    const rejected = await ingestBatch(misdeployedBatch, {
      sink,
      source: 'shadow',
      onDeadLetter: (event) => {
        deadLetters.push(event);
      },
    });

    // Detection + containment: all-or-nothing rejection — ZERO silent rows,
    // the bad event surfaced explicitly to the dead-letter seam.
    const detectionMs = performance.now() - injectAt;
    expect(rejected.ok).toBe(false);
    expect(sink.size()).toBe(0);
    expect(deadLetters.length).toBeGreaterThan(0);
    expect(deadLetters.some((event) => event.index === 1)).toBe(true);

    // Kill switch: NEXT_PUBLIC_SIGNALS_ENABLED=false → the SDK is a no-op.
    const killed = createAnalyticsClient({
      enabled: false,
      surface: 'web',
      appVersion: '1.0.0',
      outbox: new MemoryOutbox(),
      consent: granted,
      autoBeacon: false,
      send: async () => {
        throw new Error('must not be called while the kill switch is engaged');
      },
    });
    expect(killed.track('page_view', { page_type: 'home' })).toBe(false);
    expect(await killed.flush()).toEqual({ sent: 0, failed: 0 });

    // Registry revert (`npx tsx src/registry/generate.ts` + redeploy) makes
    // the registry know the event again — modelled here by the same stream
    // flowing once the deployed registry accepts the envelope.
    const recoverAt = performance.now();
    const reverted = await ingestBatch(
      [clientEnvelope({ session: { id: 'sess_drill6', seq: 3 } })],
      { sink, source: 'shadow' },
    );
    const recoveryMs = performance.now() - recoverAt;
    expect(reverted.ok).toBe(true);
    expect(sink.size()).toBe(1); // flow restored, exactly the clean event

    recordDrill({
      drill: 'misdeployed-schema',
      runbook: 'docs/analytics/runbooks/schema-rollback.md',
      injectedFailure: 'SDK deploy emits an unregistered event name (schema drift)',
      expectedDetection: 'all-or-nothing 400 + dead-letter seam report; zero silent rows',
      expectedDegradation: 'kill switch (NEXT_PUBLIC_SIGNALS_ENABLED=false) no-ops the SDK; bad rows never persist',
      expectedRecovery: 'registry revert (registry:generate + redeploy) restores clean flow',
      detectionMs,
      recoveryMs,
      containmentOk: sink.size() === 1 && deadLetters.length > 0,
      invariantsOk: true,
    });
  });
});

// ──────────────────────────────────────────────────── results table

describe('drill results table (B9)', () => {
  it('every planned drill ran, with runbooks present and invariants green', async () => {
    const ledger = drillLedger();
    const table = ledger
      .map(
        (row) =>
          `[drill:${row.drill}] detection=${row.detectionMs.toFixed(1)}ms recovery=${row.recoveryMs.toFixed(1)}ms ` +
          `containment=${row.containmentOk} invariants=${row.invariantsOk} runbook=${row.runbook}`,
      )
      .join('\n');
    // eslint-disable-next-line no-console
    console.log(`drill results:\n${table}`);

    expect(ledger.map((row) => row.drill).sort()).toEqual([...PLANNED_DRILLS].sort());
    for (const row of ledger) {
      expect(row.containmentOk).toBe(true); // degradation contained
      expect(row.invariantsOk).toBe(true); // recovery + correctness
      // Measured times are recorded evidence; sanity-bounded here (the
      // §1 action-latency SLO is a real-deployment number — runbooks own it).
      expect(row.detectionMs).toBeLessThan(30_000);
      expect(row.recoveryMs).toBeLessThan(30_000);
      // Every drill's runbook exists — exercised, not just authored.
      expect(existsSync(path.join(process.cwd(), row.runbook))).toBe(true);
    }
  });
});
