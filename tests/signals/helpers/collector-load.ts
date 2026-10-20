// B3 load harness (plan: "Load harness (`tests/` — k6/autocannon script);
// 10k eps sustained + burst; SLOs recorded as results").
//
// In-process Node harness instead of k6/autocannon: no new dependency enters
// the lockfile (known-debt discipline), and it drives the REAL chain —
// collector pipeline → (fake transport) → ingestion HTTP handler → raw sink
// — with injected faults, duplicates, and a backlog drain. The same harness
// can be pointed at a deployed staging collector later by swapping the
// transport for real fetch (config field ready).
//
// Fault model (plan acceptance items):
//   - downstream 500s during a window → client retries (503 semantics),
//   - duplicate batch sends (replay) → sink insertId dedup,
//   - backlog builds during the outage → recovery to ZERO lag at the end.

import { handleCollectorRequest, type CollectorDeps } from '@navdhya/analytics/src/collector/pipeline';
import { FixedWindowRateLimiter } from '@navdhya/analytics/src/collector/rate-limit';
import { handleIngestRequest } from '@services/functions/ingestion/src/main';
import { MemoryRawSink } from '@services/functions/ingestion/src/raw-sink';
import { MemoryNonceStore } from '@services/functions/ingestion/src/worker-auth';
import { COLLECTOR_SECRET, clientEnvelope, consentToken } from './collector-fixtures';

export interface LoadConfig {
  eventsPerBatch: number;
  /** Unique batches offered in the sustained phase. */
  offeredBatches: number;
  /** Extra tight-loop batches in the burst phase. */
  burstBatches: number;
  /** First..last transport CALLS answered with 500 (inclusive). */
  faultWindow: { from: number; to: number };
  /** Send every Nth batch twice (replay/duplicate injection). 0 = off. */
  replayEvery: number;
  maxRetries: number;
}

export interface Percentiles {
  count: number;
  p50: number;
  p95: number;
  p99: number;
  max: number;
}

export interface LoadResult {
  uniqueBatches: number;
  uniqueEvents: number;
  duplicateSends: number;
  retries: number;
  faultResponses: number;
  rejectedPermanent: number;
  /** Pending queue at the end — must be 0 (recovery to zero-lag). */
  outstanding: number;
  /** Wall-clock achieved rate over the whole run (unique events ÷ seconds). */
  achievedEps: number;
  /** First-attempt-to-acceptance latency, retries included. */
  latencyMs: Percentiles;
  /** Rows in the sink — must equal uniqueEvents (dedup held under load). */
  sinkRows: number;
  wallMs: number;
}

export const DEFAULT_LOAD_CONFIG: LoadConfig = {
  eventsPerBatch: 25,
  offeredBatches: 400, // 10k events sustained
  burstBatches: 100, // +2.5k burst
  faultWindow: { from: 60, to: 120 },
  replayEvery: 10,
  maxRetries: 8,
};

function nearestRank(sorted: number[], p: number): number {
  if (sorted.length === 0) return 0;
  const rank = Math.max(1, Math.ceil((p / 100) * sorted.length));
  return sorted[Math.min(rank, sorted.length) - 1]!;
}

export async function runCollectorLoad(config: LoadConfig = DEFAULT_LOAD_CONFIG): Promise<LoadResult> {
  const sink = new MemoryRawSink();
  const nonceStore = new MemoryNonceStore();
  const token = await consentToken();
  const nowMs = (): number => Date.now();

  let transportCalls = 0;
  let faultResponses = 0;
  const fetchImpl: CollectorDeps['fetchImpl'] = async (url, init) => {
    transportCalls += 1;
    const inFault =
      transportCalls >= config.faultWindow.from && transportCalls <= config.faultWindow.to;
    if (inFault) {
      faultResponses += 1;
      return { status: 500, text: async () => 'downstream 500' };
    }
    const result = await handleIngestRequest(
      init.body,
      init.headers['x-signals-auth'],
      {
        sink,
        source: 'shadow',
        nowMs,
        workerSecret: COLLECTOR_SECRET,
        nonceStore,
      },
      { sourceHeader: init.headers['x-signals-source'] },
    );
    return { status: result.status, text: async () => JSON.stringify(result.body) };
  };

  const deps: CollectorDeps = {
    nowSec: () => Math.floor(Date.now() / 1000),
    consentSecret: COLLECTOR_SECRET,
    hmacSecret: COLLECTOR_SECRET,
    ingestUrl: 'https://ingestion.test/ingest',
    fetchImpl,
    nonce: () => `load_${transportCalls}_${Math.random().toString(36).slice(2)}`,
    ipLimiter: new FixedWindowRateLimiter({ limit: 1_000_000, windowMs: 60_000 }),
    sessionLimiter: new FixedWindowRateLimiter({ limit: 1_000_000, windowMs: 60_000 }),
    defaultSource: 'shadow',
    onWarn: () => {},
  };

  const totalBatches = config.offeredBatches + config.burstBatches;
  const latencies: number[] = [];
  const pending: Array<{ body: string; enqueuedAtMs: number }> = [];
  let duplicateSends = 0;
  let retries = 0;
  let rejectedPermanent = 0;
  let accepted = 0;

  const buildBody = (batchIndex: number): string => {
    const events = Array.from({ length: config.eventsPerBatch }, (_, i) =>
      clientEnvelope({
        session: { id: `sess_load_${batchIndex % 97}`, seq: i + 1 },
        params: { page_type: 'catalog' },
      }),
    );
    return JSON.stringify({ events, source: 'shadow', consentToken: token });
  };

  const attemptOnce = async (body: string): Promise<number> => {
    const response = await handleCollectorRequest(
      { bodyText: body, contentLength: null, clientIp: '198.18.0.1' },
      deps,
    );
    return response.status;
  };

  /** One batch, retries included: returns latency-to-acceptance or null (queued). */
  const deliver = async (body: string, enqueuedAtMs: number): Promise<number | null> => {
    let status = await attemptOnce(body);
    for (let attempt = 1; status >= 500 && attempt <= config.maxRetries; attempt += 1) {
      retries += 1;
      status = await attemptOnce(body);
    }
    if (status === 200) {
      accepted += 1;
      latencies.push(performance.now() - enqueuedAtMs);
      return latencies[latencies.length - 1]!;
    }
    if (status === 400 || status === 403 || status === 413) {
      // Permanent rejection must NEVER happen in this workload.
      rejectedPermanent += 1;
      return null;
    }
    return null; // still failing → backlog (drained after the fault)
  };

  const started = performance.now();

  // Sustained phase: paced-ish loop with duplicate injection.
  for (let batchIndex = 0; batchIndex < totalBatches; batchIndex += 1) {
    const body = buildBody(batchIndex);
    const enqueuedAtMs = performance.now();
    const latency = await deliver(body, enqueuedAtMs);
    if (latency === null) pending.push({ body, enqueuedAtMs });
    if (config.replayEvery > 0 && (batchIndex + 1) % config.replayEvery === 0) {
      duplicateSends += 1;
      const replayLatency = await deliver(body, enqueuedAtMs);
      if (replayLatency === null) pending.push({ body, enqueuedAtMs });
    }
  }

  // Recovery: drain the backlog until zero-lag (bounded to prove termination).
  let drainPasses = 0;
  while (pending.length > 0 && drainPasses <= 100) {
    drainPasses += 1;
    const next = pending.shift()!;
    const latency = await deliver(next.body, next.enqueuedAtMs);
    if (latency === null && rejectedPermanent === 0) pending.push(next);
  }

  const wallMs = performance.now() - started;
  const sorted = latencies.slice().sort((a, b) => a - b);

  return {
    uniqueBatches: totalBatches,
    uniqueEvents: totalBatches * config.eventsPerBatch,
    duplicateSends,
    retries,
    faultResponses,
    rejectedPermanent,
    outstanding: pending.length,
    achievedEps: wallMs > 0 ? (accepted * config.eventsPerBatch) / (wallMs / 1000) : 0,
    latencyMs: {
      count: sorted.length,
      p50: nearestRank(sorted, 50),
      p95: nearestRank(sorted, 95),
      p99: nearestRank(sorted, 99),
      max: sorted.length > 0 ? sorted[sorted.length - 1]! : 0,
    },
    sinkRows: sink.size(),
    wallMs,
  };
}
