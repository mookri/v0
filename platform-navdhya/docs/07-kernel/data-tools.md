# Data Tools (Import/Export & Bulk Operations)

**Status:** ACTIVE · **Version:** 1.1.0 · **Date:** 2026-08-27
**Derived from:** doc 16 §14 (planning LD-13: one kernel-backed job surface)

Data tools are the single shared kernel surface for CSV/XLSX import, export, and bulk operations, used by every module (planning LD-13). The job infrastructure sits on kernel Jobs + Files + idempotency; per-module importers declare column mappings declaratively. No module ships its own ad-hoc import/export mechanics. Tenant-scoping and export-artifact rules come from the data architecture register (DATA-018, DELTA-010).


---

## Import/Export & Bulk Operations (shared kernel surface)

### 14.1 Architecture (normative; planning [LD-13](../21-adrs/ADR-0014-single-import-export-surface.md))

One kernel-backed job surface serves every module:

```text
import/export job (module-declared mapping)
  → queued worker (kernel Jobs)
  → progress rows + result file (kernel Files)
  → completion notification + event
```

Entities (module code `data_tools`, kernel-adjacent but tenant-scoped):

| Table | Key fields |
|-------|-----------|
| `data_jobs` | id, kind (`import|export|bulk_update`), module_code, resource, status (`queued|running|completed|failed|cancelled`), file_id (input), result_file_id, params JSON, created_by, totals JSON (processed/ok/failed), idempotency_key |
| `data_job_errors` | job_id, row_number, code, detail |

Rules:
1. **Declarative mappings**: each module registers importers/exporters
   (resource, columns, validators, row handler) in its provider —
   no ad-hoc parsers.
2. **Idempotency**: imports carry client keys; re-uploading the same file
   + key replays the stored result ([API platform §5](./api-platform.md) pattern).
3. **Atomicity**: per-row (failures collected, job completes with error
   report) unless the importer declares all-or-nothing.
4. **Limits**: 100k rows/job (plan-scaled), 50 MB files; oversized ⇒
   422 `data_tools_limit_exceeded`.
5. **Authorization**: job creation requires the target resource's
   `create` (import) / `export` (export) permission; result files are
   tenant-scoped signed URLs ([kernel services §7](./kernel-services.md)).
6. **Bulk mutations**: the [API platform §2.2](./api-platform.md) `:bulk` endpoint family is the
   synchronous path (≤100 items); `data_tools` is the asynchronous path.

### 14.2 v1 importers/exporters

| Module | Import | Export |
|--------|--------|--------|
| catalog | products+variants (CSV) | products |
| customers | customers (CSV) | customers, orders (SH-056) |
| pricing | price list entries | price lists |
| markets | duty rates | market prices |
| content | translations (CSV/XLSX) | translations |
| inventory | stock levels (CSV adjust) | stock levels, movements |

### 14.3 API & permissions

`POST /api/v1/data-tools/imports|exports`, `GET /data-tools/jobs/{id}`,
`GET .../result` under `data_tools:job:view|create|cancel` (create maps
to the resource permission per rule 5). Scopes: `data_tools:read|write`.
Events: `data_tools.job.completed` / `.failed`.

---

## Phase summary inputs (Phase 2a)

| Event family | Order of magnitude (mid tenant, month) |
|--------------|----------------------------------------|
| data_tools.* | 10¹ |

| Workflow machine | Module | Sensitive transitions |
|------------------|--------|----------------------|
| `data_job` | data_tools | cancel (operational) |

## Verification requirements

1. **Import/export**: 100k-row import completes with per-row error
    report; replay with same key returns stored result; export files are
    tenant-scoped signed URLs; oversized upload ⇒ 422.
2. **Tenant isolation**: job, error, and result-file rows are covered by the `AssertTenantIsolation` matrix; export artifacts are tenant-scoped signed URLs — see the tenancy register ([tenancy & data](../06-tenancy/tenancy-and-data.md)).
3. **Contract tests**: `data_tools:job:view|create|cancel` appears on the job routes and OpenAPI fragments validate — see the [testing strategy](../19-testing/testing-strategy.md).
