// lib/security/rate-limit.ts
// Tiny in-process token-bucket rate limiter with a per-call cost.
//
// Why this exists:
//   - `app/api/revalidate/route.ts` had a 30 req/min/IP limit, but a
//     single request could carry 100K slugs. The bucket charged 1 token
//     regardless of payload size, so an attacker could DoS the registry
//     and ISR machinery with one big request.
//   - Several API routes will need similar protection soon (PR 1 SMS
//     rate-limiting, PR 11 ad decision). Centralising the bucket now
//     keeps the call sites small and consistent.
//
// Scope:
//   - In-process, per-instance. NOT shared across regions or pods. For
//     global limits, swap in ElastiCache Redis (PR 11.5).
//   - No eviction; stale entries can be GC'd by a periodic sweep, but
//     for ≤ 100k unique IPs this is fine.
//
// Algorithm:
//   - Sliding fixed-window: each key has a `count` and `resetAt`.
//     If the window has expired, reset count to 0.
//     Increment by `cost`; if final count > `limit`, deny.

export interface RateLimitConfig {
  /** Maximum number of "units" allowed in one window. */
  limit: number;
  /** Window duration in milliseconds. */
  windowMs: number;
}

export interface RateLimitResult {
  allowed: boolean;
  /** Remaining units in the current window. */
  remaining: number;
  /** When the current window expires (ms since epoch). */
  resetAt: number;
  /** Total limit (echoed for X-RateLimit-* headers). */
  limit: number;
}

export class InProcessRateLimiter {
  private readonly buckets = new Map<string, { count: number; resetAt: number }>();
  private readonly config: RateLimitConfig;
  private readonly maxKeys: number;
  private lastSweep = Date.now();
  private static readonly SWEEP_INTERVAL_MS = 5 * 60_000;

  constructor(config: RateLimitConfig, options: { maxKeys?: number } = {}) {
    if (config.limit <= 0) throw new Error("RateLimiter: limit must be > 0");
    if (config.windowMs <= 0) throw new Error("RateLimiter: windowMs must be > 0");
    this.config = config;
    this.maxKeys = options.maxKeys ?? 100_000;
  }

  /**
   * Check whether `key` is allowed to perform `cost` units of work.
   * Always increments by `cost`; if the bucket exceeds the limit, the
   * call is denied but the cost is still recorded (so a flood of
   * over-limit calls does not let you re-acquire a fresh window).
   */
  check(key: string, cost: number = 1): RateLimitResult {
    if (cost <= 0) throw new Error("RateLimiter: cost must be > 0");
    this.maybeSweep();

    const now = Date.now();
    let bucket = this.buckets.get(key);
    if (!bucket || now > bucket.resetAt) {
      // Lazy eviction: if map is over the cap, GC expired entries first
      if (this.buckets.size >= this.maxKeys) this.sweepExpired(now);
      bucket = { count: 0, resetAt: now + this.config.windowMs };
      this.buckets.set(key, bucket);
    }
    bucket.count += cost;
    const remaining = Math.max(0, this.config.limit - bucket.count);
    return {
      allowed: bucket.count <= this.config.limit,
      remaining,
      resetAt: bucket.resetAt,
      limit: this.config.limit,
    };
  }

  /** Format the rate-limit response headers used by Next.js. */
  toHeaders(result: RateLimitResult): Record<string, string> {
    return {
      "X-RateLimit-Limit": String(result.limit),
      "X-RateLimit-Remaining": String(result.remaining),
      "X-RateLimit-Reset": String(Math.ceil(result.resetAt / 1000)),
    };
  }

  private maybeSweep(): void {
    const now = Date.now();
    if (now - this.lastSweep < InProcessRateLimiter.SWEEP_INTERVAL_MS) return;
    this.sweepExpired(now);
  }

  private sweepExpired(now: number): void {
    for (const [key, bucket] of this.buckets) {
      if (now > bucket.resetAt) this.buckets.delete(key);
    }
    this.lastSweep = now;
  }

  /** Test helper: clear all state. */
  reset(): void {
    this.buckets.clear();
    this.lastSweep = Date.now();
  }
}

/**
 * Extract a stable client IP from a Next.js request. Prefers the
 * first entry in `X-Forwarded-For` (the original client), falling
 * back to `X-Real-IP`, then `'unknown'`.
 *
 * SECURITY: do NOT trust `req.ip` in Next 16 proxy mode — it is
 * derived from the request URL and can be spoofed.
 */
export function clientIp(headers: Headers): string {
  const xff = headers.get("x-forwarded-for");
  if (xff) {
    const first = xff.split(",")[0]?.trim();
    if (first) return first;
  }
  const xri = headers.get("x-real-ip");
  if (xri) return xri.trim();
  return "unknown";
}
