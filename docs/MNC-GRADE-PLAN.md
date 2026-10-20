# Navdhya MNC-Grade Plan

**Status:** Active
**Date:** 2026-10-20
**Owner:** Platform lead
**Scope:** Enterprise-grade engineering, operations, security, and delivery standards for the Navdhya monorepo.

---

## 1. Purpose

This is the master plan for running Navdhya as an enterprise-grade platform. It defines the standards, processes, and artifacts required to operate at MNC grade across development, deployment, operations, security, and compliance. Every other document in this repository either implements a section of this plan or is linked from it.

**Principle:** A process that is not documented, automated, measured, and owned is not a process — it is a hope.

---

## 2. Vision and objectives

### 2.1 Mission

Deliver a reliable, secure, and scalable spiritual-services platform that handles bookings, payments, identity, and partner operations with enterprise-grade availability, data integrity, and user trust.

### 2.2 Objectives

| # | Objective | Measure |
| --- | --- | --- |
| O1 | Zero-downtime production deploys | 100% of production releases use rolling/canary with health-gated promotion |
| O2 | Sub-hour recovery from any single-component failure | RTO <= 1 hour for all P1 incidents |
| O3 | No data loss | RPO <= 5 minutes for all persistent data |
| O4 | Security by default | Zero critical/high vulnerabilities in production; all secrets rotated on exposure |
| O5 | Predictable delivery | Every PR passes CI in < 15 min; releases are tag-driven and reversible |
| O6 | Full observability | Every request traceable end-to-end; every alert has a runbook |
| O7 | Documented ownership | Every component, secret, and process has a named owner |
| O8 | Compliance readiness | SOC 2 / ISO 27001 evidence trail maintained continuously |

### 2.3 Non-goals

- Multi-region active-active (v1 is single-region; HA/DR is single-region with replica).
- Container orchestration (no Dockerfile; host-based deploy until a container strategy is approved).
- Paid bug bounty program (recognition only).

---

## 3. Organizational structure

### 3.1 Teams and ownership

| Team | Owner | Scope |
| --- | --- | --- |
| Platform lead | `@platform-lead` | Cross-cutting decisions, governance, ADRs, this plan |
| Engineering A | `@eng-a` | Auth, security, payments, finops, ops, CI/CD |
| Engineering B | `@eng-b` | Dashboards, UX, content, marketing surfaces |
| Engineering C | `@eng-c` | Directory, search, i18n, infrastructure, backend modules |
| Security owner | `@eng-a` | Threat model, secrets, vulnerability response |
| Release owner | `@eng-c` | Release coordination, deploy scripts, environment matrix |
| Operations owner | TBD | Monitoring, alerting, on-call, incident response |
| Documentation owner | All maintainers | Docs-as-code, link health, ADR index |

### 3.2 CODEOWNERS

High-risk paths require explicit review from the owning engineer (see `.github/CODEOWNERS`):

- `apps/web/lib/auth/`, `apps/web/lib/infra/`, `apps/web/app/api/ads/` → `@eng-a`
- `platform-navdhya/` → backend module owner
- `.github/workflows/` → `@eng-a` + `@platform-lead`
- `docs/operations/`, `docs/development/` → all maintainers

### 3.3 RACI matrix (key processes)

| Process | Responsible | Accountable | Consulted | Informed |
| --- | --- | --- | --- | --- |
| Feature development | Engineer | Tech lead | Reviewer | Product |
| PR review | Reviewer | Tech lead | Owner | — |
| Release | Release owner | Platform lead | Eng A/B/C | All |
| Deploy (staging) | Release owner | Platform lead | — | All |
| Deploy (production) | Release owner | Platform lead | Security | All |
| Incident (P1) | On-call | Platform lead | Security, Eng | All |
| Secret rotation | Security owner | Platform lead | Release owner | Affected teams |
| Schema migration | Backend owner | Platform lead | — | Frontend teams |
| Documentation | Change author | Docs owner | — | All |

---

## 4. Application portfolio

### 4.1 Applications

| App | Package | Port | Origin | Tier | Owner |
| --- | --- | --- | --- | --- | --- |
| Web | `@navdhya/web` | 3000 | `www.navdhya.com` | Public, high traffic | `@eng-b` |
| Customer | `@navdhya/customer` | 3001 | `app.navdhya.com` | Authenticated, high sensitivity | `@eng-a` |
| Partner | `@navdhya/partner-app` | 3002 | `partner.navdhya.com` | Authenticated, workforce | `@eng-c` |
| Dashboard | `@navdhya/dashboard` | 3003 | Private/TBD | Internal, admin | `@eng-b` |
| Backend API | Laravel 12 | 8000 | `api.navdhya.com` | Core, high sensitivity | `@eng-c` |

### 4.2 Shared packages

| Package | Purpose | Consumers |
| --- | --- | --- |
| `@navdhya/config` | Env validation, feature flags | All apps |
| `@navdhya/security` | CSRF, rate-limit, origin checks | All apps |
| `@navdhya/api-envelope` | Response envelopes, request IDs | All apps + BFF |
| `@navdhya/contracts` | Shared TypeScript contracts | Apps + tests |
| `@navdhya/auth` | Session, OIDC, DPoP helpers | Customer, web |
| `@navdhya/telemetry` | Signals, analytics | All apps |

### 4.3 Dependency rules

- Apps consume packages, never other apps.
- Packages do not import infrastructure drivers directly.
- Backend modules communicate through defined contracts, not direct DB access across module boundaries.
- Dependency direction is enforced by `tests/architecture` and `tooling/scripts/domain-check.js`.

---

## 5. Development lifecycle (SDLC)

### 5.1 Phases

```
Idea → Spec → Design → Implement → Test → Review → Merge → Release → Deploy → Monitor → Iterate
```

### 5.2 Standards per phase

| Phase | Artefact | Gate |
| --- | --- | --- |
| Idea | Issue (bug/feature template) | Product/tech triage |
| Spec | ADR or design doc | Architecture review for cross-cutting changes |
| Design | Component/API contract | Reviewer sign-off |
| Implement | Code + tests + docs | Local `pnpm verify` |
| Test | Unit + contract + architecture | CI green |
| Review | PR with conventional title | CODEOWNERS approval |
| Merge | Squash merge to `main` | All required checks green |
| Release | Tag `v*` | Release workflow green |
| Deploy | Environment approval | Smoke checks pass |
| Monitor | 15-min watch window | No SLO regression |
| Iterate | Post-deploy review | Incident postmortem if needed |

### 5.3 Branching strategy

- `main` is the integration branch and the only deployable branch.
- Feature branches: `feat/<scope>`, `fix/<scope>`, `docs/<scope>`, `chore/<scope>`.
- No direct commits to `main`.
- Force-push to shared branches is prohibited.
- Tags are immutable; a bad release gets a new tag.

### 5.4 Commit and PR conventions

- Conventional Commits: `feat`, `fix`, `perf`, `refactor`, `docs`, `test`, `build`, `ci`, `chore`, `revert`.
- Subject is imperative and lower-case.
- PR title enforced by `pr-hygiene` workflow.
- PR template must be completed (testing, deployment, rollback, checklist).

### 5.5 Definition of done

- [ ] Change is scoped and reversible.
- [ ] Tests cover behavior and failure mode.
- [ ] `pnpm verify` and `pnpm check:architecture` pass.
- [ ] Backend checks pass if backend changed.
- [ ] No secret, token, customer record, or production identifier added.
- [ ] Docs/runbooks updated for behavior, config, routes, or operations.
- [ ] PR title is conventional; template complete.
- [ ] Required checks green (or exception explicit and approved).
- [ ] Rollback path known for deployable changes.

---

## 6. Per-application deployment

Each application has its own deployment guide. See `docs/deployment/`:

| App | Guide | Status |
| --- | --- | --- |
| Web | `docs/deployment/web.md` | Target — host deploy not implemented |
| Customer | `docs/deployment/customer.md` | Target — host deploy not implemented |
| Partner | `docs/deployment/partner.md` | Target — host deploy not implemented |
| Dashboard | `docs/deployment/dashboard.md` | Target — host deploy not implemented |
| Backend API | `docs/deployment/backend.md` | Current — host-based procedure exists |

### 6.1 Common deployment principles

1. **One artefact per app.** Each app builds independently; no shared build.
2. **Versioned releases.** Every deploy uses a tagged release and a versioned directory.
3. **Health-gated promotion.** Traffic switches only after `/healthz` and `/readyz` pass.
4. **Rollback is a release.** Restoring a previous release follows the same verification as a deploy.
5. **No in-place overwrite.** Always unpack to a new directory and switch a symlink.
6. **Secrets are not in the artefact.** Runtime secrets live in the host environment or secret manager.
7. **Migrations are additive.** Backend schema changes are forward-only in staging/production.
8. **Backend and frontend deploy separately.** Compatibility rule: deploy backend-compatible code before frontend code that depends on it.

### 6.2 Deployment environments

| Environment | Purpose | Approval | Data |
| --- | --- | --- | --- |
| Local | Development | None | Local fixtures |
| Preview | PR review | PR author | Mock mode only |
| Staging | Release rehearsal | GitHub `staging` | Synthetic test tenant |
| Production | Live | GitHub `production` (reviewers + wait timer) | Real data |

### 6.3 Promotion path

```
feature branch
  └─ PR checks (ci.yml + security.yml)
       └─ Vercel preview (when available)
            └─ merge to main
                 └─ staging release (tag + approval)
                      └─ smoke + soak
                           └─ production release (tag + approval)
                                └─ smoke + 15-min watch
```

---

## 7. Application lifecycle

### 7.1 Lifecycle stages

```
Provision → Configure → Deploy → Monitor → Scale → Update → Retire
```

### 7.2 Stage definitions

| Stage | Activities | Exit criteria |
| --- | --- | --- |
| **Provision** | Create environment, DNS, TLS, database, secret store | Health endpoint reachable |
| **Configure** | Set environment variables, feature flags, tenant context | `platform:doctor --json` passes |
| **Deploy** | Build, package, verify checksums, unpack, switch, restart | Smoke checks pass |
| **Monitor** | Health checks, logs, alerts, SLO tracking | 15-min clean window |
| **Scale** | Add nodes, increase DB capacity, tune workers | SLOs maintained under load |
| **Update** | Patch, minor, major version upgrades | Regression tests pass; changelog updated |
| **Retire** | Drain traffic, archive data, revoke secrets, remove DNS | No references remain; postmortem filed |

### 7.3 Application lifecycle document

See `docs/lifecycle/application-lifecycle.md` for the detailed procedure per stage.

### 7.4 Release management

- Tags are `v<major>.<minor>.<patch>` (semver).
- `release.yml` verifies the commit through `ci.yml` before building.
- Artefacts are attested with SLSA build provenance.
- Deploy is gated by `DEPLOY_ENABLED` and environment approval.
- Release notes are generated from conventional commit history.
- See `docs/lifecycle/release-management.md`.

### 7.5 Change management

- All changes go through PR review.
- High-risk changes (auth, payments, tenancy, deploy workflows) require security/operations review.
- Breaking API changes require a versioned contract and migration plan.
- Schema changes require a compatibility plan (expand → contract).
- See `docs/lifecycle/change-management.md`.

---

## 8. Monitoring and observability

### 8.1 Health checks

Every deployable surface exposes liveness and readiness endpoints. See `docs/operations/MONITORING.md`.

### 8.2 Logging

- Structured JSON with `request_id`, `tenant_id`, `user_id`, `client_id`.
- No secrets, tokens, or customer PII in logs.
- Retention: 30 days minimum for application logs.

### 8.3 Alerting

Alert classes: P1 (security/availability), P2 (degraded), P3 (hygiene). Every alert has a runbook. See `docs/operations/MONITORING.md` and the backend SRE doc.

### 8.4 SLOs

| Surface | SLO | Target |
| --- | --- | --- |
| API availability | 99.9% monthly | <= 43 min downtime |
| Token issuance p95 | 300 ms | |
| Simple read p95 | 120 ms | |
| List/search p95 | 250 ms | |
| Write p95 | 300 ms | |
| Complex operation p95 | 1.5 s | |
| RPO | <= 5 min | |
| RTO | <= 1 hour | |

Frontend SLOs are not yet defined; add after a measurement baseline.

### 8.5 Dashboards

Minimum manual dashboard until a provider is chosen:
- CI gate status
- Vercel deployment status
- Backend `/up`, `/ready`, JWKS, OAuth discovery
- Queue depth and scheduler last-run
- Database health and backup age

---

## 9. Incident management

### 9.1 Severity ladder

| Severity | Definition | Response |
| --- | --- | --- |
| P1 | Platform down, data breach, tenant isolation failure | Page immediately; 15-min ack; war room |
| P2 | Degraded service, credible threat | Business-hours ops; 4-hour ack |
| P3 | Hygiene, warnings | Dashboard; weekly review |

### 9.2 Incident flow

1. Detect → 2. Declare severity → 3. Mitigate (rollback/flag-off/failover) → 4. Communicate → 5. Preserve evidence → 6. Resolve → 7. Postmortem.

### 9.3 Postmortem

- Required for all P1 incidents within 5 business days.
- Blameless; focus on system and process.
- Action items tracked to completion.
- Runbook updated before closing.

See `docs/operations/ROLLBACK.md` and `platform-navdhya/docs/22-runbooks/incident-response.md`.

---

## 10. Disaster recovery

### 10.1 Architecture

- Single-region with MySQL primary + async replica.
- Stateless app tier (n >= 2 nodes behind load balancer).
- Redis for cache/queue/session.
- Object storage (local disk default; S3-compatible optional).

### 10.2 Targets

- RPO: <= 5 minutes (binlog PITR).
- RTO: <= 1 hour (primary failover + app redeploy).
- Availability: 99.9% monthly.

### 10.3 Restore drills

- Quarterly restore drill required.
- Record actual RPO/RTO and compare to targets.
- Update runbooks if gaps found.

See `platform-navdhya/docs/20-sre/ha-dr.md`.

---

## 11. Security

### 11.1 Principles

- Least privilege everywhere.
- Fail closed on missing secrets.
- No secrets in code, logs, or docs.
- Public variables are build-time and never sensitive.
- One credential per environment.

### 11.2 Secret management

See `docs/operations/SECRETS.md` for inventory, storage, rotation, and leak response.

### 11.3 Vulnerability management

- gitleaks (pre-commit + CI) for secret scanning.
- `pnpm audit` for dependency advisories.
- Semgrep OSS for SAST.
- Trivy for filesystem/container scanning.
- SBOM generation for supply chain.
- CodeQL and dependency review when GHAS is enabled.
- Critical findings block merge; high findings require triage within 7 days.

### 11.4 Vulnerability reporting

See `SECURITY.md` for the reporting policy and channels.

---

## 12. Compliance and governance

### 12.1 Targets

- OWASP ASVS Level 2.
- SOC 2 / ISO 27001 evidence readiness via audit log.
- Tenant isolation verified by architecture tests.
- Audit trail for all sensitive actions (actor, action, target, reason, timestamp).

### 12.2 Evidence

- Audit logs retained per compliance requirements.
- CI/security scan results retained per workflow retention.
- Release attestations (SLSA provenance) for all artefacts.
- Migration records in `tenant_migrations` table.

### 12.3 Data governance

- No production data in preview/staging/E2E.
- Tenant data separation enforced at the database connection level.
- Privacy erasure tooling exists (`tooling/scripts/privacy-erasure.ts`).
- Data retention policies defined per domain.

---

## 13. Capacity planning

### 13.1 Design horizon

- 1,000 tenants on shared DB before first dedicated-mode promotions.
- 50 requests/s sustained per web node; horizontal scale-out beyond.
- 10M audit rows per month with partition/archival.
- 1M products, 10M inventory movement rows per year on shared tenant DB.

### 13.2 Scaling triggers

- CPU > 70% sustained for 10 min → add node.
- DB connections > 80% of pool → increase pool or add replica.
- Queue depth > baseline for 10 min → add worker.
- Response time p95 > 2x target → investigate.

### 13.3 Cost management

- Monthly review of infrastructure costs.
- Right-size resources based on actual usage.
- Use reserved instances/savings plans for steady-state workloads.

---

## 14. Knowledge management

### 14.1 Documentation standards

- Every operational page has a **Status** line: Current, Target, or Historical.
- One canonical page per topic; link, don't duplicate.
- Code and tests win over prose.
- A behavior change updates the page that describes it in the same PR.
- Relative links only; no absolute paths.
- No secrets, credentials, or customer data in docs.

### 14.2 Documentation structure

```
docs/
├── README.md                    # Master index
├── DOCUMENTATION-PLAN.md        # Plan and gap register
├── MNC-GRADE-PLAN.md           # This file
├── architecture/                # System design
├── api/                         # API contracts
├── deployment/                  # Per-app deployment guides
│   ├── README.md
│   ├── web.md
│   ├── customer.md
│   ├── partner.md
│   ├── dashboard.md
│   └── backend.md
├── lifecycle/                   # Application lifecycle
│   ├── README.md
│   ├── application-lifecycle.md
│   ├── release-management.md
│   └── change-management.md
├── development/                 # Developer onboarding
│   ├── SETUP.md
│   ├── TESTING.md
│   └── ENVIRONMENT-VARIABLES.md
├── operations/                  # Operations and runbooks
│   ├── README.md
│   ├── DEPLOYMENT.md
│   ├── ENVIRONMENTS.md
│   ├── VERCEL.md
│   ├── BACKEND.md
│   ├── SECRETS.md
│   ├── MONITORING.md
│   └── ROLLBACK.md
├── security/                    # Security architecture
└── ... (domain-specific docs)
```

### 14.3 ADR index

Architecture decisions are recorded in `docs/adr/`. Each ADR has a number, title, status, and date. The index lives at `docs/adr/README.md`.

### 14.4 Review cadence

- **Every release:** update deployment record and changed health checks/env vars.
- **Quarterly:** review this plan, stale banners, ADR index, link health.
- **After every incident:** amend the relevant runbook before closing the postmortem.

---

## 15. Metrics and KPIs

### 15.1 Engineering

| Metric | Target | Frequency |
| --- | --- | --- |
| PR cycle time (open to merge) | < 2 days | Weekly |
| CI pass rate | > 95% | Weekly |
| Test coverage (web) | > 80% | Per PR |
| Build time (all apps) | < 15 min | Per PR |
| Defect escape rate | < 5% | Monthly |
| Documentation freshness | < 30 days stale | Quarterly |

### 15.2 Operations

| Metric | Target | Frequency |
| --- | --- | --- |
| Uptime (API) | 99.9% | Monthly |
| MTTR (P1) | < 1 hour | Per incident |
| Deploy frequency | Weekly | Weekly |
| Rollback rate | < 5% | Monthly |
| Incident count (P1) | 0 | Monthly |

### 15.3 Security

| Metric | Target | Frequency |
| --- | --- | --- |
| Critical vulnerabilities open | 0 | Weekly |
| High vulnerabilities open | 0 (or triaged within 7 days) | Weekly |
| Secret rotation compliance | 100% | Quarterly |
| Dependency freshness | < 30 days behind | Monthly |

---

## 16. Roadmap and phases

### Phase 0 — Foundation (done)

- [x] Monorepo structure
- [x] CI pipeline (smoke-shaped)
- [x] Security ring (free substitutes)
- [x] Documentation plan and index
- [x] MNC-grade plan (this document)

### Phase 1 — Deployment readiness

- [ ] Fix release artifact packaging (static assets)
- [ ] Implement deploy scripts
- [ ] Restore Vercel preview builds
- [ ] Wire backend CI into root workflow
- [ ] Port `e2e/run.sh` to Linux
- [ ] Add branch protection (when plan allows)

### Phase 2 — Observability

- [ ] Choose APM/metrics/paging provider
- [ ] Implement alerting (suggested first alerts)
- [ ] Fix scheduler output (no more `/dev/null`)
- [ ] Define frontend SLOs after baseline
- [ ] Record first backup restore drill

### Phase 3 — Compliance

- [ ] OWASP ASVS Level 2 self-assessment
- [ ] SOC 2 evidence collection
- [ ] Tenant isolation verification drill
- [ ] Secret rotation automation

### Phase 4 — Scale

- [ ] Load testing and capacity validation
- [ ] Horizontal scaling rehearsal
- [ ] Database partitioning strategy
- [ ] CDN and caching layer

---

## 17. Open decisions

| # | Decision | Owner | Needed for |
| --- | --- | --- | --- |
| D1 | Production host(s), SSH access, process manager | Release owner | Deploy scripts |
| D2 | Whether production frontends stay on Vercel or move to host path | Platform owner | Deployment architecture |
| D3 | Vercel account/token access for build logs | Platform owner | Diagnosing red previews |
| D4 | Live security mailbox | Security owner | **Confirmed:** `hntlsquad@gmail.com` (see `SECURITY.md`) |
| D5 | Monitoring/alerting provider and paging rota | Operations owner | Observability |
| D6 | Container strategy (if any) | Platform owner | Phase 4 |
| D7 | Multi-region timeline | Platform owner | Phase 4 |

---

## 18. Related documents

- `docs/README.md` — documentation index
- `docs/DOCUMENTATION-PLAN.md` — documentation plan and gap register
- `docs/operations/DEPLOYMENT.md` — deployment guide
- `docs/operations/ENVIRONMENTS.md` — environment matrix
- `docs/operations/VERCEL.md` — Vercel setup
- `docs/operations/BACKEND.md` — backend operations
- `docs/operations/SECRETS.md` — secret management
- `docs/operations/MONITORING.md` — observability
- `docs/operations/ROLLBACK.md` — recovery
- `docs/development/SETUP.md` — local setup
- `docs/development/TESTING.md` — testing
- `docs/development/ENVIRONMENT-VARIABLES.md` — variable reference
- `CONTRIBUTING.md` — contribution rules
- `SECURITY.md` — security policy
- `docs/CI-CD-PIPELINE-PLAN.md` — CI/CD target design
- `platform-navdhya/docs/22-runbooks/deploy.md` — backend deploy runbook
- `platform-navdhya/docs/20-sre/sre-operations.md` — SRE targets
- `platform-navdhya/docs/20-sre/ha-dr.md` — HA/DR design

---

## 19. Glossary

| Term | Definition |
| --- | --- |
| **BFF** | Backend-for-Frontend; Next.js route handlers that proxy to Laravel |
| **Artefact** | A versioned, checksummed, attested build output |
| **RPO** | Recovery Point Objective; maximum acceptable data loss |
| **RTO** | Recovery Time Objective; maximum acceptable downtime |
| **SLO** | Service Level Objective; a target for a measurable metric |
| **SLI** | Service Level Indicator; the actual measurement |
| **P1/P2/P3** | Incident severity levels |
| **Strangler** | Gradual migration pattern using flag-gated redirects |
| **Expand-contract** | Migration pattern: add new, migrate, remove old |
| **Host-based deploy** | Deploying a Node process directly on a server, no container |
| **Dormant CI** | A workflow that is specified but not executed by GitHub |

---

## 20. Revision history

| Date | Change | Author |
| --- | --- | --- |
| 2026-10-20 | Initial MNC-grade plan | Platform lead |
