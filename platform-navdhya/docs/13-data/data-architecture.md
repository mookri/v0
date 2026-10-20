# Data Architecture

**Status:** APPROVED · **Version:** 1.0.0 · **Date:** 2026-08-27
**Derived from:** doc 02 §8–10 + schema conventions (§9); doc 13 §2/§5; doc 14 §13; pasted plan

The data architecture register covers Navdhya's data conventions and lifecycle: identifiers, time, money, engine/charset/naming, enum/JSON/soft-delete rules, foreign-key policy, migrations, partitioning, retention, offboarding, archival, encryption-at-rest data policy, classification, read-models, backup/PITR, and export rules. Tenancy-specific isolation mechanics live in the tenancy register.

## Register index

| ID | Section | Summary |
|----|---------|---------|
| DATA-001 | [DATA-001](#data-001-identifier-conventions) | ULID CHAR(26) PKs; users BIGINT exception |
| DATA-002 | [DATA-002](#data-002-time--timezone-conventions) | UTC-only storage and writes |
| DATA-003 | [DATA-003](#data-003-money-conventions) | DECIMAL(18,4)+currency, bcmath Money VO |
| DATA-004 | [DATA-004](#data-004-engine-charset--naming-conventions) | InnoDB, utf8mb4, naming/index rules |
| DATA-005 | [DATA-005](#data-005-enum-json--soft-delete-conventions) | VARCHAR enums, metadata-only JSON, soft-delete scope |
| DATA-006 | [DATA-006](#data-006-foreign-key--cross-module-reference-policy) | FK only within a module; value columns across |
| DATA-007 | [DATA-007](#data-007-migration-ownership) | Central vs tenant migration locations |
| DATA-008 | [DATA-008](#data-008-partitioning-strategy) | RANGE partitioning for audit/movements/outbox |
| DATA-009 | [DATA-009](#data-009-retention-schedule-defaults) | Audit 7y, outbox 30d, idempotency 24h, soft-delete 90d |
| DATA-010 | [DATA-010](#data-010-offboarding-export-then-erasure) | Export archive, scheduled erasure |
| DATA-011 | [DATA-011](#data-011-archival) | Partition-drop + object-storage archives |
| DATA-012 | [DATA-012](#data-012-encryption-at-rest-data-policy) | Encrypted columns, per-tenant keys |
| DATA-013 | [DATA-013](#data-013-data-classification--sensitivity-classes) | Consolidated sensitivity classes |
| DATA-014 | [DATA-014](#data-014-pii-handling--cross-border-rules) | PII classification, cross-border posture |
| DATA-015 | [DATA-015](#data-015-seed--provisioning-data-rules) | Deterministic tenant seeds |
| DATA-016 | [DATA-016](#data-016-read-model--projection-rules) | Event-built read models (LD-10) |
| DATA-017 | [DATA-017](#data-017-backup--point-in-time-recovery) | Binlog PITR, RPO ≤ 5 min |
| DATA-018 | [DATA-018](#data-018-export--bulk-artifact-rules) | Tenant-scoped exports, re-checked URLs |
| DATA-019 | [DATA-019](#data-019-golden-datasets--test-data) | Versioned golden datasets |
| DATA-020 | [DATA-020](#data-020-identifiers-in-apis--events) | ULID string transport, no int leakage |
| DATA-021 | [DATA-021](#data-021-money-in-apis--events) | Minor-unit integers with currency |
| DATA-022 | [DATA-022](#data-022-temporal-validity--effective-dating) | valid_from/valid_until on grants & structure |
| DATA-023 | [DATA-023](#data-023-data-verification-requirements) | Standing data-integrity suites |

---

## DATA-001 Identifier conventions

- id: DATA-001
- status: ACTIVE
- owner: Platform Architect
- version: 1.0.0
- derived_from: doc 02 §9
- depends_on: CON-008
- affects: K-012

Primary keys are ULIDs stored as `CHAR(26)` string PKs for all kernel-tenant and domain tables. The single exception is `users`, which stays `BIGINT UNSIGNED AUTO_INCREMENT` for Passport compatibility. Cross-module identifiers travel as ULID value columns (never DB-level FKs). Identifiers are immutable once issued and are the substrate of ResourceReference (K-012).

## DATA-002 Time & timezone conventions

- id: DATA-002
- status: ACTIVE
- owner: Platform Architect
- version: 1.0.0
- derived_from: doc 02 §9
- depends_on: DATA-001
- affects: 14-domains

All timestamps are stored as `DATETIME` in UTC (`created_at`, `updated_at`); the application writes UTC exclusively. Display-layer conversion to tenant/user locale happens in the experience layer, never in storage. Effective-dated records (grants, delegations, hierarchy changes) carry explicit `valid_from`/`valid_until` timestamps.

## DATA-003 Money conventions

- id: DATA-003
- status: ACTIVE
- owner: Platform Architect
- version: 1.0.0
- derived_from: doc 02 §9
- depends_on: DATA-001
- affects: 14-domains/finance

Money amounts are stored as `DECIMAL(18,4)` paired with a `CHAR(3)` ISO-4217 currency column. All arithmetic goes through the bcmath-backed `Money` value object; floating point is banned for money. Minor-unit representation (e.g. `total_minor`) is used in event payloads and APIs. Mixed-currency arithmetic requires an explicit FX conversion through finance.

## DATA-004 Engine, charset & naming conventions

- id: DATA-004
- status: ACTIVE
- owner: Platform Architect
- version: 1.0.0
- derived_from: doc 02 §9
- depends_on: DATA-001
- affects: TEN-010

Engine is InnoDB with `utf8mb4` / `utf8mb4_0900_ai_ci`. Tables are snake_case plural and module-prefixed (`sales_orders`). Indexes are named `idx_<table>_<cols>`; uniques are `uq_<table>_<cols>`. Booleans are `TINYINT(1)` via `$table->boolean()`. Tenant-scoped unique constraints MUST include `tenant_id`. Full-text `FULLTEXT` indexes are created only on designated search columns (DATA-014).

## DATA-005 Enum, JSON & soft-delete conventions

- id: DATA-005
- status: ACTIVE
- owner: Platform Architect
- version: 1.0.0
- derived_from: doc 02 §9
- depends_on: DATA-004
- affects: 14-domains

Enums are `VARCHAR` columns backed by PHP enums; the MySQL `ENUM` type is banned. JSON columns are reserved for unstructured metadata (`meta JSON NULL`); anything queried, filtered, or reported on MUST be a real column. Soft deletes apply to aggregates and master data; they are prohibited on append-only tables (movements, journal lines, audit).

## DATA-006 Foreign-key & cross-module reference policy

- id: DATA-006
- status: ACTIVE
- owner: Platform Architect
- version: 1.0.0
- derived_from: doc 02 §9; doc 01 §5
- depends_on: DATA-001
- affects: DF-006

Database-level foreign keys are allowed only within one module's tables. Cross-module references are ULID value columns without DB-level FKs, consumed via ResourceReference (K-012) and the owning module's Contracts surface. This preserves carve-out safety (DF-012).

## DATA-007 Migration ownership

- id: DATA-007
- status: ACTIVE
- owner: Platform Architect
- version: 1.0.0
- derived_from: doc 02 §8.1
- depends_on: DF-001
- affects: DATA-008

### Migrations (ownership, execution, rules)

### 8.1 Ownership

- **Central migrations** live in `database/migrations/` (kernel tables only).
- **Tenant migrations** live in `app/Modules/<X>/Database/Migrations/` and are
  registered by the module provider against the tenant connection(s).

### 8.2 Execution model

- `php artisan migrate` → central connection only.
- `php artisan migrate:tenants` → applies pending tenant migrations to
  `tenant_shared` **and** every dedicated tenant DB; progress is tracked in a
  central `tenant_migrations` table (`migration, connection, tenant_id NULL,
  batch, applied_at`) where `tenant_id NULL` means `tenant_shared`.
- New dedicated databases are created from the current full tenant schema
  (`tenant:provision-db --tenant=<id>` dumps + replays the schema, then
  records all migrations as applied).

### 8.3 Migration rules

1. Tenant migrations MUST be connection-agnostic (no hard-coded connection).
2. A migration MAY only create/alter tables owned by its module ([domain framework §7](../19-testing/testing-strategy.md)
   architecture test).
3. Destructive operations (drop column/table) require a two-step
   expand→contract release (additive first, removal after all app nodes run
   the new code) — mandatory for zero-downtime deploys.
4. Large-table backfills MUST be batched jobs, never inline in migrations.
5. Rollbacks: forward-fix preferred; every migration still ships a working
   `down()` for dev use.

---

## DATA-008 Partitioning strategy

- id: DATA-008
- status: ACTIVE
- owner: Platform Architect
- version: 1.0.0
- derived_from: doc 02 §9, §10
- depends_on: DATA-004
- affects: DATA-010

`audit_logs`, `inventory_movements`, and `outbox` (archive) are RANGE-partitioned by month once volume thresholds are crossed. Partition drops implement retention cheaply and atomically. Partition decisions are operational (driven by observed volume), recorded in the SRE runbooks.

## DATA-009 Retention schedule defaults

- id: DATA-009
- status: ACTIVE
- owner: Platform Architect
- version: 1.0.0
- derived_from: doc 02 §10
- depends_on: DATA-008
- affects: SEC-018

Retention defaults (tenant setting, overridable per plan/law): audit logs 7 years (compliance); outbox 30 days post-relay; notification log 90 days; idempotency keys 24 h; soft-deleted aggregates 90 days then purge job. Retention is enforced by scheduled purge jobs and partition drops; every purge is audited.

## DATA-010 Offboarding (export then erasure)

- id: DATA-010
- status: ACTIVE
- owner: Platform Architect
- version: 1.0.0
- derived_from: doc 02 §10; doc 13 §5.3
- depends_on: DATA-009, SEC-018
- affects: 22-runbooks

`platform.tenant.offboarded` triggers export (a machine-readable archive to the tenant's file area) followed by scheduled erasure per the GDPR-style export/erasure hooks in the security baseline. Erasure is complete, audited, and irreversible; the offboarding procedure is captured in the runbooks.

## DATA-011 Archival

- id: DATA-011
- status: ACTIVE
- owner: Platform Architect
- version: 1.0.0
- derived_from: doc 02 §10
- depends_on: DATA-008
- affects: 20-sre

After the retention window, append-only tables are archived via partition-drop; archives land in object storage with per-tenant keys. Archives are indexed by tenant, table, and window so a restore request can locate them. Restore is a runbook operation with audit.

## DATA-012 Encryption-at-rest data policy

- id: DATA-012
- status: ACTIVE
- owner: Platform Architect
- version: 1.0.0
- derived_from: doc 13 §2
- depends_on: SEC-016
- affects: SEC-018

The encryption baseline (in transit and at rest, key management) is owned by the security baseline (SEC-016). The data-layer rule: fields classified secret/credential (connector secrets, tokens, webhook secrets) use encrypted columns with per-tenant key material; backup media are encrypted; and no plaintext secret may appear in logs or exports.

## DATA-013 Data classification & sensitivity classes

- id: DATA-013
- status: ACTIVE
- owner: Platform Architect
- version: 1.0.0
- derived_from: doc 13 §5.1; doc 10 §7.5; doc 17 §14.5
- depends_on: SEC-018
- affects: EXP-003, 17-ai

Data is classified into sensitivity classes (public, internal, confidential, restricted) that drive field-masking, encryption decisions, and AI-surface exposure. The sensitivity classes declared by Phase 4 (§7.5) and Phase 4a (§14.5) are consolidated here. Restricted data is excluded from AI prompts and analytics exports by default; field-level ACL is noted as a v2 seam.

## DATA-014 PII handling & cross-border rules

- id: DATA-014
- status: ACTIVE
- owner: Platform Architect
- version: 1.0.0
- derived_from: doc 13 §5.4
- depends_on: DATA-013
- affects: SEC-018

Personal data is classified per DATA-013 and handled per the security baseline privacy rules: export and erasure hooks (GDPR-style), retention bounds, and access audit. Cross-border data movement is constrained to declared regions in v1 (single-region deployment); any multi-region expansion re-visits this rule (post-v1 path in HA/DR).

## DATA-015 Seed & provisioning data rules

- id: DATA-015
- status: ACTIVE
- owner: Platform Architect
- version: 1.0.0
- derived_from: CTRL-010; doc 02 §3
- depends_on: CTRL-010
- affects: 22-runbooks/provisioning

Tenant provisioning seeds are deterministic and idempotent: default roles, chart-of-accounts template, numbering schemes, and kernel settings. Seeds are versioned per module; re-running a seed for an already-seeded tenant is a no-op. Seed content is platform data, never tenant business data.

## DATA-016 Read-model & projection rules

- id: DATA-016
- status: ACTIVE
- owner: Platform Architect
- version: 1.0.0
- derived_from: LD-10; doc 17 §1.8; doc 16 §2.3
- depends_on: EV-008
- affects: 14-domains/crm/crm

Cross-module read needs are satisfied by read-models rebuilt from domain events and contract lookups (LD-10), never by cross-module joins. Projections (market prices, customer 360, pipeline forecasts) declare their source events, refresh semantics, and staleness contract. A projection that cannot be rebuilt from events alone is a design violation.

## DATA-017 Backup & point-in-time recovery

- id: DATA-017
- status: ACTIVE
- owner: Platform Architect
- version: 1.0.0
- derived_from: doc 00 §6.1; doc 13 §8.2
- depends_on: DATA-012
- affects: 20-sre/ha-dr

Backups combine physical snapshots with binlog-based point-in-time recovery to meet RPO ≤ 5 min. Backup scope covers the central DB and every tenant data connection (shared + dedicated). Restore drills are scheduled and their results recorded as evidence. Backup media are encrypted (DATA-012).

## DATA-018 Export & bulk artifact rules

- id: DATA-018
- status: ACTIVE
- owner: Platform Architect
- version: 1.0.0
- derived_from: DELTA-010; doc 16 §14
- depends_on: DELTA-010
- affects: 07-kernel/data-tools

Export artifacts are bound to exactly one tenant, stored in the tenant's file namespace, and their download URLs re-check tenant + permission at access time. Bulk exports of sensitive classes are gated and audited. No export may aggregate across tenants. The job mechanics live in the data-tools kernel file.

## DATA-019 Golden datasets & test data

- id: DATA-019
- status: ACTIVE
- owner: Platform Architect
- version: 1.0.0
- derived_from: doc 14 §13
- depends_on: DATA-009
- affects: 19-testing

Test environments use versioned golden datasets with diff notes. Fixtures never contain production or real PII data. Golden datasets cover the isolation matrix (two-tenant seeds), authorization matrix, and per-module happy paths. Maintenance burden is a tracked risk (ARCH-019).

## DATA-020 Identifiers in APIs & events

- id: DATA-020
- status: ACTIVE
- owner: Platform Architect
- version: 1.0.0
- derived_from: doc 06 §3; doc 05 §2
- depends_on: DATA-001
- affects: K-021

APIs and event payloads transport identifiers as ULID strings. Auto-increment integers (including the users PK exception) are never exposed on public surfaces; public tokens use ULID or opaque handles. This keeps enumeration resistance and carve-out portability.

## DATA-021 Money in APIs & events

- id: DATA-021
- status: ACTIVE
- owner: Platform Architect
- version: 1.0.0
- derived_from: doc 02 §9; doc 05 §2.1
- depends_on: DATA-003
- affects: K-021

APIs and event payloads represent money as minor-unit integers (`total_minor`) with an explicit currency code, never as floats. Server-side storage remains DECIMAL(18,4)+currency; the conversion happens at the serialization boundary via the Money value object.

## DATA-022 Temporal validity & effective dating

- id: DATA-022
- status: ACTIVE
- owner: Platform Architect
- version: 1.0.0
- derived_from: HIER-013; SEC-023; doc 09 §1.5
- depends_on: DATA-002
- affects: HIER-013

Records whose truth changes over time (authority grants, delegations, hierarchy structure, FX rates, price lists) carry `valid_from`/`valid_until` and are evaluated at decision time, not at write time. Effective dating is the mechanism behind grant expiry, delegation windows, and hierarchy versioning.

## DATA-023 Data verification requirements

- id: DATA-023
- status: ACTIVE
- owner: Platform Architect
- version: 1.0.0
- derived_from: doc 02 §11; doc 14
- depends_on: DATA-001
- affects: 19-testing

Standing data suites: (a) tenant isolation matrix (TEN-018); (b) migration idempotency (double-apply is a no-op); (c) money arithmetic golden tests (bcmath Money VO); (d) retention purge correctness (nothing purged inside retention); (e) export/erasure completeness. Each maps to a named test in the testing strategy.
