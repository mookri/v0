# Navdhya Documentation

This is the entry point for repository documentation. It covers the four Next.js applications, the Laravel platform API, shared packages, deployment, security and operations.

> **Source of truth:** executable code, tests and workflows win over prose. If a document conflicts with behaviour, fix the document or the code — do not keep both. Pages are labelled **Current**, **Target** or **Historical**.

---

## Start here

| If you are… | Read |
| --- | --- |
| New to the repository | [`development/SETUP.md`](development/SETUP.md) → [`development/TESTING.md`](development/TESTING.md) → [`CONTRIBUTING.md`](../CONTRIBUTING.md) |
| Making a change | [`CONTRIBUTING.md`](../CONTRIBUTING.md), [`architecture/dependency-rules.md`](architecture/dependency-rules.md), [`SECURITY-AND-THREAT-MODEL.md`](SECURITY-AND-THREAT-MODEL.md) |
| Deploying or releasing | [`operations/DEPLOYMENT.md`](operations/DEPLOYMENT.md) → [`operations/ENVIRONMENTS.md`](operations/ENVIRONMENTS.md) → [`operations/ROLLBACK.md`](operations/ROLLBACK.md) |
| Configuring Vercel | [`operations/VERCEL.md`](operations/VERCEL.md) |
| Deploying the backend | [`operations/BACKEND.md`](operations/BACKEND.md) and [`platform-navdhya/docs/22-runbooks/deploy.md`](../platform-navdhya/docs/22-runbooks/deploy.md) |
| Handling secrets | [`operations/SECRETS.md`](operations/SECRETS.md) |
| On call | [`operations/MONITORING.md`](operations/MONITORING.md) → [`operations/ROLLBACK.md`](operations/ROLLBACK.md) → [`platform-navdhya/docs/22-runbooks/incident-response.md`](../platform-navdhya/docs/22-runbooks/incident-response.md) |
| Reporting a vulnerability | [`SECURITY.md`](../SECURITY.md) |
| Understanding the system | [`architecture/`](architecture/), [`api/overview.md`](api/overview.md), [`SECURITY-AND-THREAT-MODEL.md`](SECURITY-AND-THREAT-MODEL.md) |
| Planning work | [`DOCUMENTATION-PLAN.md`](DOCUMENTATION-PLAN.md), [`CI-CD-PIPELINE-PLAN.md`](CI-CD-PIPELINE-PLAN.md), [`MNC-GRADE-PLAN.md`](MNC-GRADE-PLAN.md), [`PLATFORM-FEATURES-AND-CONSOLE-PLAN.md`](PLATFORM-FEATURES-AND-CONSOLE-PLAN.md) |
| Deploying a specific app | [`deployment/README.md`](deployment/README.md) → [`deployment/web.md`](deployment/web.md), [`customer.md`](deployment/customer.md), [`partner.md`](deployment/partner.md), [`dashboard.md`](deployment/dashboard.md), [`backend.md`](deployment/backend.md) |
| Application lifecycle | [`lifecycle/README.md`](lifecycle/README.md) → [`application-lifecycle.md`](lifecycle/application-lifecycle.md), [`release-management.md`](lifecycle/release-management.md), [`change-management.md`](lifecycle/change-management.md) |

---

## Repository shape

| Path | What it contains |
| --- | --- |
| `apps/web` | Public marketing, discovery, booking and account surfaces (`www.navdhya.com`) |
| `apps/customer` | Authenticated customer workspace (`app.navdhya.com`) |
| `apps/dashboard` | Internal operations dashboard |
| `apps/partner` | Partner/pandit workforce app (`partner.navdhya.com`) |
| `platform-navdhya` | Laravel 12 modular monolith: identity, tenancy, bookings, payments, API |
| `packages/*` | Capability packages consumed by the apps |
| `services/*`, `workers/*` | Independently executable workloads |
| `infrastructure/*` | External-system and platform adapters |
| `tests/*`, `e2e/*` | Architecture, contract, unit and end-to-end tests |
| `.github/workflows` | CI, security, nightly and release pipelines |
| `tooling/*`, `scripts/*` | Developer, CI and operations tooling |

The monorepo boundaries are described in [`architecture/monorepo.md`](architecture/monorepo.md). The target topology and migration status are in [`architecture/target-state.md`](architecture/target-state.md) and [`architecture/current-state.md`](architecture/current-state.md).

---

## Development

| Document | Description |
| --- | --- |
| [`development/SETUP.md`](development/SETUP.md) | Prerequisites, install, environment files, databases, running apps and the backend |
| [`development/TESTING.md`](development/TESTING.md) | Unit, contract, architecture, E2E, performance and security tests; CI mapping |
| [`development/ENVIRONMENT-VARIABLES.md`](development/ENVIRONMENT-VARIABLES.md) | Variable reference, public/server-only split and cross-service invariants |
| [`CONTRIBUTING.md`](../CONTRIBUTING.md) | Branch, commit, PR, review, testing and security expectations |
| [`operations/developer-shortcuts.md`](operations/developer-shortcuts.md) | Commands that mirror CI |

Useful root commands:

```sh
pnpm install
pnpm dev                 # all apps through Turbo
pnpm verify              # typecheck + lint + test
pnpm check:architecture  # architecture gates + domain manifests
```

---

## Architecture and domain design

| Document | Description |
| --- | --- |
| [`architecture/monorepo.md`](architecture/monorepo.md) | Top-level boundaries |
| [`architecture/dependency-rules.md`](architecture/dependency-rules.md) | Allowed dependency directions |
| [`architecture/dependency-graph.md`](architecture/dependency-graph.md) | Package graph |
| [`architecture/current-state.md`](architecture/current-state.md) | What exists today |
| [`architecture/target-state.md`](architecture/target-state.md) | Intended end state |
| [`architecture/multi-tenancy.md`](architecture/multi-tenancy.md) | Tenant model and data separation |
| [`architecture/identity.md`](architecture/identity.md) | Identity and session architecture |
| [`architecture/edge-session.md`](architecture/edge-session.md) | Session handling at the edge/BFF |
| [`architecture/customer-bff.md`](architecture/customer-bff.md) | Customer BFF design |
| [`architecture/customer-route-migration.md`](architecture/customer-route-migration.md) | Route migration record |
| [`architecture/cutover-log.md`](architecture/cutover-log.md) | Append-only traffic-flip evidence |
| [`adr/`](adr/) | Architecture decision records |

Domain and product material lives under `docs/` subdirectories such as [`commerce/`](commerce/), [`finance/`](finance/), [`partner/`](partner/), [`trust/`](trust/), [`support/`](support/), [`customer/`](customer/), [`domains/`](domains/) and [`data/`](data/).

---

## API and contracts

| Document | Description |
| --- | --- |
| [`api/overview.md`](api/overview.md) | BFF vs versioned global API, envelopes, idempotency, auth |
| [`api/partner-onboarding.md`](api/partner-onboarding.md) | Partner onboarding contract |
| [`api/pandit-onboarding.md`](api/pandit-onboarding.md) | Pandit onboarding contract |
| [`api/parity-customers.md`](api/parity-customers.md) | Customer parity record |
| [`api/parity-vendors.md`](api/parity-vendors.md) | Vendor parity record |
| [`auth/laravel-contract.md`](../auth/laravel-contract.md) | Auth contract with the Laravel backend |

Contract rules:

- BFF paths must resolve to real Laravel routes. The contract-test allow-list stays empty.
- Responses use the stable `{ data, requestId }` / `{ error: { code, message, requestId } }` shape.
- Changes are additive; breaking changes require a versioned API and a migration plan.

---

## Security

| Document | Description |
| --- | --- |
| [`SECURITY.md`](../SECURITY.md) | Supported versions and private vulnerability reporting |
| [`SECURITY-AND-THREAT-MODEL.md`](SECURITY-AND-THREAT-MODEL.md) | OTP, BFF, session and trust boundaries |
| [`security/cookie-policy.md`](security/cookie-policy.md) | Cookie and session policy |
| [`operations/SECRETS.md`](operations/SECRETS.md) | Secret inventory, storage and rotation |
| [`trust/policies.md`](trust/policies.md) | Trust and policy material |

Do not open a public issue containing exploit details. Use the private channel in `SECURITY.md`.

---

## Operations and deployment

| Document | Status | Description |
| --- | --- | --- |
| [`operations/DEPLOYMENT.md`](operations/DEPLOYMENT.md) | **Current** | Canonical end-to-end deploy guide |
| [`operations/ENVIRONMENTS.md`](operations/ENVIRONMENTS.md) | **Current** | Local, preview, staging and production matrix |
| [`operations/VERCEL.md`](operations/VERCEL.md) | **Current** | Vercel project settings and troubleshooting |
| [`operations/BACKEND.md`](operations/BACKEND.md) | **Current** | Backend deploy summary and links |
| [`operations/SECRETS.md`](operations/SECRETS.md) | **Current** | Secret management and rotation |
| [`operations/MONITORING.md`](operations/MONITORING.md) | **Current** | Health checks, logs, alerts and gaps |
| [`operations/ROLLBACK.md`](operations/ROLLBACK.md) | **Current** | Frontend/backend rollback and first response |
| [`operations/staging-deploy.md`](operations/staging-deploy.md) | **Historical** | 2026-10-01 local production-build drill |
| [`operations/cutover-runbook.md`](operations/cutover-runbook.md) | **Target** | Flag-gated strangler cutover |
| [`operations/local-platform.md`](operations/local-platform.md) | **Current** | Optional Postgres/Redis for Node adapters |
| [`operations/developer-shortcuts.md`](operations/developer-shortcuts.md) | **Current** | CI-mirroring commands |
| [`operations/admin-operations.md`](operations/admin-operations.md) | **Current** | Admin operational notes |
| [`operations/search/index-rebuild.md`](operations/search/index-rebuild.md) | **Current** | Search index rebuild |
| [`operations/README.md`](operations/README.md) | **Current** | Operations index and on-call path |
| [`operations/DEVOPS.md`](operations/DEVOPS.md) | **Current** | DevOps practices, tooling, and standards |
| [`operations/BACKEND-RCA.md`](operations/BACKEND-RCA.md) | **Current** | Backend RCA: SSO, security, and auth performance (2026-10-21) |
| [`deployment/README.md`](deployment/README.md) | **Current** | Per-application deployment guides |
| [`deployment/web.md`](deployment/web.md) | **Target** | Web app deployment |
| [`deployment/customer.md`](deployment/customer.md) | **Target** | Customer app deployment |
| [`deployment/partner.md`](deployment/partner.md) | **Target** | Partner app deployment |
| [`deployment/dashboard.md`](deployment/dashboard.md) | **Target** | Dashboard app deployment |
| [`deployment/backend.md`](deployment/backend.md) | **Current** | Backend API deployment |
| [`lifecycle/README.md`](lifecycle/README.md) | **Current** | Application lifecycle index |
| [`lifecycle/application-lifecycle.md`](lifecycle/application-lifecycle.md) | **Current** | Provision through retirement |
| [`lifecycle/release-management.md`](lifecycle/release-management.md) | **Current** | Tag-driven releases |
| [`lifecycle/change-management.md`](lifecycle/change-management.md) | **Current** | Change control |
| [`CI-CD-PIPELINE-PLAN.md`](CI-CD-PIPELINE-PLAN.md) | **Target** | CI/CD target design and migration plan |
| [`MNC-GRADE-PLAN.md`](MNC-GRADE-PLAN.md) | **Current** | Master MNC-grade plan |

Release entry points:

- `.github/workflows/ci.yml` — pull-request and main gate.
- `.github/workflows/security.yml` — security scanning and supply chain.
- `.github/workflows/nightly.yml` — slow, non-blocking checks.
- `.github/workflows/release.yml` — tag-driven artefacts, attestations and gated deploy.
- `platform-navdhya/.github/workflows/ci.yml` — backend CI specification (currently dormant; see the file header).

---

## Backend documentation

The backend has its own deep documentation set under [`platform-navdhya/docs/`](../platform-navdhya/docs/):

| Document | Description |
| --- | --- |
| [`platform-navdhya/README.md`](../platform-navdhya/README.md) | Backend entry point |
| [`22-runbooks/deploy.md`](../platform-navdhya/docs/22-runbooks/deploy.md) | Host-based deploy, scheduler and queue worker |
| [`22-runbooks/incident-response.md`](../platform-navdhya/docs/22-runbooks/incident-response.md) | Backend incident response |
| [`22-runbooks/provisioning.md`](../platform-navdhya/docs/22-runbooks/provisioning.md) | Tenant provisioning |
| [`22-runbooks/provisioning/PROD-ENV.md`](../platform-navdhya/docs/22-runbooks/provisioning/PROD-ENV.md) | **Target** production environment proposal (not current host deploy) |
| [`20-sre/sre-operations.md`](../platform-navdhya/docs/20-sre/sre-operations.md) | Alert classes and SRE operations |
| [`09-domain-framework/domain-framework.md`](../platform-navdhya/docs/09-domain-framework/domain-framework.md) | Domain framework |
| [`08-control-plane/control-plane.md`](../platform-navdhya/docs/08-control-plane/control-plane.md) | Control plane |

---

## Product, growth and content

- [`PLATFORM-FEATURES-AND-CONSOLE-PLAN.md`](PLATFORM-FEATURES-AND-CONSOLE-PLAN.md)
- [`PARTNER-FRONTEND-PLAN.md`](PARTNER-FRONTEND-PLAN.md)
- [`seo-strategy.md`](seo-strategy.md)
- [`STATE-MACHINE.md`](STATE-MACHINE.md)
- [`analytics/`](analytics/)
- [`commerce/`](commerce/)
- [`domains/`](domains/)
- [`data/`](data/)

---

## Documentation operations

- The documentation plan and gap register live in [`DOCUMENTATION-PLAN.md`](DOCUMENTATION-PLAN.md).
- A behaviour change should update the page that describes it in the same pull request.
- Prefer links over duplicated procedures.
- Do not put credentials, tokens, customer data or production-only identifiers in docs.
- Add a **Status** line to any new operational page.

For a quick check of this index, every link above is a relative path inside the repository. If a target moves, update this page and the plan together.
