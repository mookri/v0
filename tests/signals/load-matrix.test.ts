// B9 acceptance — full-matrix load test (plan §1 + B9 bullet 1):
//   Both legs of the platform under the §1 fault matrix — retries,
//   duplicate delivery, downstream failure, queue backlog, recovery —
//   not just clean ingestion:
//     leg 1 (client):    collector → ingestion → raw sink at 10k+ eps
//                        (B3 harness, re-run as part of the matrix);
//     leg 2 (truth path): emitters → outbox → publisher → Pub/Sub →
//                        ingestion consumer → sink at 10k committed
//                        domain events, closing with Level-1
//                        reconciliation (zero unexplained loss).
//   Correctness invariants are hard-asserted; throughput/latency numbers
//   are the recorded §1 evidence (sanity floors only).

import { describe, expect, it } from 'vitest';
import { DEFAULT_LOAD_CONFIG, runCollectorLoad } from './helpers/collector-load';
import { DEFAULT_MATRIX_CONFIG, runTruthPathMatrix } from './helpers/full-matrix';

describe('B9 acceptance — full-matrix load (§1 SLOs)', () => {
  it(
    'client leg: 10k+ eps through collector with faults, duplicates, backlog, recovery',
    async () => {
      const result = await runCollectorLoad(DEFAULT_LOAD_CONFIG);
      // eslint-disable-next-line no-console
      console.log(
        `[matrix:client] eps=${result.achievedEps.toFixed(0)} p95=${result.latencyMs.p95.toFixed(1)}ms ` +
          `retries=${result.retries} dupSends=${result.duplicateSends} faults=${result.faultResponses} ` +
          `sinkRows=${result.sinkRows}/${result.uniqueEvents} outstanding=${result.outstanding}`,
      );

      // Fault matrix actually exercised:
      expect(result.faultResponses).toBeGreaterThan(0); // downstream failure window
      expect(result.retries).toBeGreaterThan(0); // retries
      expect(result.duplicateSends).toBeGreaterThan(0); // duplicate delivery
      expect(result.outstanding).toBe(0); // backlog recovered to zero-lag

      // Correctness under the matrix (hard):
      expect(result.rejectedPermanent).toBe(0);
      expect(result.sinkRows).toBe(result.uniqueEvents); // exactly-once rows

      // §1 evidence floors (numbers above are the record). Latency is
      // recorded per accepted BATCH DELIVERY (unique + replayed duplicates),
      // retries included — that is the freshness unit of this leg.
      expect(result.achievedEps).toBeGreaterThan(50);
      expect(result.latencyMs.count).toBe(result.uniqueBatches + result.duplicateSends);
    },
    180_000,
  );

  it(
    'truth-path leg: 10k committed domain events, full fault matrix, Level-1 clean',
    async () => {
      const result = await runTruthPathMatrix(DEFAULT_MATRIX_CONFIG);
      // eslint-disable-next-line no-console
      console.log(
        `[matrix:truth] committed=${result.committed} eps=${result.eps.toFixed(0)} ` +
          `p50=${result.latencyMs.p50.toFixed(1)}ms p95=${result.latencyMs.p95.toFixed(1)}ms ` +
          `faults=${result.transientFaults} dupDeliveries=${result.duplicateDeliveries} ` +
          `backlogPeak=${result.backlogPeak} backlogFinal=${result.backlogFinal} ` +
          `recoveryCycles=${result.recoveryCycles} sinkRows=${result.sinkRows} wall=${result.wallMs.toFixed(0)}ms`,
      );

      // Volume + fault matrix actually exercised:
      expect(result.committed).toBe(10_000);
      expect(result.transientFaults).toBeGreaterThan(0); // downstream failure
      expect(result.duplicateDeliveries).toBeGreaterThan(0); // at-least-once redelivery
      expect(result.backlogPeak).toBeGreaterThan(0); // backlog built during the fault

      // Correctness under the matrix (hard):
      expect(result.deadLettered).toBe(0); // attempts ceiling never hit
      expect(result.consumerRejections).toBe(0); // P0 events are schema-valid
      expect(result.sinkRows).toBe(result.committed); // exactly one row per envelope ID
      expect(result.backlogFinal).toBe(0); // recovery to zero-lag

      // §1 domain-event integrity: zero unexplained loss, zero duplicates.
      expect(result.reconcile.ok).toBe(true);
      expect(result.reconcile.missingPublished).toEqual([]);
      expect(result.reconcile.missingPersisted).toEqual([]);
      expect(result.reconcile.duplicatedPersisted).toEqual([]);

      // §1 warehouse-freshness evidence: outbox-append → sink-put p95
      // recorded above; in-process floor asserted (15 min = 900,000 ms).
      expect(result.latencyMs.count).toBe(result.committed);
      expect(result.latencyMs.p95).toBeLessThan(900_000);
      expect(result.eps).toBeGreaterThan(100);
    },
    240_000,
  );
});
