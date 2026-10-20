# Navdhya Platform — Documentation

**Status:** ACTIVE · **Version:** 2.0.0 · **Date:** 2026-08-27
**Derived from:** architecture/README.md v1.0.0; planning/README.md v1.0.0

Navdhya is a multi-tenant, policy-driven, event-oriented enterprise operating
platform whose ERP, CRM, Commerce, POS, Supply Chain and AI capabilities are
independently composable modules sitting on a shared platform kernel.

The normative keywords **MUST**, **MUST NOT**, **SHOULD**, **SHOULD NOT** and
**MAY** in this documentation are interpreted per RFC 2119.

This tree is organized by concern, not by legacy document number. Every normative statement carries a stable section-level ID (registers below); citations use relative links with ID anchors. The legacy `doc NN §x` citation style no longer exists in this tree — use the old→new mapping below to repair external references. Document lifecycle, metadata schema, and change management are governed by the [constitution](./00-constitution/constitution.md).

---

## Document index

| Directory | Files | Contents |
|-----------|-------|----------|
| `00-constitution/` | [constitution.md](./00-constitution/constitution.md) | CON-001…012 — architectural principles, Definition of Done, change management, document governance |
| `01-product/` | [product-baseline.md](./01-product/product-baseline.md) (PROD-001…012) · [shopify-feature-inventory.md](./01-product/shopify-feature-inventory.md) · [erp-feature-inventory.md](./01-product/erp-feature-inventory.md) · [gap-analysis.md](./01-product/gap-analysis.md) | Product scope, personas, capability→domain→module map, plans; the two parity feature inventories with dispositions; gap analysis + deferral register |
| `02-architecture/` | [architecture-master.md](./02-architecture/architecture-master.md) | ARCH-001…020 — layered architecture, dependency rules, planes, fitness functions, NFR targets, risk register (ARCH-019) |
| `03-delta/` | [delta-isolation.md](./03-delta/delta-isolation.md) | DELTA-001…016 — boundary taxonomy, Delta matrix, verification strategy |
| `04-security/` | [identity-and-token-platform.md](./04-security/identity-and-token-platform.md) (SEC-001…004, 014, 015) · [authorization-and-policy.md](./04-security/authorization-and-policy.md) (SEC-005…013 + SEC-020…024 capability upgrade) · [security-baseline.md](./04-security/security-baseline.md) (SEC-016…019) | Identity/OAuth2/OIDC token platform; RBAC/ABAC + capability model, policies, explicit DENY, delegation, decision log; security baseline & ASVS |
| `05-hierarchy/` | [hierarchy-and-working-context.md](./05-hierarchy/hierarchy-and-working-context.md) | HIER-001…016 — typed hierarchies, authority roots, scope modes, Working Context invariant, forged-context denial, org-tree migration |
| `06-tenancy/` | [tenancy-and-data.md](./06-tenancy/tenancy-and-data.md) | TEN-001…018 — hybrid tenancy, resolution, fail-closed posture, promotion runbook, placement |
| `07-kernel/` | [kernel-contracts.md](./07-kernel/kernel-contracts.md) (K-001…030) · [kernel-services.md](./07-kernel/kernel-services.md) · [api-platform.md](./07-kernel/api-platform.md) · [data-tools.md](./07-kernel/data-tools.md) · [numbering.md](./07-kernel/numbering.md) | Normative kernel contract register; audit/notifications/files/search/settings/flags/observability/idempotency; API conventions & OpenAPI; shared import/export surface; document numbering |
| `08-control-plane/` | [control-plane.md](./08-control-plane/control-plane.md) | CTRL-001…021 — plans/entitlements, module registry, feature state, blueprints, desired/observed, reconciliation, provisioning, configuration precedence |
| `09-domain-framework/` | [domain-framework.md](./09-domain-framework/domain-framework.md) | DF-001…016 — module shape, manifest, aggregates/commands/queries, T0–T4 transaction classes, cross-domain rules |
| `10-experience/` | [experience-architecture.md](./10-experience/experience-architecture.md) | EXP-001…019 — panel model, navigation contract, permission-aware UI, Working Context switcher UX, Blade↔API convergence, public-surface allowlist |
| `11-workflow/` | [workflow-engine.md](./11-workflow/workflow-engine.md) | WF-001…016 — workflow engine, approvals, delegation/escalation, compensation |
| `12-events/` | [events-and-outbox.md](./12-events/events-and-outbox.md) | EV-001…015 — event envelope, outbox, relay, kernel event catalog |
| `13-data/` | [data-architecture.md](./13-data/data-architecture.md) | DATA-001…023 — schema conventions, identifiers, time/money, encryption, retention |
| `14-domains/` | [README.md](./14-domains/README.md) + 29 domain files | Business domains: sales, crm, procurement, inventory, finance, commerce, hr, payroll, projects, service, manufacturing, supply-chain (incl. Phase 2a/4a parity sections) |
| `15-integrations/` | [integration-framework.md](./15-integrations/integration-framework.md) · [connectors/shopify.md](./15-integrations/connectors/shopify.md) · [connectors/india-statutory.md](./15-integrations/connectors/india-statutory.md) | Connector contract + webhooks; Shopify and India-statutory connector dossiers |
| `16-analytics/` | [analytics.md](./16-analytics/analytics.md) | Analytics module + canonical metric semantics + scheduled report dispatch |
| `17-ai/` | [intelligence.md](./17-ai/intelligence.md) | Forecasting, anomaly, recommendations, copilot, agents + AI principal/authorization rules |
| `18-extensions/` | [ecosystem.md](./18-extensions/ecosystem.md) | Module SDK, partner OAuth, webhooks GA, marketplace, custom objects, SCIM GA |
| `19-testing/` | [testing-strategy.md](./19-testing/testing-strategy.md) | Test pyramid, standing suites, CI pipeline & quality gates, load, DoD, Delta verification mapping, gate automation |
| `20-sre/` | [sre-operations.md](./20-sre/sre-operations.md) · [ha-dr.md](./20-sre/ha-dr.md) | NFR targets, health/readiness, alerting, noisy-neighbor controls; HA/DR and failure-mode degradation |
| `21-adrs/` | [ADR-0001](./21-adrs/ADR-0001-laravel-modular-monolith-mysql.md) … [ADR-0019](./21-adrs/ADR-0019-documentation-tree-migration.md) | 19 seeded decision records (stack, capability syntax, parity seams, typed hierarchies, documentation tree migration) |
| `22-runbooks/` | [tenant-promotion.md](./22-runbooks/tenant-promotion.md) · [incident-response.md](./22-runbooks/incident-response.md) · [provisioning.md](./22-runbooks/provisioning.md) | Operational procedures |
| `23-evidence/` | [gates-and-milestones.md](./23-evidence/gates-and-milestones.md) | GATE-0…5 + GATE-SALES register, M0–M7 milestones, post-90-day cadence (incl. P2a/P4a parity), evidence registry |
| `specs/` | [README.md](./specs/README.md) + tasks/{0K,2A,4A} | Execution layer: task-spec methodology + full task specs for Phase 0K kernel upgrade and all 96 NEW-v1 parity rows |
---

## ID register map

Document IDs are **section-level** anchors inside concern files (not one file per ID). Register families and their homes:

| Family | Range | Count | Home |
|--------|-------|-------|------|
| CON | CON-001…CON-012 | 12 | [00-constitution/constitution.md](./00-constitution/constitution.md) |
| PROD | PROD-001…PROD-012 | 12 | [01-product/product-baseline.md](./01-product/product-baseline.md) |
| ARCH | ARCH-001…ARCH-020 | 20 | [02-architecture/architecture-master.md](./02-architecture/architecture-master.md) |
| DELTA | DELTA-001…DELTA-016 | 16 | [03-delta/delta-isolation.md](./03-delta/delta-isolation.md) |
| SEC | SEC-001…SEC-024 | 24 | 04-security (3 files) |
| HIER | HIER-001…HIER-016 | 16 | [05-hierarchy/hierarchy-and-working-context.md](./05-hierarchy/hierarchy-and-working-context.md) |
| TEN | TEN-001…TEN-018 | 18 | [06-tenancy/tenancy-and-data.md](./06-tenancy/tenancy-and-data.md) |
| K | K-001…K-030 | 30 | [07-kernel/kernel-contracts.md](./07-kernel/kernel-contracts.md) |
| CTRL | CTRL-001…CTRL-021 | 21 | [08-control-plane/control-plane.md](./08-control-plane/control-plane.md) |
| DF | DF-001…DF-016 | 16 | [09-domain-framework/domain-framework.md](./09-domain-framework/domain-framework.md) |
| EXP | EXP-001…EXP-019 | 19 | [10-experience/experience-architecture.md](./10-experience/experience-architecture.md) |
| WF | WF-001…WF-016 | 16 | [11-workflow/workflow-engine.md](./11-workflow/workflow-engine.md) |
| EV | EV-001…EV-015 | 15 | [12-events/events-and-outbox.md](./12-events/events-and-outbox.md) |
| DATA | DATA-001…DATA-023 | 23 | [13-data/data-architecture.md](./13-data/data-architecture.md) |
| ADR | ADR-0001…ADR-0019 | 19 | 21-adrs/ (one file each) |

Domain modules under `14-domains/` carry module-level sections (module shape, permission summary, verification requirements) rather than numeric ID families; the per-file old→new map for domain content lives in [14-domains/README.md](./14-domains/README.md).

---

## Execution specs

`specs/` is the execution layer: the [task-spec methodology](./specs/README.md) (identifiers, normative task template, acceptance-criteria format, Definition of Done, granularity rules) plus full task specs:

| Phase | Scope | Location |
|-------|-------|----------|
| 0K | Kernel authorization/hierarchy upgrade (prerequisite for parity) — capability model, policies, explicit DENY, delegation, decision log, typed hierarchies + Working Context, control-plane provisioning | `specs/tasks/0K/` |
| 2A | Shopify parity — 14 epics covering all 49 NEW-v1 Shopify inventory rows | `specs/tasks/2A/` |
| 4A | ERP parity — 12 epics covering all 47 NEW-v1 ERP inventory rows | `specs/tasks/4A/` |

Traceability obligation: every NEW-v1 row in the [Shopify](./01-product/shopify-feature-inventory.md) and [ERP](./01-product/erp-feature-inventory.md) inventories appears in at least one task's traceability section; every task cites at least one new-tree document anchor and maps each acceptance criterion to a suite in the [testing strategy](./19-testing/testing-strategy.md) and a gate in the [evidence register](./23-evidence/gates-and-milestones.md).

---

## Old → new mapping

The legacy `docs/architecture/` and `docs/planning/` directories were fully emptied and removed. Use this table to repair external references:

| Old location | New location |
|--------------|--------------|
| `architecture/README.md` | this index; principles → [CON-002](./00-constitution/constitution.md#con-002-architectural-principles); glossary → appendix below; phase map → [product baseline](./01-product/product-baseline.md) |
| `architecture/00-overview.md` | [02-architecture](./02-architecture/architecture-master.md) (ARCH-001/002/003/015/016/017); NFR targets → [20-sre](./20-sre/sre-operations.md) |
| `architecture/01-repo-structure-and-module-system.md` | [09-domain-framework](./09-domain-framework/domain-framework.md) (DF-001/002/011–014, manifest K-015); dependency rules → [ARCH-009](./02-architecture/architecture-master.md#arch-009-enforcement-tooling); enforcement → [19-testing](./19-testing/testing-strategy.md) |
| `architecture/02-tenancy-and-data.md` | §1–7 → [06-tenancy](./06-tenancy/tenancy-and-data.md) (TEN-001…018); §8–10 → [13-data](./13-data/data-architecture.md) (DATA-007…014); schema conventions → DATA-003…006 |
| `architecture/03-identity-sso-token-platform.md` | [04-security/identity-and-token-platform.md](./04-security/identity-and-token-platform.md) (SEC-001…004, SEC-014); SCIM GA section → [18-extensions](./18-extensions/ecosystem.md) |
| `architecture/04-authorization-and-policy.md` | [04-security/authorization-and-policy.md](./04-security/authorization-and-policy.md) (SEC-005…013) + new capability upgrade SEC-020…024 |
| `architecture/05-kernel-services.md` | events/outbox → [12-events](./12-events/events-and-outbox.md); workflow → [11-workflow](./11-workflow/workflow-engine.md); remainder → [07-kernel/kernel-services.md](./07-kernel/kernel-services.md) |
| `architecture/06-api-platform-and-channels.md` | §1–6/8/11 → [07-kernel/api-platform.md](./07-kernel/api-platform.md); §7 webhooks + §10 channel adapters → [15-integrations](./15-integrations/integration-framework.md) |
| `architecture/07-phase1-commercial-core.md` | split into [14-domains](./14-domains/README.md): catalog, pricing, customers, suppliers, sales, purchasing, inventory, payments, invoicing |
| `architecture/08-phase2-commerce.md` | store-checkout, promotions-shipping-returns, giftcards-subscriptions-pos under 14-domains/commerce; §9 → [connector seed](./15-integrations/connectors/shopify.md); §10.2 allowlist → [EXP-006](./10-experience/experience-architecture.md#exp-006-public-surface-allowlist-consolidation) + [CI pin](./19-testing/testing-strategy.md#public-surface-allowlist-ci-pinned) |
| `architecture/09-phase3-finance.md` | 14-domains/finance: gl, tax, ar-ap-banking, assets-budgets-reports |
| `architecture/10-phase4-enterprise-operations.md` | 14-domains: hr, payroll, projects, service, manufacturing, supply-chain; §7.5 sensitivity classes → [security-baseline](./04-security/security-baseline.md) |
| `architecture/11-phase5-ecosystem.md` | [18-extensions/ecosystem.md](./18-extensions/ecosystem.md) |
| `architecture/12-phase6-intelligence.md` | §1 → [16-analytics](./16-analytics/analytics.md); §2–6 → [17-ai](./17-ai/intelligence.md) |
| `architecture/13-security-compliance-nfr.md` | §1–7 → [security-baseline](./04-security/security-baseline.md); §8–9 → [ha-dr](./20-sre/ha-dr.md) + [incident-response](./22-runbooks/incident-response.md) |
| `architecture/14-quality-testing-ci.md` | [19-testing/testing-strategy.md](./19-testing/testing-strategy.md) (DoD → [CON-011](./00-constitution/constitution.md#con-011-definition-of-done)) |
| `architecture/15-implementation-sequence.md` | [23-evidence/gates-and-milestones.md](./23-evidence/gates-and-milestones.md); risk register → [ARCH-019](./02-architecture/architecture-master.md#arch-019-risk-register) |
| `architecture/16-phase2a-shopify-parity.md` | decomposed: markets, order ops, buy rules/recovery/discovery/payment flags, customer accounts, content, POS/barcode/digital, selling plans, promotions, local commerce → 14-domains/commerce + sales + crm files; §14 → [07-kernel/data-tools.md](./07-kernel/data-tools.md); summary/verification → files + 19-testing |
| `architecture/17-phase4a-erp-parity.md` | decomposed: CRM, sales ext, pro-forma, purchasing ext, HR checklists, expenses, quality, inventory ext, contracts, manufacturing ext, finance ext → 14-domains files; §9 → [india-statutory dossier](./15-integrations/connectors/india-statutory.md); §13.2 → [analytics](./16-analytics/analytics.md); §13.3 → [numbering](./07-kernel/numbering.md) |
| `planning/README.md` | traceability rules + disposition codes → [specs/README.md](./specs/README.md); scope summary → [gap-analysis](./01-product/gap-analysis.md) |
| `planning/01-shopify-feature-inventory.md` | [01-product/shopify-feature-inventory.md](./01-product/shopify-feature-inventory.md) (counts verified 49/74/15) |
| `planning/02-erp-feature-inventory.md` | [01-product/erp-feature-inventory.md](./01-product/erp-feature-inventory.md) (counts fixed 47/53/16) |
| `planning/03-gap-analysis-and-decisions.md` | [01-product/gap-analysis.md](./01-product/gap-analysis.md); LD-01…LD-16 → [ADR-0002](./21-adrs/ADR-0002-capability-colon-syntax.md)…[ADR-0017](./21-adrs/ADR-0017-delegation-time-boxed.md); authorization upgrade → [SEC-020…024](./04-security/authorization-and-policy.md) |
| `planning/04-task-spec-methodology.md` | [specs/README.md](./specs/README.md) |

The detailed per-domain-file decomposition map (which old section landed in which domain file) is maintained in [14-domains/README.md](./14-domains/README.md). The migration itself is recorded in [ADR-0019](./21-adrs/ADR-0019-documentation-tree-migration.md).

---

## Reading order

**New contributors:** [constitution](./00-constitution/constitution.md) → [architecture master](./02-architecture/architecture-master.md) → [domain framework](./09-domain-framework/domain-framework.md) → [tenancy](./06-tenancy/tenancy-and-data.md) → [identity](./04-security/identity-and-token-platform.md) → [authorization](./04-security/authorization-and-policy.md) → [kernel contracts](./07-kernel/kernel-contracts.md) → [API platform](./07-kernel/api-platform.md) → [events](./12-events/events-and-outbox.md) → [workflow](./11-workflow/workflow-engine.md) → [control plane](./08-control-plane/control-plane.md), then the [domain file](./14-domains/README.md) for the module being worked on, then [testing strategy](./19-testing/testing-strategy.md) and [gates & milestones](./23-evidence/gates-and-milestones.md) before writing code.

**Parity work:** [gap analysis](./01-product/gap-analysis.md) → the relevant inventory → the owning domain file's Phase 2a/4a section → the task in `specs/tasks/`.

**Security review:** [security baseline](./04-security/security-baseline.md) → [authorization & policy](./04-security/authorization-and-policy.md) → [hierarchies & Working Context](./05-hierarchy/hierarchy-and-working-context.md) → [Delta isolation](./03-delta/delta-isolation.md) → [testing strategy](./19-testing/testing-strategy.md).

---

## Technology baseline

| Concern | Choice |
|---------|--------|
| Runtime | PHP 8.2+ (dev target 8.4/8.5), Laravel 12 |
| Shape | Modular monolith, API-first, extraction-ready |
| Database | MySQL 8 (utf8mb4, InnoDB) |
| Cache / Queue / Sessions (prod) | Redis |
| Authorization server | Laravel Passport (OAuth 2.0 + OIDC surface) |
| Admin console (v1) | Velzon Blade template, server-rendered |
| API surface | `/api/v1` JSON, OpenAPI 3.1 contracts |
| Static analysis | PHPStan (level 6 baseline → 8), Deptrac, Laravel Pint |

Stack deviations from the referenced master plan (MySQL 8 not PostgreSQL, Laravel modular monolith not Nx, colon capability syntax) are recorded in [ADR-0001](./21-adrs/ADR-0001-laravel-modular-monolith-mysql.md) and [ADR-0002](./21-adrs/ADR-0002-capability-colon-syntax.md).

---

## Architectural principles (summary)

The seven architectural principles are canonized as [CON-002](./00-constitution/constitution.md#con-002-architectural-principles) in the constitution: domains depend on the platform (never the reverse); integration via contracts and events only; API-first and extraction-ready; fail closed; everything is auditable; manifest-driven platform; additive evolution. The fail-closed posture, auditability, and manifest-driven rules each have their own entries ([CON-003](./00-constitution/constitution.md#con-003-fail-closed-posture), [CON-004](./00-constitution/constitution.md#con-004-everything-is-auditable), [CON-005](./00-constitution/constitution.md#con-005-manifest-driven-platform)).

---

## Glossary

| Term | Definition |
|------|------------|
| **Tenant** | An isolated customer organization of the platform; root of all row-level data isolation. |
| **Membership** | The binding of a user to a tenant with an org assignment and role assignments. A user may hold memberships in multiple tenants. |
| **Organization (Org)** | A node in a tenant's org tree used for ABAC scoping of data visibility. Since the hierarchy upgrade, the org tree is the `organization` typed hierarchy (HIER-015). |
| **Kernel / Platform Kernel** | The set of primitives in `app/Platform/` (identity, tenancy, authorization, events, workflow, audit, etc.). |
| **Domain Module** | A business capability under `app/Modules/<Domain>/` with a manifest, bounded context, and published contracts. |
| **Module Manifest** | `module.json` declaring code, version, phase, dependencies, permissions, events, scopes, feature flag, navigation (K-015). |
| **Contract Surface** | A module's `Contracts/` directory — the only code other modules may import. |
| **Channel Adapter** | An integration boundary exposing the platform to an external or non-web channel (POS, mobile, partner, Shopify, WooCommerce). |
| **Permission Code / Capability** | `<module>:<resource>:<action>` — the unit of RBAC authorization, declared in manifests; capabilities carry structured conditions per SEC-020. |
| **Scope** | OAuth 2.0 scope mirroring permission codes; effective authority = scopes ∩ grants ∩ membership. |
| **Authority Grant** | A binding of a principal to a hierarchy node with a scope mode (K-005); the mechanism behind role + org assignments. |
| **Policy** | A first-class authorization object evaluated in the explicit-DENY precedence chain (SEC-021/022). |
| **Typed Hierarchy** | A named tree type (`organization`, `management`, `sales_territory`, `warehouse`, `legal_entity`, `project`, `custom`) whose nodes carry authority (HIER-001…003). |
| **Scope Mode** | `SELF|NODE|DIRECT_CHILDREN|SUBTREE` — the mechanism by which an authority grant covers hierarchy nodes; business meaning is defined per domain (HIER-005). |
| **Working Context** | The current operating node for a session/API call; hard invariant `WorkingContext ⊆ MaximumAuthority` (HIER-006…008). |
| **SoD** | Segregation of Duties — declarative conflict rules enforced at assignment and authorization time (SEC-011). |
| **Delta** | A cross-boundary interaction (kernel↔domain, module↔module, tenant↔tenant, …); every Delta class is isolated and verified per the DELTA register. |
| **Outbox** | Transactional table capturing domain events for reliable relay to queues/webhooks (EV register). |
| **Event Catalog** | The kernel-materialized registry of all domain event names and payload schemas. |
| **Blueprint** | Declarative desired state of a tenant (modules, configuration, entitlements) reconciled by the control plane (CTRL-005…009). |
| **Control Plane** | The platform surface that materializes plans, manifests, blueprints, provisioning, and desired-vs-observed reconciliation (CTRL register). |
| **DB Mode** | Per-tenant storage mode: `shared` (row-level) or `dedicated` (own database). See the tenancy register. |
| **JIT Provisioning** | Just-in-time creation of user + membership on first federated login. |
| **Step-up Auth** | Re-authentication to a higher ACR level before sensitive operations. |
| **ACR** | Authentication Context Class Reference (OIDC) — the assurance level of the active session. |
| **IdP Federation** | Delegating primary authentication to an enterprise identity provider (OIDC/SAML) per tenant. |
| **SCIM** | System for Cross-domain Identity Management 2.0 — provisioning protocol for users/groups. |
| **Disposition** | The v1 verdict on an inventoried external feature: `COVERED`, `NEW-v1`, `DEFERRED`, or `OUT` (gap analysis register). |
| **Phase 2a / 4a** | The Shopify-parity and ERP-parity phases covering the 96 NEW-v1 inventory rows; task-spec'd under `specs/tasks/`. |
| **GATE-SALES** | The standing order-lifecycle E2E gate, re-run at every gate from GATE-3 onward (evidence register). |
