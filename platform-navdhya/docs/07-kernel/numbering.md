# Document Numbering Registry

**Status:** ACTIVE · **Version:** 1.1.0 · **Date:** 2026-08-27
**Derived from:** doc 17 §13.3 (extends invoicing numbering schemes, doc 07 §9.6, to all documents)

The numbering registry generalizes the invoicing numbering schemes ([invoicing §9.6](../14-domains/finance/invoicing.md)) to every numbered document type in the platform (orders, invoices, POs, journals, etc.). Numbering schemes are tenant-configurable per document type, gap-tolerant per scheme policy, and audited. The registry is a kernel-backed shared surface so every module resolves numbers through one mechanism.


---

## Document numbering registry

1. The invoicing numbering-scheme pattern ([commercial core domains §9.6](../14-domains/finance/invoicing.md)) is generalized
   into a kernel-adjacent registry consumed by **all** document types:
   `document_numbering_schemes`: `doc_type` (UQ per tenant;
   `quote|sales_order|draft_order|purchase_order|requisition|grn|
   invoice|credit_note|bill|debit_note|journal_entry|transfer|
   adjustment|rma|work_order|expense_report|contract|ncr|capa`),
   `prefix`, `sequence_scope` (`tenant|fiscal_year|calendar_year`),
   `padding`, `current_value`, `gapless` (bool, default true),
   `effective_from`, `status`.
2. Allocation contract `NumberAllocator.next(doc_type, context)`:
   atomic row-locked increment; gapless schemes MUST NOT skip values
   (failed allocations roll back with the enclosing transaction);
   non-gapless schemes audit skipped ranges.
3. Scheme changes are effective-dated and audited; numbers already
   issued are never renumbered.
4. Migration: existing per-module sequences backfill into the registry;
   observable numbering behavior is unchanged (regression-tested).
5. Events: `platform.numbering.scheme_updated`. Permission:
   `platform:numbering:configure` (sensitive; platform namespace,
   [authorization & policy §2.1](../04-security/authorization-and-policy.md)).

---

## Verification requirements

1. **Numbering registry**: concurrent allocation yields unique,
    gapless sequences (gapless schemes); scheme change never alters
    issued numbers; backfilled modules produce identical numbering
    (regression fixtures).
2. **Tenant isolation**: scheme rows are covered by the `AssertTenantIsolation` matrix — see the tenancy register ([tenancy & data](../06-tenancy/tenancy-and-data.md)).
3. **Contract tests**: `platform:numbering:configure` appears on the registry routes and OpenAPI fragments validate — see the [testing strategy](../19-testing/testing-strategy.md).
