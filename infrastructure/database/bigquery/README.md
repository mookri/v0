# Signals warehouse (BigQuery) — plan B5

Version-controlled SQL for the Navdhya Signals warehouse: dataset DDL,
staging/mart models, data-quality tests, and the recorded table-size
manifest behind the CI cost gate. **No warehouse credential lives in this
repo** — every `PROJECT` placeholder is replaced at apply time (same posture
as `DATABASE_URL`).

## Layout

| Path | Contents |
| --- | --- |
| `ddl/0001_signals_datasets.sql` | `signals_raw` / `signals_stg` / `signals_mart` / `signals_reconciliation` schemas + partitioned/clustered tables |
| `ddl/0002_ga4_export.sql` | Our side of the GA4 export contract (Google manages `analytics_<PROPERTY_ID>` — link created in the GA4 console) |
| `models/stg/` | Typed views: `events` (truth), `ga4_events` (export) |
| `models/mart/` | funnels, retention, ltv, attribution, experiment_readouts, data_quality, operational_metrics |
| `tests/` | Data-quality tests — **any returned row is a failure** |
| `table-sizes.json` | Recorded sizes for the static cost gate (driver-gated refresh) |

## Rules (enforced, not aspirational)

- **Partition filter mandatory** in every model/test: `event_date` range,
  `_TABLE_SUFFIX`, `_PARTITIONDATE`, or `@since`/`@until`. CI gate:
  `npx tsx tooling/scripts/warehouse-lint.ts` — fails on a missing filter,
  on `SELECT *` without one, and on an estimated over-budget scan
  (`budgetBytes` default 5 GiB, per-file override `-- @budget_bytes:N`).
- The cost estimate is a **static heuristic** over `table-sizes.json`.
  Production billing protection is BigQuery `maximum_bytes_billed` on every
  job submission — the lint is an early tripwire, not the bill gate.
- Retention baseline: `raw_30d` = 30 days, `raw_90d` = 90 days,
  `audit_long` = 400 days (registry `retentionClass` per event). The events
  table carries the 400-day partition ceiling; per-class pruning jobs are
  versioned with the B7 retention matrix.

## Scheduling (console / driver-gated)

1. Apply `ddl/*.sql` with `bq` (replace `PROJECT`).
2. Schedule `models/**` as BigQuery scheduled queries with `@since`/`@until`
   bound to the run window (daily for marts, hourly for ops metrics).
3. Schedule `tests/**` nightly; a non-empty result files an owner alert
   (nightly runner: `tooling/scripts/warehouse-nightly.ts`).
4. Refresh `table-sizes.json` with a `bq --dry_run` of a full scan per
   table when sizes materially change.

## Nightly checks & data trust

`tooling/scripts/warehouse-nightly.ts` runs the nine checks
(`packages/analytics/src/server/checks/`) over exported JSON and prints the
data-trust statement — "specified checks passed at T", never an export-
completeness claim. Level-2 GA4 parity:
`packages/analytics/src/server/reconciliation/level2.ts`.
