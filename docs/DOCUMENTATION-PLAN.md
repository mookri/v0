# Documentation Plan

**Status:** Active
**Date:** 2026-10-20
**Owner:** Repository maintainers
**Scope:** Root documentation, deployment and operations, developer onboarding, contribution and community files.

---

## 1. Why this plan exists

The repository has accumulated strong design material — architecture, domain specs, CI plans, threat models and backend runbooks — but the entry points are uneven:

- There was no root documentation index before this batch.
- The root `README.md` described a single v0-generated Next.js app.
- The backend `README.md` was still the Laravel default.
- Deployment knowledge was split between a CI plan, a frontend staging drill, and a backend runbook, with no single operator path.
- Several documents described a **target state** without saying so, which made them unsafe to follow as if they were current.

This plan defines the documentation set, its sources of truth, and the work needed to keep it trustworthy.

---

## 2. Audiences

| Audience | What they need first |
| --- | --- |
| New engineer | Local setup, test commands, architecture boundaries, contribution rules |
| Reviewer | PR expectations, architecture/contract gates, security rules |
| Release operator | Environment matrix, build artefacts, deploy and rollback procedure |
| On-call engineer | Health checks, logs, incident path, rollback |
| Security reviewer | Threat model, security policy, secret handling, reporting channel |
| Product/partner stakeholder | Product plans, API contracts, lifecycle and policy docs |

---

## 3. Documentation principles

1. **Code and tests win.** If a document conflicts with executable behaviour, fix the document or the code — do not silently keep both.
2. **Label the state.** Every operational page says whether it is **Current**, **Target**, or **Historical**.
3. **One canonical page per topic.** Other pages link to it instead of restating it.
4. **No secrets, real customer data, or production identifiers** in docs. Use placeholders and environment-variable names.
5. **Commands must be runnable** from the repository root unless a working directory is stated.
6. **Windows and Linux are both supported for development.** CI and deploy paths are Linux-first.
7. **A change that alters behaviour updates the page that describes it** in the same pull request.

Status labels used across the docs:

| Label | Meaning |
| --- | --- |
| **Current** | Implemented in this repository and safe to follow |
| **Target** | Designed but not fully implemented; do not assume it is live |
| **Historical** | A record of a past drill or decision; not the current procedure |

---

## 4. Current inventory

| Area | Document | Status | Notes |
| --- | --- | --- | --- |
| Entry point | `README.md` | **Current** | Rewritten in this batch |
| Docs index | `docs/README.md` | **Current** | Added in this batch |
| This plan | `docs/DOCUMENTATION-PLAN.md` | **Current** | Added in this batch |
| MNC-grade plan | `docs/MNC-GRADE-PLAN.md` | **Current** | Master MNC-grade plan; links all other docs |
| Per-app deployment | `docs/deployment/*.md` | **Current/Target** | Per-app build, env, health, rollback |
| Application lifecycle | `docs/lifecycle/*.md` | **Current** | Provision through retirement, release, change management |
| CI/CD | `docs/CI-CD-PIPELINE-PLAN.md` | **Target** | Detailed target pipeline; root and backend jobs are not fully wired yet |
| Security | `SECURITY.md` | **Current** | Reporting policy at the repository root |
| Security architecture | `docs/SECURITY-AND-THREAT-MODEL.md` | **Current** | OTP, BFF, session and trust boundaries |
| Architecture | `docs/architecture/*.md` | **Mixed** | Current-state, target-state and migration records; read the page header |
| API | `docs/api/*.md` | **Current/Target** | Contract conventions plus onboarding flows |
| Frontend staging drill | `docs/operations/staging-deploy.md` | **Historical** | 2026-10-01 local drill; not a live staging deployment |
| Cutover | `docs/operations/cutover-runbook.md` | **Target** | Flag-gated strangler cutover; not yet a live traffic procedure |
| Local platform | `docs/operations/local-platform.md` | **Current** | Optional Postgres/Redis for Node adapters |
| Frontend deploy | `docs/operations/DEPLOYMENT.md` | **Current** | Added in this batch; distinguishes Vercel from host release path |
| Environments | `docs/operations/ENVIRONMENTS.md` | **Current** | Added in this batch |
| Vercel | `docs/operations/VERCEL.md` | **Current** | Added in this batch; includes known preview failure state |
| Backend deploy summary | `docs/operations/BACKEND.md` | **Current** | Added in this batch; canonical detail remains the backend runbook |
| Secrets | `docs/operations/SECRETS.md` | **Current** | Added in this batch |
| Monitoring | `docs/operations/MONITORING.md` | **Current** | Added in this batch; alerting target state is marked |
| Rollback | `docs/operations/ROLLBACK.md` | **Current** | Added in this batch |
| Local setup | `docs/development/SETUP.md` | **Current** | Added in this batch |
| Testing | `docs/development/TESTING.md` | **Current** | Added in this batch |
| Environment variables | `docs/development/ENVIRONMENT-VARIABLES.md` | **Current** | Added in this batch |
| Contribution | `CONTRIBUTING.md` | **Current** | Added in this batch |
| Backend entry point | `platform-navdhya/README.md` | **Current** | Rewritten in this batch |
| Backend deploy runbook | `platform-navdhya/docs/22-runbooks/deploy.md` | **Current** | Host-based, no Docker image |
| Backend incident runbook | `platform-navdhya/docs/22-runbooks/incident-response.md` | **Current** | Backend incident path |
| Production environment proposal | `platform-navdhya/docs/22-runbooks/provisioning/PROD-ENV.md` | **Target** | Describes Vault/ECS-style infrastructure that is **not** the current host-based deploy; banner added |
| Backend CI | `platform-navdhya/.github/workflows/ci.yml` | **Target** | Executable specification; dormant until the backend becomes its own repository or the jobs are moved to the root workflow |

---

## 5. Gap register

### P0 — before a production release

| Gap | Why it matters | Action |
| --- | --- | --- |
| `scripts/deploy/staging.sh` and `scripts/deploy/production.sh` do not exist | `release.yml` deliberately fails when deploy is enabled without them | Define the script contract and implement against the real hosts; see `scripts/deploy/README.md` |
| Release tarballs omit `.next/static` and `public` | A host deploy would serve 404s for JS/CSS/assets | Amend `release.yml` packaging or add a hard preflight that refuses an incomplete artefact |
| Staging and production deploy jobs run in parallel | Production can deploy even if staging fails (`fail-fast: false`) | Split into sequential jobs or add an explicit gate/approval |
| Vercel previews are red for `navdhya` and `navdhya-0` | Preview feedback and PR confidence are reduced | Capture the build log with a Vercel login/token, then fix project settings; see `docs/operations/VERCEL.md` |
| Backend CI is not executed by GitHub | The workflow lives under `platform-navdhya/.github/workflows/`, which GitHub does not read from a monorepo | Move/call the backend jobs from the root workflow, or split the backend repository |
| No branch protection or required checks on the private Free plan | A green PR is advisory, not enforced | Enable protection when the plan/repository settings allow it; keep `ci-gate` and `security-gate` as the intended required checks |
| `e2e/run.sh` is missing | The nightly e2e job is gated and cannot run on Linux | Port `e2e/run.ps1` to a Linux entry point, or run the suite in a Linux container |
| Monitoring and alert routing are not documented as a live system | Incidents have no agreed paging path | Name the actual provider, dashboards and escalation path; see `docs/operations/MONITORING.md` |
| Production secret ownership/rotation is not assigned | A leaked or expired secret has no clear responder | Assign owners and record rotation dates in the secret inventory |

### P1 — next documentation pass

| Gap | Action |
| --- | --- |
| API index is a short overview | Add a route family index and generated OpenAPI artefact reference |
| ADR index is missing and ADR numbers repeat | Add `docs/adr/README.md` with canonical numbers and status |
| Package ownership is spread across READMEs | Add a package index with owner, boundary and contract |
| No restore drill for the production database | Add a backup/restore runbook with RPO/RTO and a dated drill |
| No on-call schedule/rota | Add an ownership table and escalation contacts |
| No documentation link checker | Add a cheap CI check for broken relative links |
| Release notes are generated only by GitHub | Add a release checklist that links artefacts, checksums and known issues |

### P2 — continuous improvement

- Generate the environment-variable reference from `.env.example` files and code where practical.
- Add diagrams for the request path and tenancy model.
- Add short package READMEs where they are still placeholders.
- Review historical drill documents and archive them once superseded.

---

## 6. What this batch adds

| File | Purpose |
| --- | --- |
| `docs/README.md` | Single entry point for all documentation |
| `docs/operations/DEPLOYMENT.md` | Canonical end-to-end deployment guide |
| `docs/operations/ENVIRONMENTS.md` | Environment and domain matrix |
| `docs/operations/VERCEL.md` | Vercel project setup and troubleshooting |
| `docs/operations/BACKEND.md` | Frontend/operator summary of the Laravel deploy path |
| `docs/operations/SECRETS.md` | Secret inventory, storage and rotation |
| `docs/operations/MONITORING.md` | Health checks, logs, alerts and gaps |
| `docs/operations/ROLLBACK.md` | Rollback and incident-first recovery |
| `docs/development/SETUP.md` | Local onboarding |
| `docs/development/TESTING.md` | Test layers and CI mapping |
| `docs/development/ENVIRONMENT-VARIABLES.md` | Variable reference and invariants |
| `CONTRIBUTING.md` | Branch, PR, review and security expectations |
| `.github/PULL_REQUEST_TEMPLATE.md` | Repeatable PR checklist |
| `.github/ISSUE_TEMPLATE/*.md` | Bug and feature request templates |
| `platform-navdhya/README.md` | Project-specific backend entry point |
| `scripts/deploy/README.md` | Contract for the missing deploy scripts |
| `docs/MNC-GRADE-PLAN.md` | Master MNC-grade plan |
| `docs/deployment/*.md` | Per-app deployment guides |
| `docs/lifecycle/*.md` | Application lifecycle, release, change management |
| `apps/customer/.env.example` | Corrected `NAVDHYA_API_BASE` to origin-only |
| `platform-navdhya/.env.example` | Added the missing `IDENTITY_HMAC_SECRET` placeholder |
| `docs/operations/staging-deploy.md` | Historical banner and stale-count correction |
| `docs/operations/cutover-runbook.md` | Target-state banner |
| `platform-navdhya/docs/22-runbooks/provisioning/PROD-ENV.md` | Target-state banner (not current host deploy) |
| `platform-navdhya/docs/13-data/data-architecture.md` | Note that `migrate:tenants` is not registered |

---

## 7. Known inconsistencies and how they are handled

1. **Host deploy vs container/Vault proposal.** `release.yml` and the backend deploy runbook are host-based and explicitly say there is no Dockerfile. `PROD-ENV.md` describes a Vault/ECS-style target. The target document now carries a banner and is not presented as current.
2. **Staging drill vs staging environment.** `staging-deploy.md` records a local production-build drill on 2026-10-01, not a deployed staging environment. It is labelled historical.
3. **Root README vs monorepo.** The old README described a single Next.js 15 v0 app. It has been replaced with the four-app monorepo overview.
4. **Backend README vs project.** The backend README was the Laravel default. It has been replaced with Navdhya-specific setup, test and deploy information.
5. **Backend CI location.** `platform-navdhya/.github/workflows/ci.yml` is a valid executable specification but GitHub only reads the root `.github/workflows/`. It is labelled target/dormant.
6. **API base URL example.** `apps/customer/.env.example` previously showed `http://localhost:8000/api/v1` for `NAVDHYA_API_BASE`, but `laravel-client.ts` appends `/api/v1` itself. The comment is corrected to require an origin only.
7. **Vercel status.** Two Vercel projects exist and their preview checks are currently red. The deployment guide documents how to configure and diagnose them without claiming they are fixed.
8. **Missing backend example key.** `platform-navdhya/.env.example` did not list `IDENTITY_HMAC_SECRET` even though the deploy runbook and config require it. A placeholder with the pairing rule was added.
9. **Tenant migration command drift.** `platform-navdhya/docs/13-data/data-architecture.md` documents `php artisan migrate:tenants`, but the command is not registered in the current checkout. The page now carries an implementation note; confirm the real execution path before a deploy.
10. **Incomplete release artefact.** `release.yml` packages only `.next/standalone`, but Next.js also needs `.next/static` and `public`. This is a P0 blocker for any host deploy and is recorded in the deployment guide and script contract.

---

## 8. Ownership and maintenance

| Area | Owner | Review trigger |
| --- | --- | --- |
| Root README, docs index | Repository maintainers | New app/package or changed entry point |
| Deployment and environments | Release owner | Any change to `.github/workflows/release.yml`, hosting or domains |
| Backend deploy | Backend owner | Any change to `platform-navdhya/docs/22-runbooks/deploy.md` or runtime dependencies |
| Security and secrets | Security owner | New secret, provider, or incident |
| Testing | CI owner | New test layer or workflow job |
| Product/domain docs | Domain owner | Contract or lifecycle change |

Cadence:

- **Every release:** update the deployment record and any changed health checks or environment variables.
- **Quarterly:** review this plan, stale banners, ADR index and link health.
- **After every incident:** add or amend the relevant runbook before closing the postmortem.

---

## 9. Success measures

- A new engineer can go from clone to a running app and a green local test using only `docs/development/SETUP.md`.
- An operator can identify the correct deploy path, required approvals and rollback step from `docs/operations/DEPLOYMENT.md` in under five minutes.
- No page presents a target-state system as current.
- No secret, real credential or customer record appears in documentation.
- Every P0 gap has an owner and a linked implementation task.

---

## 10. Open decisions

| Decision | Owner | Needed for |
| --- | --- | --- |
| Production host(s), SSH access and process manager for the Next.js apps | Release owner | Implementing `scripts/deploy/*.sh` |
| Whether production frontends stay on Vercel or move to the host release path | Platform owner | Deployment architecture and rollback |
| Vercel account/token access for build logs | Platform owner | Diagnosing the red previews |
| Live security mailbox | Security owner | **Confirmed:** `hntlsquad@gmail.com` (updated in `SECURITY.md`) |
| Monitoring/alerting provider and paging rota | Operations owner | Making `MONITORING.md` operational rather than advisory |

---

## 11. Next steps

1. Fix release packaging so `.next/static` and `public` are present in the deployable artefact.
2. Implement the deploy-script contract in `scripts/deploy/README.md` for the chosen hosts, and make staging precede production.
3. Restore Vercel preview builds or explicitly disable the Vercel checks in the PR process.
4. Wire backend CI into the root workflow, or split the backend repository.
5. Add the ADR and API indexes.
6. Run the first documentation link check and record the result.
