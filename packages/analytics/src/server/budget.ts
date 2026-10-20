// Budget alerts + reduction ladder (plan B9: "Budget alerts live at 50/80/100%
// with the documented reduction ladder (diagnostics first; revenue pipeline
// last, never automatic)").
//
// Cost posture: BigQuery spend is gated three ways — static lint (B5),
// `maximum_bytes_billed` per job (B5), and the USAGE ladder here. The ladder
// is ORDERED and PARTIAL: each rung sheds a cost class while the platform
// keeps operating; the last rung is the revenue pipeline and it is NEVER
// automatic — a human decides before revenue evidence is affected.

export const BUDGET_ALERT_THRESHOLDS = [50, 80, 100] as const;

export type BudgetLevel = 'ok' | 'warn_50' | 'warn_80' | 'breach_100';

export interface BudgetUsage {
  /** Fraction of the monthly budget consumed, in [0, ∞). */
  usedRatio: number;
  level: BudgetLevel;
  /** Thresholds crossed, ascending (e.g. [50, 80]). */
  crossed: number[];
  /** Alert message for the highest crossed threshold; null when ok. */
  message: string | null;
}

/** Classify spend against the 50/80/100 alert ladder. */
export function classifyBudgetUsage(usedBytes: number, budgetBytes: number): BudgetUsage {
  if (!Number.isFinite(usedBytes) || !Number.isFinite(budgetBytes) || budgetBytes <= 0) {
    throw new Error(`classifyBudgetUsage: invalid inputs used=${usedBytes} budget=${budgetBytes}`);
  }
  const usedRatio = usedBytes / budgetBytes;
  const percent = usedRatio * 100;
  const crossed = BUDGET_ALERT_THRESHOLDS.filter((threshold) => percent >= threshold);
  const highest = crossed.length > 0 ? crossed[crossed.length - 1]! : null;
  const level: BudgetLevel =
    highest === 100 ? 'breach_100' : highest === 80 ? 'warn_80' : highest === 50 ? 'warn_50' : 'ok';
  return {
    usedRatio,
    level,
    crossed,
    message:
      highest === null
        ? null
        : `signals budget at ${percent.toFixed(1)}% (${highest}% threshold crossed)` +
          (highest === 100 ? ' — reduction ladder REQUIRED (see budget.ts)' : ''),
  };
}

export interface LadderRung {
  id: string;
  order: number;
  /** What this rung sheds / does. */
  action: string;
  /** True when the rung touches revenue/booking evidence pipelines. */
  touchesRevenue: boolean;
  /** The ladder is manual by design; revenue rungs are doubly so. */
  automatic: boolean;
}

/** Ordered reduction ladder. Diagnostics first; revenue last, never automatic. */
export const REDUCTION_LADDER: readonly LadderRung[] = Object.freeze([
  Object.freeze({
    id: 'reduce_crash_sampling',
    order: 1,
    action:
      'tighten adaptive crash/error sampling toward the floor (crash/sampling.ts) — highest-volume diagnostics shed first',
    touchesRevenue: false,
    automatic: false,
  }),
  Object.freeze({
    id: 'shrink_diagnostic_windows',
    order: 2,
    action:
      'shrink diagnostic mart windows (error_groups, anomaly scans) and pause non-critical nightly checks',
    touchesRevenue: false,
    automatic: false,
  }),
  Object.freeze({
    id: 'reduce_shadow_volume',
    order: 3,
    action:
      'reduce shadow-comparison sampling (B3/B5 comparison is evidence, not truth — shed before first-party marts)',
    touchesRevenue: false,
    automatic: false,
  }),
  Object.freeze({
    id: 'pause_experiments',
    order: 4,
    action:
      'pause running experiments (assignment continues; exposure logging and readouts pause) — analytical, not revenue',
    touchesRevenue: false,
    automatic: false,
  }),
  Object.freeze({
    id: 'revenue_pipeline_manual',
    order: 5,
    action:
      'REDUCE REVENUE PIPELINE (payment/booking marts, key-event parity checks) — requires explicit human approval; never automatic',
    touchesRevenue: true,
    automatic: false,
  }),
]);

/** Rungs to apply for a crossed threshold: 100 → full ladder (minus revenue
 *  without approval), 80 → first two rungs, 50 → informational only. */
export function ladderForLevel(level: BudgetLevel): readonly LadderRung[] {
  if (level === 'breach_100') return REDUCTION_LADDER;
  if (level === 'warn_80') return REDUCTION_LADDER.filter((rung) => rung.order <= 2);
  return [];
}
