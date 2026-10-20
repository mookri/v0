// Owner alerts (plan B5 evaluation criterion: "every nightly check has a
// green path, a red path, and an owner alert"). Green → no alert; red → a
// payload with owner, concrete diagnostics, and a runbook pointer. Delivery
// (webhook/Slack) is a driver-gated concern of the nightly runner script.

import type { CheckId, CheckResult } from './types';
import { CHECK_OWNERS } from './types';

export interface OwnerAlert {
  check: CheckId;
  owner: string;
  title: string;
  diagnostics: string[];
  runbook: string;
  checkedAt: string;
  severity: 'page';
}

export const CHECK_RUNBOOKS: Record<CheckId, string> = {
  missing_expected_domain_events:
    'Compare outbox emissions vs signals_raw for the window; check publisher logs and ingestion rejects (docs/analytics/signals-platform-plan.md §B5).',
  duplicate_event_ids:
    'Inspect insertId dedup on signals_raw.events; verify BigQuery insertAll retries were not double-inserted.',
  unpublished_beyond_sla:
    'Check outbox lease/attempts/dead_letter rows in Postgres; publisher loop down or poison record?',
  published_not_persisted:
    'Trace published IDs through ingestion (HMAC, 4xx/5xx, insert errors); replayable via publisher redelivery.',
  quarantined_records:
    'Read the reject reason per ID; a schema change or producer bug — fix the producer, do not loosen validation silently.',
  transaction_count_divergence:
    'Authoritative transactions (Postgres) vs mirrored events: find which side dropped counts; Level-1 report first.',
  volume_swing:
    'Compare against deploy/traffic calendars; instrumentation regression vs real traffic change (growth owns the call).',
  freshness:
    'Pipeline stale: publisher → Pub/Sub → ingestion → sink chain; check newest received_at in signals_raw.',
  query_cost:
    'Find the over-budget query in the run log; enforce partition filter / maximum_bytes_billed (warehouse lint gates this in CI).',
};

export function renderOwnerAlert(result: CheckResult): OwnerAlert | null {
  if (result.status === 'green') return null;
  return {
    check: result.id,
    owner: CHECK_OWNERS[result.id],
    title: `[signals] nightly check RED: ${result.id}`,
    diagnostics: result.diagnostics,
    runbook: CHECK_RUNBOOKS[result.id],
    checkedAt: result.checkedAt,
    severity: 'page',
  };
}
