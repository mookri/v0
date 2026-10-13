// Transactional outbox contract (plan B2) — the primary integrity seam of
// the truth path: business write + outbox append share one database
// transaction; publication is at-least-once with lease-based redelivery;
// effects stay idempotent via the stable ID as dedup key end to end.
//
// Honesty notes:
//   - `attempts` counts claims (delivery attempts STARTED), not successes —
//     a crash after claim still burns an attempt; the ceiling therefore
//     bounds poison records even through process deaths.
//   - A claimed record without `markPublished` is redelivered after
//     `leaseExpiresAt`; consumers must dedup by `id` (envelope ULID).

export interface OutboxRecord {
  /** Stable domain/signal ID (ULID when emitted by a Signals emitter). */
  id: string;
  aggregateId: string;
  type: string;
  payload: unknown;
  occurredAt: string;
  published: boolean;
  publishedAt: string | null;
  /** Set while a worker holds the lease; null when free. */
  claimedAt: string | null;
  leaseExpiresAt: string | null;
  /** Number of times claimed (delivery attempts started). */
  attempts: number;
  /** Terminal after the attempts ceiling — excluded from claim(). */
  deadLettered: boolean;
}

/** What callers hand to append(); stores fill the lease/publication state. */
export interface OutboxAppend {
  /** Stable ID for idempotent appends and end-to-end dedup. */
  id?: string;
  aggregateId: string;
  type: string;
  payload: unknown;
  occurredAt: string;
}

export interface ClaimOptions {
  /** Lease duration in ms; claimed records are invisible until expiry. */
  leaseMs?: number;
}

export interface OutboxStore {
  /** Idempotent by `id`: re-appending an existing ID keeps the first record. */
  append(record: OutboxAppend): Promise<void>;
  /**
   * Atomically claim up to `limit` free records: unclaimed, expired-lease,
   * not published, not dead-lettered — oldest first. Increments `attempts`
   * and sets the lease. Postgres uses FOR UPDATE SKIP LOCKED.
   */
  claim(limit: number, options?: ClaimOptions): Promise<OutboxRecord[]>;
  /** Positive acknowledgement from downstream — the ONLY path to published. */
  markPublished(id: string): Promise<void>;
  /** Give the lease back for immediate retry (transient failure). */
  release(id: string): Promise<void>;
  /** Terminal after the attempts ceiling — never claimed again. */
  markDeadLettered(id: string): Promise<void>;
}
