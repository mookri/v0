# Task Specifications — Methodology & Register Index

**Status:** ACTIVE · **Version:** 1.0.0 · **Date:** 2026-08-27
**Derived from:** `planning/04-task-spec-methodology.md` + `planning/README.md` (planning tree removed by the [documentation tree migration](../21-adrs/ADR-0019-documentation-tree-migration.md))

This is the execution layer of the documentation tree: full task specifications for the Phase 0K kernel prerequisite, Phase 2A (Shopify parity), and Phase 4A (ERP parity), carrying all 96 `NEW-v1` parity rows ([Shopify feature inventory](../01-product/shopify-feature-inventory.md) + [ERP feature inventory](../01-product/erp-feature-inventory.md), dispositions registered in the [gap analysis](../01-product/gap-analysis.md)). Task files live under `specs/tasks/{0K,2A,4A}/`; epic files summarize each epic's tasks. Estimates are planning classes (S/M/L), not commitments — sprint planning happens post-approval.

## Spec methodology

The conventions below are normative: every task file in this register follows them, and reviewers reject task docs that do not.

### 1. Hierarchy & Identifiers

```text
Phase/Milestone (23-evidence gates & milestones cadence)
└── Epic          E-<phase>-<nn>        e.g. E-2A-01
    └── Task      T-<epic>-<nn>         e.g. T-2A01-03
        └── Subtask  T-<epic>-<nn>.<s>  e.g. T-2A01-03.2
```

- **Epic**: a deliverable capability (one module or one coherent cross-
  module slice). Maps 1:1 to a domain-doc section group.
- **Task**: a buildable unit of work sized 1–5 engineer-days. A task has
  exactly one owning module and a closed write set (tables/files it may
  create or change).
- **Subtask**: a checklist-level decomposition of a task (schema, contract,
  logic, API, console, tests). Subtasks inherit the task's acceptance
  criteria and add their own completion checks.

ID stability: IDs are never reused or renumbered; superseded tasks are
marked `SUPERSEDED BY <id>`.

### 2. Task Document Structure (normative template)

Every task file `docs/specs/tasks/<phase>/<task-id>.md` MUST contain, in
order:

1. **Header**: id, title, epic, module(s), phase, status
   (`Draft|Approved|In Progress|Done`), owner role, estimate class
   (`S ≤ 1d | M ≤ 3d | L ≤ 5d`).
2. **Traceability**: inventory rows implemented (e.g. `SH-045, SH-046`),
   domain-doc sections (e.g. `14-domains/sales/sales.md` §3.1–§3.4), dependent tasks.
3. **Objective**: 2–4 sentences, what capability exists after this task
   that did not exist before. No implementation narration.
4. **Scope**: explicit in-scope list; explicit out-of-scope list
   (prevents scope creep; out-of-scope items cite the task that owns
   them).
5. **Subtasks**: numbered table with id, description, deliverable,
   completion check.
6. **Acceptance Criteria**: numbered `AC-<task>-<n>` statements (see §3).
7. **Verification Mapping**: each AC → the concrete test/gate that proves
   it ([testing strategy](../19-testing/testing-strategy.md) gate names + architecture-doc verification section refs).
8. **Dependencies & Sequencing**: tasks that must be `Done` first; tasks
   this task unblocks.
9. **Risks & Notes**: known unknowns, decisions needed, seams touched.

### 3. Acceptance Criteria Format (normative)

Each acceptance criterion MUST be:

- **Observable**: describes externally verifiable behavior (API response,
  event emitted, table invariant, CI gate result) — never internal design
  intent.
- **Decidable**: an engineer and a reviewer can agree yes/no with no
  interpretation. Words like "appropriate", "reasonable", "user-friendly"
  are banned in ACs.
- **Testable in CI**: every AC maps to at least one automated check
  (PHPUnit feature/unit test, contract test, architecture test, or a
  named standing suite). Manual-only ACs are prohibited except for
  explicitly flagged UX review items marked `(manual)`.
- **Independently falsifiable**: failing the check MUST mean the task is
  not done.

Canonical AC sentence shapes:

```text
AC-<task>-1  GIVEN <state> WHEN <action> THEN <observable outcome>.
AC-<task>-2  <Invariant> holds: <property> (enforced by <test name>).
AC-<task>-3  Endpoint <METHOD PATH> returns <status + envelope code> when <condition>.
AC-<task>-4  Event <event.name> is emitted with <payload properties> when <transition>.
AC-<task>-5  CI gate <gate> passes with <artifact>.
```

Rule of coverage: a task MUST have at least one AC per subtask category
(schema, behavior, API, event, authorization, tenancy) that the task
touches. Authorization-touching tasks MUST include a negative AC (the
denial path). Tenant-scoped work MUST include an isolation AC citing the
`AssertTenantIsolation` matrix ([tenancy & data §11](../06-tenancy/tenancy-and-data.md), [testing strategy §5](../19-testing/testing-strategy.md)).

### 4. Definition of Done (applies to every task)

A task is `Done` only when **all** of the following hold — the task-level view of the platform Definition of Done (canonical: [CON-011 Definition of Done](../00-constitution/constitution.md#con-011-definition-of-done), reflected in the [testing strategy's Definition of Done](../19-testing/testing-strategy.md#definition-of-done)):

1. All ACs pass in CI (not locally-only).
2. Pint, PHPStan (current baseline), Deptrac gates green ([testing strategy §10](../19-testing/testing-strategy.md)).
3. Module manifest updated if permissions/scopes/events/navigation
   changed; `module:list` output reflects it.
4. OpenAPI fragment updated and contract tests green for any new/changed
   endpoint ([testing strategy §4](../19-testing/testing-strategy.md)).
5. Audit coverage present for every state change introduced ([kernel services §5.3](../07-kernel/kernel-services.md)).
6. No new cross-module joins or non-contract imports (architecture tests
   green, [domain framework §7](../19-testing/testing-strategy.md)).
7. Documentation deltas landed: domain doc section cited by the
   task matches implemented behavior (doc drift is a review blocker).

### 5. Task Granularity Heuristics

- If a task's write set spans more than 2 modules, split it.
- If a task has more than 8 subtasks, split it.
- If acceptance criteria exceed 12, the task is probably an epic — split.
- Schema migration tasks are separate from behavior tasks when the schema
  is consumed by ≥2 tasks (shared foundation task).
- Every phase's first task is the module scaffold task (directory,
  manifest, provider, empty contracts, routes registered, test
  directories) so later tasks never debate structure.

### 6. Spec Authoring Workflow

1. Pick an epic from `docs/specs/README.md` backlog (ordered by [gates & milestones](../23-evidence/gates-and-milestones.md)
   cadence).
2. Draft tasks using the template; cite domain-doc sections precisely
   (doc + § number).
3. Self-check against §3 (observable/decidable/CI-testable) and §5.
4. Consistency check: every referenced event/permission/scope exists in
   the domain docs (grep the permission inventories).
5. Mark `Approved` when reviewed; only `Approved` tasks enter sprint
   planning.

### 7. Verification (this methodology)

Accepted when:
1. The template in §2 is used verbatim by every file in
   `docs/specs/tasks/`.
2. Every AC in `docs/specs/tasks/` conforms to §3 shapes (lint by review).
3. Every task's Verification Mapping references an existing [testing strategy](../19-testing/testing-strategy.md) gate
   or architecture verification section.

## Disposition codes

Every inventory row in the [Shopify feature inventory](../01-product/shopify-feature-inventory.md) and [ERP feature inventory](../01-product/erp-feature-inventory.md) carries exactly one disposition, registered in the [gap analysis](../01-product/gap-analysis.md):

- `COVERED` — already specified in the canonical tree (00–23); the inventory row cites the owning section. Tasks may cite COVERED rows as deltas (e.g. `ERP-007 · COVERED (delta)`) when they supply inputs to an existing capability.
- `NEW-v1` — accepted into v1 scope; specified normatively in the domain docs (`14-domains/`, `15-integrations/`) and carried by at least one task in this register.
- `DEFERRED` — acknowledged with a target phase (post-v1) and a seam that keeps the door open; not task-spec'd. Deferral seams are recorded in the gap analysis.
- `OUT` — explicitly out of scope, with a reason.

## Traceability rules

1. **No orphan features.** Every `NEW-v1` row appears in (a) a normative domain-doc section, (b) that domain doc's permission + event summaries, and (c) at least one task's Traceability section in this register.
2. **No orphan tasks.** Every task cites the domain-doc sections it implements and the standing suites + gates ([testing strategy](../19-testing/testing-strategy.md)) that prove its acceptance criteria, plus its phase gate ([gates & milestones](../23-evidence/gates-and-milestones.md#gate-register)).
3. **Inventory completeness is asserted, not assumed.** Newly discovered features enter through a gap-analysis amendment, never directly into a task.

```text
SH / ERP inventory rows (01-product)
        │
        ▼
disposition register (01-product/gap-analysis.md)
        │
        ▼
normative domain docs (14-domains/*, 15-integrations/*)
        │
        ▼
task specs (specs/tasks/{0K,2A,4A}) ── acceptance criteria
        │
        ▼
standing suites + CI gates (19-testing) ── phase gates (23-evidence)
```

## Register index

Phase totals: 0K — 8 tasks (grouped, no epic files); 2A — 14 epics / 33 tasks; 4A — 12 epics / 37 tasks.

### Phase 0K — kernel prerequisite (8 tasks, grouped — no epic files)

| Task | Title | Group | Est |
|------|-------|-------|-----|
| [T-0K01-01](./tasks/0K/T-0K01-01.md) | Capability metadata + policies schema | 0K — kernel authorization upgrade | M |
| [T-0K01-02](./tasks/0K/T-0K01-02.md) | Policy evaluation engine + explicit DENY precedence | 0K — kernel authorization upgrade | L |
| [T-0K01-03](./tasks/0K/T-0K01-03.md) | Authorization decision log + AI-agent safety rules | 0K — kernel authorization upgrade | M |
| [T-0K01-04](./tasks/0K/T-0K01-04.md) | Delegation grants | 0K — kernel authorization upgrade | M |
| [T-0K02-01](./tasks/0K/T-0K02-01.md) | Typed hierarchy schema + node/relationship CRUD | 0K — kernel authorization upgrade | L |
| [T-0K02-02](./tasks/0K/T-0K02-02.md) | Working Context resolution + invariant + forgery denial | 0K — kernel authorization upgrade | L |
| [T-0K02-03](./tasks/0K/T-0K02-03.md) | Context switching API/UX contract + org-tree migration | 0K — kernel authorization upgrade | L |
| [T-0K03-01](./tasks/0K/T-0K03-01.md) | Control-plane blueprint + provisioning reconcile loop | 0K — kernel authorization upgrade | L |

### Phase 2A — Shopify parity (14 epics · 33 tasks)

| Epic | Task | Title | Inventory rows | Est |
|------|------|-------|----------------|-----|
| [E-2A-01](./tasks/2A/E-2A-01.md) | [T-2A01-01](./tasks/2A/T-2A01-01.md) | Markets module scaffold + entities | — | M |
| [E-2A-01](./tasks/2A/E-2A-01.md) | [T-2A01-02](./tasks/2A/T-2A01-02.md) | Market price resolver + projections | SH-150, SH-151 | L |
| [E-2A-01](./tasks/2A/E-2A-01.md) | [T-2A01-03](./tasks/2A/T-2A01-03.md) | Duties at checkout + tax display modes | SH-152, SH-155 | M |
| [E-2A-02](./tasks/2A/E-2A-02.md) | [T-2A02-01](./tasks/2A/T-2A02-01.md) | Draft orders + invoice link | SH-045 | M |
| [E-2A-02](./tasks/2A/E-2A-02.md) | [T-2A02-02](./tasks/2A/T-2A02-02.md) | Order editing + restock | SH-046, SH-047 · COVERED (delta) | L |
| [E-2A-02](./tasks/2A/E-2A-02.md) | [T-2A02-03](./tasks/2A/T-2A02-03.md) | Order notes, tags & attributes | SH-049 | S |
| [E-2A-02](./tasks/2A/E-2A-02.md) | [T-2A02-04](./tasks/2A/T-2A02-04.md) | Deposits & payment terms | SH-076, SH-078 | M |
| [E-2A-02](./tasks/2A/E-2A-02.md) | [T-2A02-05](./tasks/2A/T-2A02-05.md) | Order risk annotation | SH-053 | M |
| [E-2A-03](./tasks/2A/E-2A-03.md) | [T-2A03-01](./tasks/2A/T-2A03-01.md) | Checkout buy rules | SH-011 | M |
| [E-2A-04](./tasks/2A/E-2A-04.md) | [T-2A04-01](./tasks/2A/T-2A04-01.md) | Buyer profiles + auth wall | SH-092 | M |
| [E-2A-04](./tasks/2A/E-2A-04.md) | [T-2A04-02](./tasks/2A/T-2A04-02.md) | Order history + reorder | SH-093, SH-055 · COVERED (delta) | M |
| [E-2A-04](./tasks/2A/E-2A-04.md) | [T-2A04-03](./tasks/2A/T-2A04-03.md) | Wishlists | SH-095 | S |
| [E-2A-04](./tasks/2A/E-2A-04.md) | [T-2A04-04](./tasks/2A/T-2A04-04.md) | Store credit ledger | SH-073, SH-097 | M |
| [E-2A-04](./tasks/2A/E-2A-04.md) | [T-2A04-05](./tasks/2A/T-2A04-05.md) | Customer merge | SH-098 | M |
| [E-2A-04](./tasks/2A/E-2A-04.md) | [T-2A04-06](./tasks/2A/T-2A04-06.md) | Notes & tags on customers | SH-096 | S |
| [E-2A-05](./tasks/2A/E-2A-05.md) | [T-2A05-01](./tasks/2A/T-2A05-01.md) | Metafields engine (v1 preview) | SH-007 | M |
| [E-2A-05](./tasks/2A/E-2A-05.md) | [T-2A05-02](./tasks/2A/T-2A05-02.md) | Metaobjects | SH-008 | M |
| [E-2A-05](./tasks/2A/E-2A-05.md) | [T-2A05-03](./tasks/2A/T-2A05-03.md) | Pages, blogs & navigation menus | SH-112, SH-113, SH-114 | M |
| [E-2A-05](./tasks/2A/E-2A-05.md) | [T-2A05-04](./tasks/2A/T-2A05-04.md) | Translations storage (schema v1) | SH-154 | S |
| [E-2A-06](./tasks/2A/E-2A-06.md) | [T-2A06-01](./tasks/2A/T-2A06-01.md) | Barcode lookup contract + scan operations | SH-030, SH-147, ERP-069 | M |
| [E-2A-06](./tasks/2A/E-2A-06.md) | [T-2A06-02](./tasks/2A/T-2A06-02.md) | POS terminal peripherals | SH-149 | S |
| [E-2A-06](./tasks/2A/E-2A-06.md) | [T-2A06-03](./tasks/2A/T-2A06-03.md) | Digital products & delivery | SH-012 | M |
| [E-2A-07](./tasks/2A/E-2A-07.md) | [T-2A07-01](./tasks/2A/T-2A07-01.md) | Selling plans — preorders & trials | SH-014 | M |
| [E-2A-08](./tasks/2A/E-2A-08.md) | [T-2A08-01](./tasks/2A/T-2A08-01.md) | Discount condition — applies_on_sale_items | SH-067 | S |
| [E-2A-09](./tasks/2A/E-2A-09.md) | [T-2A09-01](./tasks/2A/T-2A09-01.md) | Pickup locations + BOPIS | SH-050, SH-148, SH-121 | M |
| [E-2A-09](./tasks/2A/E-2A-09.md) | [T-2A09-02](./tasks/2A/T-2A09-02.md) | Local delivery zones | SH-051 | S |
| [E-2A-10](./tasks/2A/E-2A-10.md) | [T-2A10-01](./tasks/2A/T-2A10-01.md) | Abandoned checkout recovery | SH-052, SH-132 | M |
| [E-2A-11](./tasks/2A/E-2A-11.md) | [T-2A11-01](./tasks/2A/T-2A11-01.md) | Product feeds | SH-010 | M |
| [E-2A-11](./tasks/2A/E-2A-11.md) | [T-2A11-02](./tasks/2A/T-2A11-02.md) | Sitemaps + redirects | SH-116, SH-117 | M |
| [E-2A-12](./tasks/2A/E-2A-12.md) | [T-2A12-01](./tasks/2A/T-2A12-01.md) | Payment capability flags + wallets | SH-071 · COVERED (delta) | S |
| [E-2A-13](./tasks/2A/E-2A-13.md) | [T-2A13-01](./tasks/2A/T-2A13-01.md) | Data-tools job infrastructure + idempotent imports | SH-009, SH-176, SH-185, ERP-238 | L |
| [E-2A-13](./tasks/2A/E-2A-13.md) | [T-2A13-02](./tasks/2A/T-2A13-02.md) | v1 importer/exporter set | SH-056 | M |
| [E-2A-14](./tasks/2A/E-2A-14.md) | [T-2A14-01](./tasks/2A/T-2A14-01.md) | B2B-lite companies + tax exemption | SH-099, SH-162 | M |

### Phase 4A — ERP parity (12 epics · 37 tasks)

| Epic | Task | Title | Inventory rows | Est |
|------|------|-------|----------------|-----|
| [E-4A-01](./tasks/4A/E-4A-01.md) | [T-4A01-01](./tasks/4A/T-4A01-01.md) | Leads + conversion via published contracts | ERP-001, ERP-005 | M |
| [E-4A-01](./tasks/4A/E-4A-01.md) | [T-4A01-02](./tasks/4A/T-4A01-02.md) | Opportunities + pipeline state machines | ERP-002, ERP-003 | M |
| [E-4A-01](./tasks/4A/E-4A-01.md) | [T-4A01-03](./tasks/4A/T-4A01-03.md) | Activities, campaigns & contact roles | ERP-004, ERP-006, ERP-009, ERP-232 | M |
| [E-4A-01](./tasks/4A/E-4A-01.md) | [T-4A01-04](./tasks/4A/T-4A01-04.md) | Customer 360 read model + forecasting inputs | ERP-008, ERP-007 · COVERED (delta) | M |
| [E-4A-02](./tasks/4A/E-4A-02.md) | [T-4A02-01](./tasks/4A/T-4A02-01.md) | Blanket orders with releases | ERP-022 | M |
| [E-4A-02](./tasks/4A/E-4A-02.md) | [T-4A02-02](./tasks/4A/T-4A02-02.md) | Backorder report + allocation runs | ERP-027 | M |
| [E-4A-02](./tasks/4A/E-4A-02.md) | [T-4A02-03](./tasks/4A/T-4A02-03.md) | Pro-forma invoices (invoicing-owned) | ERP-029 | S |
| [E-4A-02](./tasks/4A/E-4A-02.md) | [T-4A02-04](./tasks/4A/T-4A02-04.md) | Drop-shipping | ERP-194 | M |
| [E-4A-03](./tasks/4A/E-4A-03.md) | [T-4A03-01](./tasks/4A/T-4A03-01.md) | Purchase requisitions with workflow approval | ERP-040 | M |
| [E-4A-03](./tasks/4A/E-4A-03.md) | [T-4A03-02](./tasks/4A/T-4A03-02.md) | Blanket purchase agreements | ERP-043 | M |
| [E-4A-03](./tasks/4A/E-4A-03.md) | [T-4A03-03](./tasks/4A/T-4A03-03.md) | Landed cost allocation at GRN | ERP-045 | M |
| [E-4A-03](./tasks/4A/E-4A-03.md) | [T-4A03-04](./tasks/4A/T-4A03-04.md) | Supplier scorecards (suppliers-owned) | ERP-046 | M |
| [E-4A-03](./tasks/4A/E-4A-03.md) | [T-4A03-05](./tasks/4A/T-4A03-05.md) | Subcontracting flows | ERP-048 | L |
| [E-4A-04](./tasks/4A/E-4A-04.md) | [T-4A04-01](./tasks/4A/T-4A04-01.md) | Onboarding/offboarding checklists | ERP-118 | M |
| [E-4A-05](./tasks/4A/E-4A-05.md) | [T-4A05-01](./tasks/4A/T-4A05-01.md) | Expense reports, approvals & receipts | ERP-094 | M |
| [E-4A-05](./tasks/4A/E-4A-05.md) | [T-4A05-02](./tasks/4A/T-4A05-02.md) | Reimbursement, billable re-charge, GL posting & travel | ERP-120 | M |
| [E-4A-06](./tasks/4A/E-4A-06.md) | [T-4A06-01](./tasks/4A/T-4A06-01.md) | Inspection plans + quality gates | ERP-044 · COVERED (+QC NEW), ERP-175 | L |
| [E-4A-06](./tasks/4A/E-4A-06.md) | [T-4A06-02](./tasks/4A/T-4A06-02.md) | NCR + CAPA management | ERP-244 | M |
| [E-4A-07](./tasks/4A/E-4A-07.md) | [T-4A07-01](./tasks/4A/T-4A07-01.md) | Serial tracking | ERP-061, SH-032 | M |
| [E-4A-07](./tasks/4A/E-4A-07.md) | [T-4A07-02](./tasks/4A/T-4A07-02.md) | Batch/lot tracking, FEFO & genealogy | ERP-062, ERP-177, SH-033 | M |
| [E-4A-07](./tasks/4A/E-4A-07.md) | [T-4A07-03](./tasks/4A/T-4A07-03.md) | FIFO valuation + stock aging | ERP-064, ERP-065 | M |
| [E-4A-07](./tasks/4A/E-4A-07.md) | [T-4A07-04](./tasks/4A/T-4A07-04.md) | Pick lists + put-away (light) | ERP-067 | M |
| [E-4A-08](./tasks/4A/E-4A-08.md) | [T-4A08-01](./tasks/4A/T-4A08-01.md) | GST e-invoicing (IRN) adapter | ERP-211, SH-163 | M |
| [E-4A-08](./tasks/4A/E-4A-08.md) | [T-4A08-02](./tasks/4A/T-4A08-02.md) | E-way bill adapter | ERP-212 | M |
| [E-4A-08](./tasks/4A/E-4A-08.md) | [T-4A08-03](./tasks/4A/T-4A08-03.md) | PF / ESI / PT computation + filing exports | ERP-114, ERP-215 | M |
| [E-4A-08](./tasks/4A/E-4A-08.md) | [T-4A08-04](./tasks/4A/T-4A08-04.md) | GSTR-1 / GSTR-3B exports | ERP-213, SH-164 | M |
| [E-4A-08](./tasks/4A/E-4A-08.md) | [T-4A08-05](./tasks/4A/T-4A08-05.md) | TDS on payouts + TCS on sales | ERP-214 | M |
| [E-4A-09](./tasks/4A/E-4A-09.md) | [T-4A09-01](./tasks/4A/T-4A09-01.md) | Contract lifecycle + renewal orchestration | ERP-245 | L |
| [E-4A-09](./tasks/4A/E-4A-09.md) | [T-4A09-02](./tasks/4A/T-4A09-02.md) | Warranties + coverage contract | ERP-154 | M |
| [E-4A-10](./tasks/4A/E-4A-10.md) | [T-4A10-01](./tasks/4A/T-4A10-01.md) | Shop-floor operation reporting | ERP-174 | M |
| [E-4A-10](./tasks/4A/E-4A-10.md) | [T-4A10-02](./tasks/4A/T-4A10-02.md) | By-products + scrap posting | ERP-176 | M |
| [E-4A-11](./tasks/4A/E-4A-11.md) | [T-4A11-01](./tasks/4A/T-4A11-01.md) | Cost centers & dimension validation | ERP-086 | M |
| [E-4A-11](./tasks/4A/E-4A-11.md) | [T-4A11-02](./tasks/4A/T-4A11-02.md) | Profitability read model (cost center, project, customer) | ERP-087, ERP-135 | M |
| [E-4A-11](./tasks/4A/E-4A-11.md) | [T-4A11-03](./tasks/4A/T-4A11-03.md) | Recurring journal entries | ERP-092 | M |
| [E-4A-11](./tasks/4A/E-4A-11.md) | [T-4A11-04](./tasks/4A/T-4A11-04.md) | AR dunning | ERP-093 | M |
| [E-4A-12](./tasks/4A/E-4A-12.md) | [T-4A12-01](./tasks/4A/T-4A12-01.md) | Scheduled report dispatch | ERP-237 | M |
| [E-4A-12](./tasks/4A/E-4A-12.md) | [T-4A12-02](./tasks/4A/T-4A12-02.md) | Document numbering registry | ERP-242 · COVERED → upgraded | M |

## NEW-v1 parity coverage

This register carries all **96 NEW-v1 parity rows**:

- **Shopify — 49 rows** carried by Phase 2A tasks, except: SH-032 → T-4A07-01, SH-033 → T-4A07-02, SH-163 → T-4A08-01, SH-164 → T-4A08-04 (carried by Phase 4A tasks where the capability lives on the ERP side of the stack).
- **ERP — 47 rows** carried by Phase 4A tasks, except: ERP-069 → T-2A06-01, ERP-238 → T-2A13-01 (carried by Phase 2A tasks whose surface they extend).
- **COVERED rows cited as deltas** (traceability only, not NEW-v1 scope): SH-047 · COVERED (delta) → T-2A02-02; SH-055 · COVERED (delta) → T-2A04-02; SH-071 · COVERED (delta) → T-2A12-01; ERP-007 · COVERED (delta) → T-4A01-04; ERP-044 · COVERED (+QC NEW) → T-4A06-01; ERP-242 · COVERED → upgraded → T-4A12-02.

Coverage is verified against the disposition register ([gap analysis](../01-product/gap-analysis.md)) — every NEW-v1 row must appear in at least one task's Traceability section, and every task must cite at least one domain-doc anchor.
