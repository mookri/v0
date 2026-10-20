import type {
  ClaimOptions,
  OutboxAppend,
  OutboxRecord,
  OutboxStore,
} from '@navdhya/contracts/src/messaging/outbox';
import type { DbClient } from '@infra/database/postgres/client';

const DEFAULT_LEASE_MS = 30_000;

interface OutboxRow {
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

function mapRow(row: OutboxRow): OutboxRecord {
  let payload = row.payload;
  if (typeof payload === 'string') {
    try {
      payload = JSON.parse(payload);
    } catch {
      // leave as-is; malformed payload surfaces at the publisher
    }
  }
  return {
    id: row.id,
    aggregateId: row.aggregate_id,
    type: row.type,
    payload,
    occurredAt: row.occurred_at,
    published: row.published,
    publishedAt: row.published_at,
    claimedAt: row.claimed_at,
    leaseExpiresAt: row.lease_expires_at,
    attempts: row.attempts,
    deadLettered: row.dead_lettered,
  };
}

function fallbackId(): string {
  return `outbox_${Date.now().toString(36)}_${Math.random().toString(36).slice(2, 12)}`;
}

// Postgres OutboxStore (plan B2). Atomicity comes from running on the SAME
// connection/transaction as the business write: when constructed over a
// tx-scoped DbClient (see PostgresTransactionManager), INSERT + business row
// commit or roll back together. claim() uses FOR UPDATE SKIP LOCKED so
// concurrent workers never steal each other's leases.
export class PostgresOutboxStore implements OutboxStore {
  constructor(
    private readonly db: DbClient,
    private readonly now: () => number = Date.now,
  ) {}

  async append(record: OutboxAppend): Promise<void> {
    const id = record.id ?? fallbackId();
    await this.db.query(
      `INSERT INTO outbox (id, aggregate_id, type, payload, occurred_at, published, attempts, dead_lettered)
       VALUES ($1, $2, $3, $4, $5, false, 0, false)
       ON CONFLICT (id) DO NOTHING`,
      [id, record.aggregateId, record.type, JSON.stringify(record.payload), record.occurredAt],
    );
  }

  async claim(limit: number, options?: ClaimOptions): Promise<OutboxRecord[]> {
    const nowMs = this.now();
    const leaseMs = options?.leaseMs ?? DEFAULT_LEASE_MS;
    const rows = await this.db.query<OutboxRow>(
      `UPDATE outbox
       SET claimed_at = $1, lease_expires_at = $2, attempts = attempts + 1
       WHERE id IN (
         SELECT id FROM outbox
         WHERE NOT published
           AND NOT dead_lettered
           AND (claimed_at IS NULL OR lease_expires_at <= $1)
         ORDER BY occurred_at ASC
         LIMIT $3
         FOR UPDATE SKIP LOCKED
       )
       RETURNING id, aggregate_id, type, payload, occurred_at, published,
                 published_at, claimed_at, lease_expires_at, attempts, dead_lettered`,
      [new Date(nowMs).toISOString(), new Date(nowMs + leaseMs).toISOString(), limit],
    );
    return rows.map(mapRow);
  }

  async markPublished(id: string): Promise<void> {
    await this.db.query(
      `UPDATE outbox
       SET published = true, published_at = $2, claimed_at = NULL, lease_expires_at = NULL
       WHERE id = $1`,
      [id, new Date(this.now()).toISOString()],
    );
  }

  async release(id: string): Promise<void> {
    await this.db.query(
      `UPDATE outbox SET claimed_at = NULL, lease_expires_at = NULL
       WHERE id = $1 AND NOT published`,
      [id],
    );
  }

  async markDeadLettered(id: string): Promise<void> {
    await this.db.query(
      `UPDATE outbox
       SET dead_lettered = true, claimed_at = NULL, lease_expires_at = NULL
       WHERE id = $1 AND NOT published`,
      [id],
    );
  }
}
