// Statement-level fake for the B2 outbox path. It understands EXACTLY the
// SQL shapes PostgresOutboxStore / PostgresTransactionManager issue and
// simulates transaction semantics: writes inside BEGIN…COMMIT are buffered
// and applied atomically; ROLLBACK discards them. If the real SQL drifts,
// the shape assertions in the tests fail here first.
//
// Not a general Postgres: no joins, no concurrency, no types beyond the
// outbox statements. Anything unrecognized throws loudly.

import type { DbClient } from '@infra/database/postgres/client';

export interface FakeOutboxRow {
  id: string;
  aggregate_id: string;
  type: string;
  payload: unknown;
  occurred_at: string;
  published: boolean;
  published_at: string | null;
  claimed_at: string | null;
  lease_expires_at: string | null;
  attempts: number;
  dead_lettered: boolean;
}

type PendingWrite = () => void;

export class FakePgClient implements DbClient {
  private readonly applied = new Map<string, FakeOutboxRow>();
  private pending: PendingWrite[] | null = null;
  /** Every statement seen, in order — for SQL-shape assertions. */
  readonly log: Array<{ text: string; params: unknown[] }> = [];

  async query<T = unknown>(text: string, params: unknown[] = []): Promise<T[]> {
    this.log.push({ text, params });
    const sql = text.replace(/\s+/g, ' ').trim();

    if (sql === 'BEGIN') {
      if (this.pending) throw new Error('FakePgClient: nested BEGIN');
      this.pending = [];
      return [] as T[];
    }
    if (sql === 'COMMIT') {
      if (!this.pending) throw new Error('FakePgClient: COMMIT without BEGIN');
      const writes = this.pending;
      this.pending = null;
      for (const write of writes) write(); // atomic apply
      return [] as T[];
    }
    if (sql === 'ROLLBACK') {
      if (!this.pending) throw new Error('FakePgClient: ROLLBACK without BEGIN');
      this.pending = null; // discard everything buffered
      return [] as T[];
    }

    if (sql.startsWith('INSERT INTO outbox')) {
      const [id, aggregateId, type, payload, occurredAt] = params as [
        string,
        string,
        string,
        string,
        string,
      ];
      const row: FakeOutboxRow = {
        id,
        aggregate_id: aggregateId,
        type,
        payload: JSON.parse(payload),
        occurred_at: occurredAt,
        published: false,
        published_at: null,
        claimed_at: null,
        lease_expires_at: null,
        attempts: 0,
        dead_lettered: false,
      };
      this.runOrBuffer(() => {
        if (!this.applied.has(id)) this.applied.set(id, row); // ON CONFLICT DO NOTHING
      });
      return [] as T[];
    }

    if (sql.includes('FOR UPDATE SKIP LOCKED')) {
      if (this.pending) throw new Error('FakePgClient: claim() must not run inside a transaction');
      const [claimedAt, leaseExpiresAt, limit] = params as [string, string, number];
      const rows = [...this.applied.values()]
        .filter(
          (r) =>
            !r.published &&
            !r.dead_lettered &&
            (r.claimed_at === null || (r.lease_expires_at !== null && r.lease_expires_at <= claimedAt)),
        )
        .sort((a, b) => a.occurred_at.localeCompare(b.occurred_at))
        .slice(0, limit)
        .map((r) => {
          const next: FakeOutboxRow = {
            ...r,
            claimed_at: claimedAt,
            lease_expires_at: leaseExpiresAt,
            attempts: r.attempts + 1,
          };
          this.applied.set(r.id, next);
          return next;
        });
      return rows as unknown as T[];
    }

    if (sql.startsWith('UPDATE outbox')) {
      const [id, second] = params as [string, string | undefined];
      if (sql.includes('dead_lettered = true')) {
        this.runOrBuffer(() => this.patch(id, { dead_lettered: true, claimed_at: null, lease_expires_at: null }, { excludePublished: true }));
      } else if (sql.includes('published = true')) {
        this.runOrBuffer(() => this.patch(id, { published: true, published_at: second ?? null, claimed_at: null, lease_expires_at: null }));
      } else if (sql.includes('SET claimed_at = NULL')) {
        this.runOrBuffer(() => this.patch(id, { claimed_at: null, lease_expires_at: null }, { excludePublished: true }));
      } else {
        throw new Error(`FakePgClient: unrecognized UPDATE statement:\n${sql}`);
      }
      return [] as T[];
    }

    throw new Error(`FakePgClient: unrecognized statement:\n${sql}`);
  }

  /** Rows as committed — rolled-back writes never appear here. */
  outboxRows(): FakeOutboxRow[] {
    if (this.pending) throw new Error('FakePgClient: inspecting rows mid-transaction');
    return [...this.applied.values()];
  }

  statements(): string[] {
    return this.log.map((entry) => entry.text.replace(/\s+/g, ' ').trim());
  }

  private runOrBuffer(write: PendingWrite): void {
    if (this.pending) this.pending.push(write);
    else write();
  }

  private patch(
    id: string,
    changes: Partial<FakeOutboxRow>,
    opts: { excludePublished?: boolean } = {},
  ): void {
    const row = this.applied.get(id);
    if (!row) return;
    if (opts.excludePublished && row.published) return;
    this.applied.set(id, { ...row, ...changes });
  }
}
