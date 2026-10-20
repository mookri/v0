# Runbook — Tenant Promotion (shared → dedicated)

**Status:** ACTIVE · **Version:** 1.1.0 · **Date:** 2026-08-27
**Derived from:** doc 02 §7; tenancy register TEN-013/014

Moves a tenant from row-level shared storage to a dedicated database with a checksummed backfill, a single-transaction cutover, and a 14-day rollback window. Register entries: [TEN-013](../06-tenancy/tenancy-and-data.md#ten-013-tenant-promotion-shared--dedicated), [TEN-014](../06-tenancy/tenancy-and-data.md#ten-014-demotion--rollback-window). The end-to-end promotion flow is covered by the tenant isolation suite in CI against a second MySQL database ([testing strategy](../19-testing/testing-strategy.md)).

---

## Promotion runbook

**Preconditions:** tenant active; target MySQL instance provisioned with the
same schema version as `tenant_shared`; maintenance window scheduled.

1. **Freeze** — set tenant status `promoting` (writes rejected with 503 +
   `Retry-After`; reads continue from shared DB).
2. **Backfill** — kernel command `tenant:promote --tenant=<id>` copies the
   tenant's rows table-by-table in chunks (5k rows), checksumming each chunk
   (row count + CRC32 aggregate) into the dedicated DB.
3. **Verify** — per-table checksum comparison; any mismatch aborts (tenant
   returns to `active`, no cutover).
4. **Cutover** — single central transaction: set `db_mode=dedicated`,
   `db_connection_name`, store encrypted config, bump registry cache, emit
   `platform.tenant.promoted`.
5. **Smoke** — automated read/write smoke suite against the tenant; on
   failure, flip `db_mode` back (rows remain authoritative in shared DB until
   step 6).
6. **Rollback window** — shared-DB rows are retained for **14 days**;
   `tenant:demote --tenant=<id> [--from-backup]` reverses the cutover. After
   the window, `tenant:purge-shared --tenant=<id>` deletes the tenant's
   shared rows (audited).

Demotion (dedicated → shared) uses the same runbook in reverse with an
exclusive maintenance window.

---