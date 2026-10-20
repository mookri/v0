import type {
  ClaimOptions,
  OutboxAppend,
  OutboxRecord,
  OutboxStore,
} from '@navdhya/contracts/src/messaging/outbox';

const DEFAULT_LEASE_MS = 30_000;

// In-memory OutboxStore with the FULL lease contract (plan B2). This is the
// reference implementation the contract tests bind to — PostgresOutboxStore
// must be observationally equivalent for claim/publish/release/dead-letter.
// Injectable clock so lease-expiry redelivery is deterministic in tests.
export class MemoryOutboxStore implements OutboxStore {
  private readonly records = new Map<string, OutboxRecord>();
  private seq = 0;

  constructor(private readonly now: () => number = Date.now) {}

  async append(record: OutboxAppend): Promise<void> {
    if (record.id !== undefined && this.records.has(record.id)) {
      return; // idempotent by stable ID — first record wins
    }
    const id = record.id ?? `outbox_${++this.seq}`;
    this.records.set(id, {
      aggregateId: record.aggregateId,
      type: record.type,
      payload: record.payload,
      occurredAt: record.occurredAt,
      id,
      published: false,
      publishedAt: null,
      claimedAt: null,
      leaseExpiresAt: null,
      attempts: 0,
      deadLettered: false,
    });
  }

  async claim(limit: number, options?: ClaimOptions): Promise<OutboxRecord[]> {
    const nowMs = this.now();
    const leaseMs = options?.leaseMs ?? DEFAULT_LEASE_MS;
    const claimed = [...this.records.values()]
      .filter(
        (r) =>
          !r.published &&
          !r.deadLettered &&
          (r.claimedAt === null ||
            (r.leaseExpiresAt !== null && Date.parse(r.leaseExpiresAt) <= nowMs)),
      )
      .sort((a, b) => a.occurredAt.localeCompare(b.occurredAt))
      .slice(0, limit);
    return claimed.map((r) => {
      const next: OutboxRecord = {
        ...r,
        claimedAt: new Date(nowMs).toISOString(),
        leaseExpiresAt: new Date(nowMs + leaseMs).toISOString(),
        attempts: r.attempts + 1,
      };
      this.records.set(r.id, next);
      return next;
    });
  }

  async markPublished(id: string): Promise<void> {
    const rec = this.records.get(id);
    if (!rec) return;
    this.records.set(id, {
      ...rec,
      published: true,
      publishedAt: new Date(this.now()).toISOString(),
      claimedAt: null,
      leaseExpiresAt: null,
    });
  }

  async release(id: string): Promise<void> {
    const rec = this.records.get(id);
    if (!rec || rec.published) return;
    this.records.set(id, { ...rec, claimedAt: null, leaseExpiresAt: null });
  }

  async markDeadLettered(id: string): Promise<void> {
    const rec = this.records.get(id);
    if (!rec || rec.published) return;
    this.records.set(id, { ...rec, deadLettered: true, claimedAt: null, leaseExpiresAt: null });
  }

  /** Test/inspection helper — not part of the OutboxStore contract. */
  snapshot(): OutboxRecord[] {
    return [...this.records.values()];
  }
}
