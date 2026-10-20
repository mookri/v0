// Drill ledger (plan B9): every failure drill records its injected failure,
// expected detection/degradation/recovery and the MEASURED times, forming
// the drill-results table the acceptance requires. The final test asserts
// every PLANNED drill is present — deleting a drill breaks the table.

export interface DrillRecord {
  drill: string;
  /** docs/analytics/runbooks/<file>.md — must exist; exercised evidence. */
  runbook: string;
  injectedFailure: string;
  expectedDetection: string;
  expectedDegradation: string;
  expectedRecovery: string;
  /** Injection → first visible detection signal. */
  detectionMs: number;
  /** Heal → invariants restored (zero-lag / zero-loss / zero-backlog). */
  recoveryMs: number;
  /** Business flows or data preservation evidence (per drill). */
  containmentOk: boolean;
  /** Hard correctness invariants of the drill. */
  invariantsOk: boolean;
}

export const PLANNED_DRILLS = [
  'collector-outage',
  'pubsub-backlog',
  'ingestion-500s',
  'consent-service-outage',
  'bigquery-quota-exhaustion',
  'misdeployed-schema',
] as const;

const LEDGER: DrillRecord[] = [];

export function recordDrill(record: DrillRecord): void {
  LEDGER.push(record);
}

export function drillLedger(): DrillRecord[] {
  return [...LEDGER];
}
