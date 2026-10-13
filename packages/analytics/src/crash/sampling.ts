// Adaptive error sampling (plan B8 crash bullet): crash volume is
// user-driven — a broken release can produce tens of thousands of errors
// per minute, and shipping all of them distorts rate metrics and costs.
// The sampler keeps a bounded fraction so GROUP RATES stay comparable
// (sampling uniformly per error preserves proportions) while the floor
// guarantees low-volume groups still surface.
//
// Honesty notes:
//   - Sampling is per-error with a probability derived from observed
//     volume; rates computed downstream are sampling-corrected by the
//     same probability (recorded on the batch), never raw counts.
//   - The sampler is in-memory state; deployments own persistence across
//     restarts (restart ⇒ temporarily rate 1.0, then re-adapts — safe
//     direction: over-collection, not loss).

export const DEFAULT_SAMPLE_FLOOR = 0.01;
export const DEFAULT_TARGET_PER_MINUTE = 60;

/** Probability in [floor, 1]: keeps observed rate near the target. */
export function adaptiveSampleRate(
  errorsPerMinute: number,
  targetPerMinute: number = DEFAULT_TARGET_PER_MINUTE,
  floor: number = DEFAULT_SAMPLE_FLOOR,
): number {
  if (errorsPerMinute <= 0) return 1;
  return Math.min(1, Math.max(floor, targetPerMinute / errorsPerMinute));
}

export interface ErrorSamplerOptions {
  targetPerMinute?: number;
  floor?: number;
  /** Rolling window over which the rate is estimated (default 60s). */
  windowMs?: number;
  nowMs: () => number;
  /** Injected for determinism in tests; defaults to Math.random. */
  random?: () => number;
}

export class ErrorSampler {
  private readonly targetPerMinute: number;
  private readonly floor: number;
  private readonly windowMs: number;
  private readonly nowMs: () => number;
  private readonly random: () => number;
  private readonly stamps: number[] = [];

  constructor(options: ErrorSamplerOptions) {
    this.targetPerMinute = options.targetPerMinute ?? DEFAULT_TARGET_PER_MINUTE;
    this.floor = options.floor ?? DEFAULT_SAMPLE_FLOOR;
    this.windowMs = options.windowMs ?? 60_000;
    this.nowMs = options.nowMs;
    this.random = options.random ?? Math.random;
  }

  /** Current estimated errors/minute over the rolling window. */
  observedRate(): number {
    const cutoff = this.nowMs() - this.windowMs;
    while (this.stamps.length > 0 && this.stamps[0]! < cutoff) this.stamps.shift();
    return (this.stamps.length * 60_000) / this.windowMs;
  }

  /** Decide whether THIS error is shipped. Every error (sampled or not)
   *  updates the rate estimate, so bursts are seen immediately. */
  shouldSample(): boolean {
    const rate = this.observedRate();
    const probability = adaptiveSampleRate(rate, this.targetPerMinute, this.floor);
    this.stamps.push(this.nowMs());
    return this.random() < probability;
  }

  /** The probability the most recent shouldSample decision used (for
   *  sampling-corrected downstream rates). */
  lastProbability(): number {
    return adaptiveSampleRate(this.observedRate(), this.targetPerMinute, this.floor);
  }
}
