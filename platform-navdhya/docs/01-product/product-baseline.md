# Product Baseline

**Status:** APPROVED · **Version:** 1.0.0 · **Date:** 2026-08-27
**Derived from:** pasted master plan; doc 00 §1/§8; planning 03 §1.3; doc 02 §3

The product baseline fixes what Navdhya v1 is and is not: scope, non-goals, personas, platform surfaces, the authoritative Capability → Domain → Module map, the new parity modules, and the plan/entitlement model. It is the single reference for capability-to-module resolution.

## Register index

| ID | Section | Summary |
|----|---------|---------|
| PROD-001 | [Product positioning](#prod-001-product-positioning) | Navdhya is a multi-tenant, policy-driven, event-oriented enterprise |
| PROD-002 | [Scope](#prod-002-scope) | v1 scope is the union of: the commercial core (catalog, customers, |
| PROD-003 | [Non-goals](#prod-003-non-goals) | Explicitly out of v1: SPA or native mobile clients; multi-region |
| PROD-004 | [Personas](#prod-004-personas) | Three primary personas drive plan and surface decisions: |
| PROD-005 | [Platform surfaces](#prod-005-platform-surfaces) | v1 ships five surfaces: the Blade admin console (server-rendered, |
| PROD-006 | [Capability → Domain → Module map (authoritative)](#prod-006-capability--domain--module-map-authoritative) | This is the single authoritative map from business capability to domain to |
| PROD-007 | [New modules introduced by parity](#prod-007-new-modules-introduced-by-parity) | Everything else in parity scope is an extension to an existing module, |
| PROD-008 | [Plan & entitlement model](#prod-008-plan--entitlement-model) | A plan is a named bundle of module entitlements. Entitlements gate module |
| PROD-009 | [Entitlement enforcement](#prod-009-entitlement-enforcement) | A request that targets an unlicensed module is denied with a stable |
| PROD-010 | [Feature-flag semantics](#prod-010-feature-flag-semantics) | Feature flags are declared in manifests and materialized by the kernel. |
| PROD-011 | [Surface entitlement gating](#prod-011-surface-entitlement-gating) | The buyer and partner surfaces are only exposed when the owning module is |
| PROD-012 | [Parity acceptance](#prod-012-parity-acceptance) | Parity means architecture, not imitation: every external feature is |

---


## PROD-001 Product positioning

- id: PROD-001
- status: APPROVED
- owner: Product Owner
- version: 1.0.0
- derived_from: pasted master plan; doc 00 overview
- depends_on: CON-001
- affects: every domain document

Navdhya is a multi-tenant, policy-driven, event-oriented enterprise
operating platform. ERP, CRM, Commerce, POS, Supply Chain, and AI
capabilities are independently composable modules on a shared platform
kernel. One platform, many plans: the same codebase serves an SMB
merchant, a mid-market ERP shop, and an enterprise multi-org buyer by
activating different module sets.

## PROD-002 Scope

- id: PROD-002
- status: APPROVED
- owner: Product Owner
- version: 1.0.0
- derived_from: doc 00 §1; gap analysis §1
- depends_on: PROD-001
- affects: 14-domains

v1 scope is the union of: the commercial core (catalog, customers,
suppliers, pricing, sales, purchasing, inventory, payments, invoicing);
commerce (store, checkout, discounts, shipping, returns, gift cards,
subscriptions, POS, channels); finance (GL, tax, AR, AP, banking, assets,
budgets, reports); enterprise ops (HR, payroll, projects, service,
manufacturing, supply chain); the Phase 2a Shopify-parity surface and the
Phase 4a ERP-parity surface recorded in the feature inventories; and the
authorization/hierarchy capability upgrade.

## PROD-003 Non-goals

- id: PROD-003
- status: APPROVED
- owner: Product Owner
- version: 1.0.0
- derived_from: doc 00 §8; deferral register
- depends_on: PROD-002
- affects: 01-product/gap-analysis

Explicitly out of v1: SPA or native mobile clients; multi-region
active-active; a third-party app runtime inside the platform (Phase 5
ships SDK contracts only); real-time browser streaming; native theme
rendering (deferred, headless storefront API is the contract); full MRP II
/ capacity planning; EDI; recruitment/ATS; performance management; field
service; and customer support portal. Each non-goal carries a preservation
seam in the [deferral register](gap-analysis.md#3-deferral-register).

## PROD-004 Personas

- id: PROD-004
- status: APPROVED
- owner: Product Owner
- version: 1.0.0
- derived_from: feature inventories
- depends_on: PROD-002
- affects: 10-experience

Three primary personas drive plan and surface decisions:

| Persona | Profile | Dominant modules |
|---------|---------|------------------|
| SMB merchant | Single store, small team, commerce-first | store, checkout, payments, catalog, POS |
| Mid-market ERP | Finance + supply chain depth, one legal entity | gl, ap, ar, purchasing, inventory, manufacturing |
| Enterprise multi-org | Multiple orgs, governance, SSO/SCIM, AI | hierarchy, control plane, extensions, intelligence |

## PROD-005 Platform surfaces

- id: PROD-005
- status: APPROVED
- owner: Product Owner
- version: 1.0.0
- derived_from: doc 06 §8; doc 08 §1.5
- depends_on: PROD-004
- affects: 10-experience, 07-kernel/api-platform

v1 ships five surfaces: the Blade admin console (server-rendered,
permission-aware); the `/api/v1` JSON API (contract-first); the POS
terminal surface; the public buyer surface (headless storefront API +
account pages); and the partner/developer surface (Phase 5). Blade and API
converge on the same commands and authorization path.

## PROD-006 Capability → Domain → Module map (authoritative)

- id: PROD-006
- status: APPROVED
- owner: Platform Architect
- version: 1.0.0
- derived_from: gap analysis §1.3 module table
- depends_on: PROD-002
- affects: 14-domains, 08-control-plane

This is the single authoritative map from business capability to domain to
module. Every `NEW-v1` parity row and every module manifest resolves
through it.

| Capability | Domain | Module(s) |
|------------|--------|-----------|
| Sell online | commerce | store, checkout, discounts, shipping, returns, markets, content, customer_accounts |
| Merchandise | commerce | catalog, giftcards, subscriptions (selling plans) |
| Take payment | commerce/finance | payments, giftcards |
| Sell in person | commerce | pos |
| Manage orders | sales | sales, order_ops |
| Procure | procurement | suppliers, purchasing |
| Hold stock | inventory | inventory |
| Bill & collect | finance | invoicing, ar, gl, tax, banking |
| Budget & report | finance | budgets, assets, finance_reports |
| Sell relationships | crm | customers, crm |
| Manage people | hr | hr, payroll, expenses |
| Deliver projects | projects | projects |
| Support customers | service | service |
| Make things | manufacturing | manufacturing, quality |
| Move things | supply-chain | supply_chain |
| Bind contracts | sales | contracts |
| Integrate | integrations | channels (connectors), india_statutory |
| Analyze | analytics | analytics |
| Assist with AI | ai | forecasting, anomaly, recommendations, copilot, agents |
| Extend | extensions | sdk, partners, marketplace, custom_objects |

## PROD-007 New modules introduced by parity

- id: PROD-007
- status: APPROVED
- owner: Platform Architect
- version: 1.0.0
- derived_from: gap analysis §1.3
- depends_on: PROD-006
- affects: 08-control-plane, specs

| Module | Phase | Consumes |
|--------|-------|----------|
| `markets` | 2a | store, pricing, tax, finance (FX) |
| `order_ops` | 2a | sales, invoicing, payments |
| `customer_accounts` | 2a | customers, kernel identity |
| `content` | 2a | store, kernel Files/Search |
| `selling_plans` (ext of `subscriptions`) | 2a | subscriptions, checkout |
| `crm` | 4a | customers, sales |
| `expenses` | 4a | hr, payments, invoicing, gl |
| `quality` | 4a | inventory, purchasing, manufacturing |
| `contracts` | 4a | customers, invoicing, service |

Everything else in parity scope is an extension to an existing module,
preserving module boundaries and extraction rules.

## PROD-008 Plan & entitlement model

- id: PROD-008
- status: APPROVED
- owner: Product Owner
- version: 1.0.0
- derived_from: doc 02 §3 plans + feature flags
- depends_on: PROD-006
- affects: 08-control-plane

A plan is a named bundle of module entitlements. Entitlements gate module
installation, license state, and configuration activation. Feature flags
gate behavior within an installed module. The control plane materializes
plans into tenant blueprints ([CTRL register](../08-control-plane/control-plane.md)).

## PROD-009 Entitlement enforcement

- id: PROD-009
- status: APPROVED
- owner: Platform Architect
- version: 1.0.0
- derived_from: doc 02 §3
- depends_on: PROD-008
- affects: 08-control-plane, 04-security

A request that targets an unlicensed module is denied with a stable
machine-readable code, never silently degraded. Entitlement checks run in
the same fail-closed path as tenancy and authorization.

## PROD-010 Feature-flag semantics

- id: PROD-010
- status: APPROVED
- owner: Platform Architect
- version: 1.0.0
- derived_from: doc 05 §10
- depends_on: PROD-008
- affects: 07-kernel

Feature flags are declared in manifests and materialized by the kernel.
Flag state is part of a module's feature state
(installed/licensed/configured/activated/healthy) in the control plane.
Flag changes are audited.

## PROD-011 Surface entitlement gating

- id: PROD-011
- status: APPROVED
- owner: Platform Architect
- version: 1.0.0
- derived_from: doc 06 §8; doc 08 §1.5
- depends_on: PROD-008
- affects: 10-experience

The buyer and partner surfaces are only exposed when the owning module is
licensed and activated for the tenant. Navigation and public allowlists
reflect entitlement; they never grant it.

## PROD-012 Parity acceptance

- id: PROD-012
- status: APPROVED
- owner: Product Owner
- version: 1.0.0
- derived_from: planning README §2
- depends_on: PROD-006
- affects: 01-product/gap-analysis, specs

Parity means architecture, not imitation: every external feature is
restated as a Navdhya capability obeying the platform principles. A
`NEW-v1` row is accepted only when it appears in the owning domain doc, in
that doc's permission + event summary, and in at least one spec task with
acceptance criteria (no orphan features, no orphan tasks).
