# Experience Architecture

**Status:** APPROVED · **Version:** 1.0.0 · **Date:** 2026-08-27
**Derived from:** doc 06 §8; doc 08 §1.5/§10.2; doc 16 §16.2; HIER-006…010; pasted master plan

How the platform is surfaced to humans and partners: the panel model, the navigation contract (navigation reflects authorization; never grants), permission-aware UI rules, the Working Context switcher UX, tenant switching, and Blade↔API convergence. Consumes the hierarchy and authorization registers.

## Register index

| ID | Section | Summary |
|----|---------|---------|
| EXP-001 | [Panel model](#exp-001-panel-model) | v1 ships four panels: **admin** (Blade console for operators and tenant |
| EXP-002 | [Navigation contract](#exp-002-navigation-contract) | **Navigation reflects authorization; it never grants it.** Nav entries are |
| EXP-003 | [Permission-aware UI rules](#exp-003-permission-aware-ui-rules) | UI elements (buttons, actions, fields) render conditionally on capability. |
| EXP-004 | [Fail-closed UI posture](#exp-004-fail-closed-ui-posture) | When authorization or tenant resolution fails, the UI surfaces a clear |
| EXP-005 | [Blade↔API convergence](#exp-005-bladeapi-convergence) | The Blade admin console and the `/api/v1` API converge on the same command |
| EXP-006 | [Public-surface allowlist consolidation](#exp-006-public-surface-allowlist-consolidation) | The unauthenticated (public) surface is a single consolidated allowlist |
| EXP-007 | [Buyer account experience](#exp-007-buyer-account-experience) | Buyer accounts are platform users (kernel identity, LD-04) with a buyer |
| EXP-008 | [POS experience](#exp-008-pos-experience) | The POS panel is optimized for in-store operation: barcode scan, cart, |
| EXP-009 | [Admin console information architecture](#exp-009-admin-console-information-architecture) | The admin console is organized by domain (matching `14-domains/`), with a |
| EXP-010 | [Permission-visible affordances](#exp-010-permission-visible-affordances) | Destructive or sensitive actions (approve, capture, execute, refund, void) |
| EXP-011 | [Working Context switcher UX](#exp-011-working-context-switcher-ux) | A persistent, panel-global **Working Context switcher** shows the current |
| EXP-012 | [Context stack UX](#exp-012-context-stack-ux) | The switcher exposes the context **stack**: the principal can see recent |
| EXP-013 | [Context-scoped defaults](#exp-013-context-scoped-defaults) | Views that are node-scoped (stock, orders, reports) default to the current |
| EXP-014 | [Tenant switching](#exp-014-tenant-switching) | A user with memberships in multiple tenants can switch the active tenant. |
| EXP-015 | [Accessibility & i18n posture](#exp-015-accessibility--i18n-posture) | v1 ships English UI with a translation storage schema in place |
| EXP-016 | [Error and empty-state UX](#exp-016-error-and-empty-state-ux) | Every panel renders the stable error envelope (DF-009) as a human-readable |
| EXP-017 | [Real-time & refresh posture](#exp-017-real-time--refresh-posture) | v1 does not stream to browsers. Panels use webhooks + polling. The UI |
| EXP-018 | [Partner/developer experience (Phase 5)](#exp-018-partnerdeveloper-experience-phase-5) | The partner panel (Phase 5) exposes the SDK, OAuth app management, |
| EXP-019 | [Experience verification](#exp-019-experience-verification) | Standing suites: (a) navigation allowlist matches effective authorization |

---


The experience architecture governs how the platform is surfaced to humans
and partners: the panel model, the navigation contract, permission-aware UI
rules, the Working Context switcher UX, tenant switching, and the
Blade↔API convergence. It consumes the
[hierarchy register](../05-hierarchy/hierarchy-and-working-context.md)
(HIER-006…010) and the
[authorization register](../04-security/authorization-and-policy.md).

## EXP-001 Panel model

- id: EXP-001
- status: APPROVED
- owner: Product Owner
- version: 1.0.0
- derived_from: doc 06 §8; PROD-005
- depends_on: PROD-005
- affects: 10-experience

v1 ships four panels: **admin** (Blade console for operators and tenant
staff), **buyer** (public storefront API + rendered account pages), **POS**
(store terminal surface), and **partner** (developer/ecosystem surface,
Phase 5). Each panel has its own auth surface, navigation shell, and
permission model, but all converge on the same commands and authorization
path.

## EXP-002 Navigation contract

- id: EXP-002
- status: APPROVED
- owner: Platform Architect
- version: 1.0.0
- derived_from: doc 01 §3.2 manifest navigation; CON-005
- depends_on: EXP-001, CTRL-015
- affects: 10-experience

**Navigation reflects authorization; it never grants it.** Nav entries are
declared in module manifests and materialized by the kernel. An entry is
rendered only when the principal holds the capability it requires AND the
owning module is licensed and activated. Hiding a nav item does not relax
any server-side check; every action is re-authorized on the server
regardless of what the UI showed.

## EXP-003 Permission-aware UI rules

- id: EXP-003
- status: APPROVED
- owner: Platform Architect
- version: 1.0.0
- derived_from: doc 04 §8
- depends_on: EXP-002
- affects: 10-experience

UI elements (buttons, actions, fields) render conditionally on capability.
The rule is advisory: the server is the enforcement point. Sensitive fields
respect data-sensitivity classes (security baseline). A UI that exposes an
action the principal cannot perform is a defect; a UI that hides an action
but still permits it server-side is acceptable (defense in depth).

## EXP-004 Fail-closed UI posture

- id: EXP-004
- status: APPROVED
- owner: Platform Architect
- version: 1.0.0
- derived_from: CON-003
- depends_on: EXP-003
- affects: 10-experience

When authorization or tenant resolution fails, the UI surfaces a clear
denial (403/401) with a stable code, never a degraded permissive view.
Unresolved working context that an operation requires prompts a context
selection rather than silently widening scope.

## EXP-005 Blade↔API convergence

- id: EXP-005
- status: APPROVED
- owner: Platform Architect
- version: 1.0.0
- derived_from: doc 06 §8
- depends_on: EXP-001, DF-004
- affects: 10-experience, 07-kernel/api-platform

The Blade admin console and the `/api/v1` API converge on the same command
and query objects (DF-004/DF-005). Blade renders are produced from the same
data the API returns; there is one authorization path and one data shape.
No business logic lives in a Blade template. This makes a future SPA a
surface swap, not a rewrite.

## EXP-006 Public-surface allowlist consolidation

- id: EXP-006
- status: APPROVED
- owner: Platform Architect
- version: 1.0.0
- derived_from: doc 08 §10.2; doc 16 §16.2
- depends_on: DELTA-013
- affects: 10-experience, 19-testing

The unauthenticated (public) surface is a single consolidated allowlist
spanning Phase 2 and Phase 2a additions. It is CI-pinned: any public route
not on the allowlist fails the build. Public routes MUST resolve tenant, be
throttled, and never leak cross-tenant data.

## EXP-007 Buyer account experience

- id: EXP-007
- status: APPROVED
- owner: Product Owner
- version: 1.0.0
- derived_from: doc 16 §5; LD-04
- depends_on: EXP-001, SEC-001
- affects: 10-experience, 14-domains/crm/customers

Buyer accounts are platform users (kernel identity, LD-04) with a buyer
profile. The buyer panel offers self-service: profile, order history,
reorder, wishlists, store credit. Buyer tokens MUST NOT carry admin scopes.
Buyer auth uses session for server-rendered account pages in v1, PKCE for a
future storefront SPA.

## EXP-008 POS experience

- id: EXP-008
- status: APPROVED
- owner: Product Owner
- version: 1.0.0
- derived_from: doc 08 §8; doc 16 §7
- depends_on: EXP-001
- affects: 10-experience, 14-domains/commerce

The POS panel is optimized for in-store operation: barcode scan, cart,
capture, pickup/delivery fulfillment, peripheral printing. POS operations
run under the operator's working context (store/warehouse node). Peripheral
config is terminal-scoped; printing stays local per the channel adapter
rules.

## EXP-009 Admin console information architecture

- id: EXP-009
- status: APPROVED
- owner: Product Owner
- version: 1.0.0
- derived_from: doc 06 §8
- depends_on: EXP-001
- affects: 10-experience

The admin console is organized by domain (matching `14-domains/`), with a
global context switcher, tenant switcher, and search. Information
architecture mirrors the capability→domain→module map (PROD-006) so that
navigation, docs, and code use the same vocabulary.

## EXP-010 Permission-visible affordances

- id: EXP-010
- status: APPROVED
- owner: Platform Architect
- version: 1.0.0
- derived_from: doc 04 §8
- depends_on: EXP-003
- affects: 10-experience

Destructive or sensitive actions (approve, capture, execute, refund, void)
require explicit confirmation and, where the capability is `sensitive`,
step-up authentication (ACR) before the command executes. The UI signals
the step-up requirement before submission.

## EXP-011 Working Context switcher UX

- id: EXP-011
- status: APPROVED
- owner: Product Owner
- version: 1.0.0
- derived_from: HIER-009; pasted master plan
- depends_on: HIER-009, EXP-001
- affects: 10-experience

A persistent, panel-global **Working Context switcher** shows the current
operating node and hierarchy. Switching lists only nodes within the
principal's MaximumAuthority; selecting one pushes a context frame
(HIER-010), emits `platform.context.switched`, and re-scopes open views.
The switcher is the single source of working-context truth in the UI.

## EXP-012 Context stack UX

- id: EXP-012
- status: APPROVED
- owner: Product Owner
- version: 1.0.0
- derived_from: HIER-010
- depends_on: EXP-011, HIER-010
- affects: 10-experience

The switcher exposes the context **stack**: the principal can see recent
frames and pop back to a prior node. Stack frames show node name, hierarchy
type, and scope mode. The stack is bounded; the UI truncates history beyond
the bound.

## EXP-013 Context-scoped defaults

- id: EXP-013
- status: APPROVED
- owner: Platform Architect
- version: 1.0.0
- derived_from: HIER-006…007
- depends_on: EXP-011, HIER-007
- affects: 10-experience

Views that are node-scoped (stock, orders, reports) default to the current
Working Context. A view may offer an explicit node override, but the
override is validated against MaximumAuthority (HIER-008). Views that
require a node and have none prompt selection rather than defaulting to
"all".

## EXP-014 Tenant switching

- id: EXP-014
- status: APPROVED
- owner: Platform Architect
- version: 1.0.0
- derived_from: doc 02 §1 multi-membership
- depends_on: EXP-001, TEN-006
- affects: 10-experience

A user with memberships in multiple tenants can switch the active tenant.
Switching resets the working context (a context is tenant-scoped), re-scopes
navigation, and is audited. Tenant switching never merges data views across
tenants.

## EXP-015 Accessibility & i18n posture

- id: EXP-015
- status: APPROVED
- owner: Product Owner
- version: 1.0.0
- derived_from: doc 16 §6.6 translation storage
- depends_on: EXP-001
- affects: 10-experience

v1 ships English UI with a translation storage schema in place
(content module, [content §6.6](../14-domains/commerce/content.md)); translation tooling is deferred. Surfaces
follow accessible markup practices (semantic Blade templates, keyboard
navigability for core admin flows). Accessibility hardening is a named
post-v1 track.

## EXP-016 Error and empty-state UX

- id: EXP-016
- status: APPROVED
- owner: Platform Architect
- version: 1.0.0
- derived_from: DF-009
- depends_on: EXP-004
- affects: 10-experience

Every panel renders the stable error envelope (DF-009) as a human-readable
message with the machine code and a correlation/request id for support.
Empty states are explicit (never a silent blank), and destructive-action
errors never lose the user's input.

## EXP-017 Real-time & refresh posture

- id: EXP-017
- status: APPROVED
- owner: Platform Architect
- version: 1.0.0
- derived_from: doc 00 §8 (no realtime streaming in v1)
- depends_on: EXP-001
- affects: 10-experience

v1 does not stream to browsers. Panels use webhooks + polling. The UI
communicates staleness (last-updated, refresh) rather than implying live
state. Real-time push is a post-v1 capability with the seam in the API
contracts.

## EXP-018 Partner/developer experience (Phase 5)

- id: EXP-018
- status: APPROVED
- owner: Product Owner
- version: 1.0.0
- derived_from: doc 11
- depends_on: EXP-001
- affects: 18-extensions

The partner panel (Phase 5) exposes the SDK, OAuth app management,
webhook subscriptions, and marketplace listing. It reuses the same identity
and authorization platform; partner apps act as `partner` principals with
scoped tokens.

## EXP-019 Experience verification

- id: EXP-019
- status: APPROVED
- owner: Platform Architect
- version: 1.0.0
- derived_from: doc 14 suites
- depends_on: EXP-001…018
- affects: 19-testing

Standing suites: (a) navigation allowlist matches effective authorization
(no nav-only access, no hidden-but-permitted surprise); (b) public allowlist
pin; (c) working-context switcher lists only in-authority nodes and denies
out-of-authority selection; (d) tenant switch resets context; (e) Blade and
API return the same data shape for a converged command. Each maps to a
named test in [testing strategy](../19-testing/testing-strategy.md).
