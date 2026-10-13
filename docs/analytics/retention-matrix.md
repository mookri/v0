# Retention matrix (v1.1.0)

Canonical machine-readable source: `packages/analytics/src/privacy/retention-matrix.ts`
(`RETENTION_MATRIX`, `RETENTION_CLASS_DAYS`, `retentionDaysForClass`). This
document is the reviewable rendering; the code is the executable one, and a
unit test pins that every registry `retentionClass` and every required domain
resolves there. Effective from 2026-10-13 (plan B7); v1.1.0 adds the
`crash_events` domain with the B8 crash subsystem.

**"DPDP-ready" is a legal-review outcome, not an engineering label.** The
durations below are engineering defaults a reviewer can change in one place;
the legal workstream owns the final values.

## Domains

| Domain | Retention | Authority | Enforcement (where deletion actually happens) |
| --- | --- | --- | --- |
| private_collection_stream | class-driven (`raw_30d`=30d, `raw_90d`=90d, `audit_long`=400d) | registry `retentionClass` per event | `signals_raw.events` partition expiration (400d ceiling) + per-class pruning jobs |
| ga4_data | 400d | GA4 console user-data retention setting (max 14 months on GA4) | GA4 platform + GA4 user-deletion API for subject requests |
| exported_datasets | 400d | retention matrix v1.0.0 | `signals_ga4.events_typed` view window + raw partition expiration |
| event_audit_records | 400d | `audit_long` class | raw partition expiration |
| experiment_data | 400d | experiment ledger (B8) | ledger storage TTL |
| messaging_identifiers | 90d | FCM/audience adapter owner | audience sync prunes idle tokens; deletion/withdrawal removes immediately |
| consent_records | 1095d | legal review (engineering default 3y) | consent ledger TTL — consent proof must outlive the events it governed |
| erasure_tokens | **indefinite** | privacy engineering | never pruned — deleting a token would resurrect the subject on the next projection rebuild |
| deletion_audit_records | 1095d | legal review (engineering default 3y) | `deletion_audit` TTL — the proof per system per request |
| crash_events (v1.1.0) | 180d | crash subsystem owners | `signals_crash.errors`/`sessions` partition expiration — restricted dataset, web instrumentation only |

## How erasure interacts with retention

- **Account deletion** erases everywhere immediately (30-day SLA, `DeletionWorkflow`),
  independent of the durations above. Token scope: `full`.
- **Consent withdrawal** stops fan-out immediately (ledger, SLO 15 minutes)
  and revokes previously granted destinations (GA4 deletion request, FCM/
  audience removal), but does NOT erase raw data — it ages out by this
  matrix. Token scope: `fanout_only` (upgrades to `full` on later deletion,
  never downgrades).
- **`signals_stg` / `signals_mart` are views** in this warehouse: their rows
  physically live in `signals_raw`, so raw hard-deletion plus re-projection
  removes subject rows. A soft-delete view alone is never the erasure
  mechanism — `signals_raw.events`/`quarantined` receive real `DELETE`s
  (see `tooling/scripts/privacy-erasure.ts`).
- **Erasure-aware access**: read paths that could surface a subject consult
  the erasure-token table (`filterErased`/`excludeErased`); queries over
  erased hashes return nothing.

## Key rotation (HMAC peppers)

`PepperRing` (`packages/analytics/src/privacy/pepper-rotation.ts`):
identifiers are `v{keyVersion}:{base64url(hmac)}`. Rotation activates the new
pepper and puts the previous one on a bounded **overlap**: within it, old
identifiers resolve (continuity — and the migration budget for re-minting
stored mappings); after expiry they are unresolvable by design. Unknown key
versions and digest mismatches always fail closed. Peppers are loaded from
KMS/env outside the package; exactly one pepper may be active.

## Operational constants

| Constant | Value | Where |
| --- | --- | --- |
| Deletion SLA | 30 days | `DELETION_SLA_DAYS` (deletion-workflow.ts) |
| Withdrawal fan-out block SLO | 15 minutes | `WITHDRAWAL_BLOCK_SLO_MS` (consent-ledger.ts) |
| BigQuery erasure cost cap | `SIGNALS_BQ_MAX_BYTES_BILLED` (default 10 GiB) | privacy-erasure CLI |

Schema: `infrastructure/database/postgres/migrations/2026_10_14_000001_create_privacy_tables.sql`
(erasure_tokens, consent_withdrawals, deletion_audit).
CLI: `npx tsx tooling/scripts/privacy-erasure.ts --request request.json [--live]`
(dry-run default; `--live` fail-closes on any missing credential).
