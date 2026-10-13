// B3 acceptance — collector load harness. Offered volume is 10k events
// sustained + 2.5k burst through the REAL collector→ingestion chain, with an
// injected downstream-500 window, duplicate sends, and a backlog drain.
// Correctness invariants are hard-asserted; throughput/latency SLOs are
// recorded as results with generous in-process floors (the harness runs in
// one Node thread — a deployed-edge run repeats this against the staging URL).

import { describe, expect, it } from 'vitest';
import { DEFAULT_LOAD_CONFIG, runCollectorLoad, type LoadConfig } from './helpers/collector-load';

function record(label: string, result: Awaited<ReturnType<typeof runCollectorLoad>>): void {
  // eslint-disable-next-line no-console
  console.log(
    `[load:${label}] eps=${result.achievedEps.toFixed(0)} ` +
      `p50=${result.latencyMs.p50.toFixed(1)}ms p95=${result.latencyMs.p95.toFixed(1)}ms ` +
      `p99=${result.latencyMs.p99.toFixed(1)}ms retries=${result.retries} ` +
      `faults=${result.faultResponses} dupSends=${result.duplicateSends} ` +
      `sinkRows=${result.sinkRows}/${result.uniqueEvents} wall=${result.wallMs.toFixed(0)}ms`,
  );
}

describe('collector load harness (B3)', () => {
  it(
    'sustained 10k events + burst: zero loss, zero dup rows, recovery to zero-lag',
    async () => {
      const result = await runCollectorLoad(DEFAULT_LOAD_CONFIG);
      record('sustained+burst', result);

      // Correctness invariants (hard):
      expect(result.rejectedPermanent).toBe(0);
      expect(result.outstanding).toBe(0); // recovery to zero-lag
      expect(result.uniqueBatches).toBe(500); // 400 sustained + 100 burst
      expect(result.duplicateSends).toBe(50); // replay injection actually ran
      expect(result.faultResponses).toBeGreaterThan(0); // the 500-window hit
      expect(result.retries).toBeGreaterThan(0); // and the client retried
      expect(result.sinkRows).toBe(result.uniqueEvents); // dedup exact: one row per event

      // Recorded SLOs: the measured numbers are the record (logged above;
      // ~8k eps / p95 <10 ms standalone, scaled down under suite-wide CPU
      // contention). Only a sanity floor is asserted — correctness above is
      // what must never regress, and wall-clock throughput is machine-load
      // dependent, not a contract of this suite.
      expect(result.achievedEps).toBeGreaterThan(50);
      expect(result.latencyMs.count).toBeGreaterThan(0);
    },
    120_000,
  );

  it(
    'fault-free burst: every batch accepted first try, backlog stays empty',
    async () => {
      const burstConfig: LoadConfig = {
        ...DEFAULT_LOAD_CONFIG,
        offeredBatches: 50,
        burstBatches: 300,
        faultWindow: { from: 0, to: 0 }, // never triggers (window start > call count)
        replayEvery: 0,
      };
      const result = await runCollectorLoad(burstConfig);
      record('burst', result);

      expect(result.rejectedPermanent).toBe(0);
      expect(result.outstanding).toBe(0);
      expect(result.retries).toBe(0);
      expect(result.faultResponses).toBe(0);
      expect(result.sinkRows).toBe(result.uniqueEvents);
      expect(result.latencyMs.count).toBe(result.uniqueBatches);
    },
    120_000,
  );
});
