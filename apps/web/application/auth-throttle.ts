import { InProcessRateLimiter, clientIp } from '@/lib/security/rate-limit';

/**
 * P0 auth throttle — port/adapter per `auth/p0-contract.md` §0/§3.
 *
 * `RateLimiter` is the abstraction; `InProcessRateLimiter` is the P0
 * adapter. Do NOT let call sites reach past this interface: in a
 * multi-instance deployment an in-process limiter yields
 * `limit × instance_count`, so the Redis adapter (Phase 3) must be
 * drop-in. See `auth/p0-contract.md`.
 */
export interface RateLimitDecision {
  allowed: boolean;
  remaining: number;
  /** Seconds until the caller may retry (0 when allowed). */
  retryAfter: number;
}

export interface RateLimiter {
  consume(key: string, limit: number, windowSeconds: number): Promise<RateLimitDecision>;
}

/** P0 adapter: per-instance sliding window. NOT global across pods. */
export class InProcessAuthRateLimiter implements RateLimiter {
  // Buckets are keyed per (limiter, limit, window) triple because a
  // single InProcessRateLimiter is configured per-instance at
  // construction, and auth callsites use different windows.
  private readonly limiters = new Map<string, InProcessRateLimiter>();

  private limiterFor(limit: number, windowSeconds: number): InProcessRateLimiter {
    const key = `${limit}:${windowSeconds}`;
    let limiter = this.limiters.get(key);
    if (!limiter) {
      limiter = new InProcessRateLimiter(
        { limit, windowMs: windowSeconds * 1000 },
        { maxKeys: 50_000 },
      );
      this.limiters.set(key, limiter);
    }
    return limiter;
  }

  async consume(key: string, limit: number, windowSeconds: number): Promise<RateLimitDecision> {
    const result = this.limiterFor(limit, windowSeconds).check(key, 1);
    const retryAfter = result.allowed
      ? 0
      : Math.max(1, Math.ceil((result.resetAt - Date.now()) / 1000));
    return { allowed: result.allowed, remaining: result.remaining, retryAfter };
  }

  headers(decision: RateLimitDecision, limit: number): Record<string, string> {
    const headers: Record<string, string> = {
      'X-RateLimit-Limit': String(limit),
      'X-RateLimit-Remaining': String(decision.remaining),
    };
    if (!decision.allowed) headers['Retry-After'] = String(decision.retryAfter);
    return headers;
  }

  /** Test helper. */
  reset(): void {
    for (const limiter of this.limiters.values()) limiter.reset();
    this.limiters.clear();
  }
}

export const authRateLimiter = new InProcessAuthRateLimiter();

/** Per-identity throttle key (contract §3: backend is still authoritative). */
export function identityKey(purpose: string, identity: string): string {
  return `auth:${purpose}:id:${identity.toLowerCase()}`;
}

/** Per-IP throttle key — first line before identity is even parsed. */
export function ipKey(purpose: string, headers: Headers): string {
  return `auth:${purpose}:ip:${clientIp(headers)}`;
}

/**
 * P0 throttle policy (contract §3, gate: "Resend spam → 429").
 * These are BFF-side abuse limits; Laravel enforces the real
 * attempt/lock counter (I8).
 */
export const THROTTLE = {
  /** OTP issue / resend — 5 per 10 min per identity. */
  otpIdentity: { limit: 5, windowSeconds: 600 },
  /** OTP issue — 10 per 10 min per IP. */
  otpIp: { limit: 10, windowSeconds: 600 },
  /** Login attempts — 10 per 10 min per IP. */
  loginIp: { limit: 10, windowSeconds: 600 },
  /** Signup intent — 5 per 10 min per IP. */
  signupIp: { limit: 5, windowSeconds: 600 },
  /** MFA verify — 5 per 15 min per IP (mirrors backend lock window). */
  mfaIp: { limit: 5, windowSeconds: 900 },
} as const;

/**
 * Idempotency claim with atomic ABSENT → IN_PROGRESS → COMPLETED
 * transition (contract §4). In-process for P0; the interface keeps the
 * Redis/DB adapter swappable.
 *
 * `fingerprint` is part of the key material: same key + different body
 * must yield 409, never silent reuse (I13).
 */
export interface IdempotencyStore {
  /**
   * Attempts to claim `key` for `fingerprint`.
   *  - 'claimed'      → caller owns the operation, proceed
   *  - 'in-progress'  → another request owns it right now
   *  - 'completed'    → same fingerprint already succeeded (see getResult)
   *  - 'conflict'     → same key, different fingerprint (409)
   */
  claim(
    key: string,
    fingerprint: string,
    ttlSeconds?: number,
  ): Promise<'claimed' | 'in-progress' | 'completed' | 'conflict'>;
  /** Record the successful response payload for replay (contract §4). */
  complete(key: string, result?: unknown): Promise<void>;
  /** The stored payload when claim() returned 'completed'. */
  getResult(key: string): unknown;
}

interface IdempotencyRecord {
  fingerprint: string;
  status: 'IN_PROGRESS' | 'COMPLETED';
  expiresAt: number;
  result?: unknown;
}

export class InProcessIdempotencyStore implements IdempotencyStore {
  private readonly records = new Map<string, IdempotencyRecord>();

  async claim(
    key: string,
    fingerprint: string,
    ttlSeconds = 600,
  ): Promise<'claimed' | 'in-progress' | 'completed' | 'conflict'> {
    const now = Date.now();
    const existing = this.records.get(key);

    if (existing && existing.expiresAt <= now) {
      this.records.delete(key);
    } else if (existing) {
      if (existing.fingerprint !== fingerprint) return 'conflict';
      if (existing.status === 'COMPLETED') return 'completed';
      return 'in-progress';
    }

    // Atomic claim within this process: Map.set is synchronous and we
    // never re-check after setting, so two concurrent callers cannot
    // both observe ABSENT.
    this.records.set(key, {
      fingerprint,
      status: 'IN_PROGRESS',
      expiresAt: now + ttlSeconds * 1000,
    });
    return 'claimed';
  }

  async complete(key: string, result?: unknown): Promise<void> {
    const record = this.records.get(key);
    if (record) {
      record.status = 'COMPLETED';
      record.result = result;
    }
  }

  getResult(key: string): unknown {
    return this.records.get(key)?.result;
  }

  /** Test helper. */
  reset(): void {
    this.records.clear();
  }
}

export const idempotencyStore = new InProcessIdempotencyStore();

/** sha256 of a canonical JSON body (contract §4 requestFingerprint). */
export async function requestFingerprint(body: unknown): Promise<string> {
  const canonical = JSON.stringify(body ?? null);
  const digest = await crypto.subtle.digest('SHA-256', new TextEncoder().encode(canonical));
  return Array.from(new Uint8Array(digest))
    .map((b) => b.toString(16).padStart(2, '0'))
    .join('');
}
