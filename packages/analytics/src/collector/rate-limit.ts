// packages/analytics/src/collector/rate-limit.ts
//
// Fixed-window rate limiter used by BOTH collector adapters (plan B3, item 2:
// "rate limits per session + IP"). Deliberately dumb and bounded:
//   - one bucket per key, window reset at `now + windowMs`,
//   - memory bounded by `maxKeys` (prune expired first, then evict oldest),
//   - injectable clock so tests never sleep.
//
// Honest limitation (documented): buckets are PER EDGE ISOLATE — there is no
// shared store at the edge, and the collector is stateless by rule. A
// distributed flood is degraded, not stopped, by this layer; the ingestion
// service's HMAC window and per-batch caps remain the hard backstop.

export interface RateLimitDecision {
  /** `ok` — deliberately not "allowed": analytics never authorizes anything
   *  (this is transport shaping, not an authorization decision). */
  ok: boolean;
  /** End of the current window for this key. */
  resetAtMs: number;
  /** Whole seconds to wait before retrying (0 when ok). */
  retryAfterSec: number;
}

export interface RateLimiterLike {
  check(key: string, cost?: number): RateLimitDecision;
}

export interface FixedWindowOptions {
  limit: number;
  windowMs: number;
  maxKeys?: number;
}

interface Bucket {
  count: number;
  resetAtMs: number;
}

export class FixedWindowRateLimiter implements RateLimiterLike {
  private readonly buckets = new Map<string, Bucket>();
  private readonly limit: number;
  private readonly windowMs: number;
  private readonly maxKeys: number;
  private readonly nowMs: () => number;

  constructor(options: FixedWindowOptions, nowMs: () => number = Date.now) {
    if (options.limit < 1 || options.windowMs < 1) throw new Error('rate-limit: invalid options');
    this.limit = options.limit;
    this.windowMs = options.windowMs;
    this.maxKeys = options.maxKeys ?? 50_000;
    this.nowMs = nowMs;
  }

  check(key: string, cost = 1): RateLimitDecision {
    const now = this.nowMs();
    if (this.buckets.size >= this.maxKeys) this.prune(now);

    let bucket = this.buckets.get(key);
    if (!bucket || bucket.resetAtMs <= now) {
      bucket = { count: 0, resetAtMs: now + this.windowMs };
      this.buckets.set(key, bucket);
    }
    if (bucket.count + cost > this.limit) {
      return {
        ok: false,
        resetAtMs: bucket.resetAtMs,
        retryAfterSec: Math.max(1, Math.ceil((bucket.resetAtMs - now) / 1000)),
      };
    }
    bucket.count += cost;
    return { ok: true, resetAtMs: bucket.resetAtMs, retryAfterSec: 0 };
  }

  /** Test/observability helper. */
  size(): number {
    return this.buckets.size;
  }

  private prune(now: number): void {
    for (const [key, bucket] of this.buckets) {
      if (bucket.resetAtMs <= now) this.buckets.delete(key);
    }
    // Still full of live buckets → evict oldest inserts (Map order) so
    // memory stays bounded even under key-flooding.
    for (const key of this.buckets.keys()) {
      if (this.buckets.size <= this.maxKeys) break;
      this.buckets.delete(key);
    }
  }
}
