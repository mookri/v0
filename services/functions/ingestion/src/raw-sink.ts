// signals_raw sink (plan B2, item 5): streaming insert with the envelope ID
// as BigQuery insertId (best-effort dedup → exactly-once rows under
// at-least-once delivery) and an ENFORCED `source` column —
// first_party | server | shadow | ga4_export never blur into each other.

import type { SignalEnvelope } from '@navdhya/analytics/src/contracts/envelope';
import { SIGNAL_SOURCES, type SignalSource } from '@navdhya/analytics/src/contracts/signal-source';

// Single definition lives in @navdhya/analytics (shared with the B3
// collector, which stamps `shadow` while comparing); re-exported here so
// existing imports keep working.
export { SIGNAL_SOURCES, isSignalSource } from '@navdhya/analytics/src/contracts/signal-source';
export type { SignalSource } from '@navdhya/analytics/src/contracts/signal-source';

export interface RawSignalRow {
  /** Dedup key: the envelope ULID. Same ID twice → one row. */
  insertId: string;
  json: Record<string, unknown>;
}

export interface RawSink {
  put(rows: RawSignalRow[]): Promise<void>;
}

export function toRawRow(
  envelope: SignalEnvelope,
  opts: { source: SignalSource; receivedAt: string },
): RawSignalRow {
  if (!SIGNAL_SOURCES.includes(opts.source)) {
    throw new Error(`signals_raw: invalid source "${String(opts.source)}"`);
  }
  return {
    insertId: envelope.id,
    json: {
      id: envelope.id,
      name: envelope.name,
      version: envelope.version,
      occurred_at: envelope.occurredAt,
      // Date-partition key for signals_raw (plan B5): derived from the
      // event's own timestamp so streaming inserts land in the right
      // `event_date` partition with no server-side computation.
      event_date: envelope.occurredAt.slice(0, 10),
      received_at: opts.receivedAt,
      surface: envelope.surface,
      app_version: envelope.appVersion,
      session_id: envelope.session.id,
      session_seq: envelope.session.seq,
      uid_hash: envelope.actor.uidHash,
      anon_id: envelope.actor.anonId,
      consent_analytics: envelope.consent.analytics,
      consent_ads: envelope.consent.ads,
      route_template: envelope.context.routeTemplate,
      experiment_exposure_id: envelope.context.experimentExposureId ?? null,
      utm_json: envelope.context.utm !== undefined ? JSON.stringify(envelope.context.utm) : null,
      params_json: JSON.stringify(envelope.params),
      breadcrumbs_json:
        envelope.breadcrumbs !== undefined ? JSON.stringify(envelope.breadcrumbs) : null,
      source: opts.source,
    },
  };
}

/** Reference sink with insertId dedup — the contract tests bind to this. */
export class MemoryRawSink implements RawSink {
  private readonly rows = new Map<string, RawSignalRow>();
  putCalls = 0;

  async put(rows: RawSignalRow[]): Promise<void> {
    this.putCalls += 1;
    for (const row of rows) {
      if (!this.rows.has(row.insertId)) this.rows.set(row.insertId, row);
    }
  }

  all(): RawSignalRow[] {
    return [...this.rows.values()];
  }

  size(): number {
    return this.rows.size;
  }
}

export interface BigQuerySinkConfig {
  projectId: string;
  dataset: string;
  table: string;
  accessToken: () => Promise<string>;
  fetchFn?: typeof fetch;
}

export class BigQueryRawSink implements RawSink {
  private readonly fetchFn: typeof fetch;

  constructor(private readonly config: BigQuerySinkConfig) {
    if (!config.projectId || !config.dataset || !config.table) {
      throw new Error('BQ_TARGET_MISSING');
    }
    this.fetchFn = config.fetchFn ?? fetch;
  }

  async put(rows: RawSignalRow[]): Promise<void> {
    if (rows.length === 0) return;
    for (const row of rows) {
      if (!SIGNAL_SOURCES.includes(row.json.source as SignalSource)) {
        throw new Error(`signals_raw: invalid source "${String(row.json.source)}"`);
      }
    }
    const token = await this.config.accessToken();
    const url =
      `https://bigquery.googleapis.com/bigquery/v2/projects/${this.config.projectId}` +
      `/datasets/${this.config.dataset}/tables/${this.config.table}/insertAll`;
    const res = await this.fetchFn(url, {
      method: 'POST',
      headers: { Authorization: `Bearer ${token}`, 'Content-Type': 'application/json' },
      body: JSON.stringify({ rows: rows.map((r) => ({ insertId: r.insertId, json: r.json })) }),
    });
    if (!res.ok) throw new Error(`BQ_INSERT_FAILED:${res.status}`);
    const body = (await res.json()) as { insertErrors?: unknown[] };
    if (Array.isArray(body.insertErrors) && body.insertErrors.length > 0) {
      // Partial failure → caller retries the batch; insertId dedup makes the
      // retry safe (no doubled rows). Never ack a partially-persisted batch.
      throw new Error(`BQ_INSERT_ERRORS:${body.insertErrors.length}`);
    }
  }
}
