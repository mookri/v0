// Per-family volume anomaly detection (plan B6 bullet 5): rolling baseline +
// robust z-score (median/MAD — resistant to the baseline itself drifting).
//
// Policy (evaluation criterion): a 10× spike on a diagnostic family
// ANNOTATES the affected window; only revenue-adjacent families PAGE. The
// result carries the exact window and baseline numbers so the annotation is
// self-explaining — no black box.
//
// Robust z = 0.6745 × (x − median) / MAD, with two documented fallbacks:
//   - MAD = 0 → mean absolute deviation (a narrower but real spread);
//   - both = 0 → perfectly flat history: any deviation is definitive
//     (z = ±Infinity, or 0 when the current value is also flat).

export const REVENUE_ADJACENT_FAMILIES: readonly string[] = [
  'payment_succeeded',
  'payment_failed',
  'booking_confirmed',
];

export interface AnomalyOptions {
  /** Minimum history buckets before a baseline is trusted (default 7). */
  minHistory?: number;
  /** |z| at or above which a bucket is anomalous (default 3.5). */
  zThreshold?: number;
}

export interface AnomalyInput {
  /** Event family (usually the event name). */
  family: string;
  /** Count in the current bucket. */
  current: number;
  /** Prior bucket counts, oldest first. */
  history: number[];
  /** The current bucket's window (epoch ms) — annotated on breach. */
  windowStartMs: number;
  windowEndMs: number;
}

export type AnomalyStatus = 'ok' | 'annotate' | 'page';

export interface AnomalyResult {
  family: string;
  status: AnomalyStatus;
  z: number;
  current: number;
  baselineMedian: number;
  baselineMad: number;
  historyLength: number;
  zThreshold: number;
  revenueAdjacent: boolean;
  /** The affected window — present iff status !== 'ok'. */
  window: { fromMs: number; toMs: number } | null;
  note: string;
}

function median(values: number[]): number {
  const sorted = [...values].sort((a, b) => a - b);
  const mid = Math.floor(sorted.length / 2);
  return sorted.length % 2 === 1 ? sorted[mid]! : (sorted[mid - 1]! + sorted[mid]!) / 2;
}

export function detectAnomaly(input: AnomalyInput, options: AnomalyOptions = {}): AnomalyResult {
  const minHistory = options.minHistory ?? 7;
  const zThreshold = options.zThreshold ?? 3.5;
  const revenueAdjacent = REVENUE_ADJACENT_FAMILIES.includes(input.family);

  const base = {
    family: input.family,
    current: input.current,
    historyLength: input.history.length,
    zThreshold,
    revenueAdjacent,
    window: null,
  };

  if (input.history.length < minHistory) {
    return {
      ...base,
      status: 'ok',
      z: 0,
      baselineMedian: 0,
      baselineMad: 0,
      note: `baseline not trusted yet (${input.history.length} < ${minHistory} buckets) — no call made`,
    };
  }

  const med = median(input.history);
  const mad = median(input.history.map((value) => Math.abs(value - med)));
  let scale = mad;
  if (scale === 0) {
    const meanAbsDev =
      input.history.reduce((sum, value) => sum + Math.abs(value - med), 0) / input.history.length;
    scale = meanAbsDev;
  }

  const z =
    scale === 0
      ? input.current === med
        ? 0
        : input.current > med
          ? Number.POSITIVE_INFINITY
          : Number.NEGATIVE_INFINITY
      : (0.6745 * (input.current - med)) / scale;

  const anomalous = Math.abs(z) >= zThreshold;
  if (!anomalous) {
    return {
      ...base,
      status: 'ok',
      z,
      baselineMedian: med,
      baselineMad: mad,
      note: `within ${zThreshold}σ of baseline`,
    };
  }

  const status: AnomalyStatus = revenueAdjacent ? 'page' : 'annotate';
  return {
    ...base,
    status,
    z,
    baselineMedian: med,
    baselineMad: mad,
    window: { fromMs: input.windowStartMs, toMs: input.windowEndMs },
    note:
      `${input.family} z=${z === Number.POSITIVE_INFINITY ? '+∞' : z.toFixed(2)} ` +
      `(current ${input.current} vs median ${med}, MAD ${mad}) — ` +
      `${revenueAdjacent ? 'revenue-adjacent: PAGE' : 'diagnostic family: annotate only'}`,
  };
}
