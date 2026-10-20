// packages/analytics/src/client/retry-policy.ts
//
// Exponential backoff with full jitter for eligible client send attempts.
// Scheduling is owned by the caller (flush loop / batcher); this module is
// pure so the policy is testable and tunable without timers.

export interface BackoffOptions {
  baseMs?: number;
  maxMs?: number;
  /** 0..1 — fraction of the computed delay that is randomized. 1 = full jitter. */
  jitter?: number;
  /** Injectable RNG for deterministic tests. */
  random?: () => number;
}

const DEFAULTS: Required<Omit<BackoffOptions, 'random'>> = {
  baseMs: 500,
  maxMs: 30_000,
  jitter: 1,
};

/**
 * Delay before attempt number `attempt` (0-based: first retry = 0).
 * unjittered = min(maxMs, baseMs * 2^attempt); result adds jitter in
 * [(1-jitter), 1] × unjittered so concurrent clients desynchronize.
 */
export function nextBackoffMs(attempt: number, opts: BackoffOptions = {}): number {
  const { baseMs, maxMs, jitter } = { ...DEFAULTS, ...opts };
  if (!Number.isInteger(attempt) || attempt < 0) throw new Error('backoff: attempt must be a non-negative integer');
  const raw = Math.min(maxMs, baseMs * 2 ** attempt);
  const random = opts.random ?? Math.random;
  const floor = 1 - jitter;
  return Math.round(raw * (floor + (1 - floor) * random()));
}
