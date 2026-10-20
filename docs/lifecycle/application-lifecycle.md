# Application Lifecycle

**Status:** Current (host deployment steps are **Target**)
**Scope:** Managing applications from provisioning through retirement.

---

## 1. Lifecycle stages

```
Provision → Configure → Deploy → Monitor → Scale → Update → Retire
```

---

## 2. Stage definitions

### 2.1 Provision

**Goal:** Create the environment, infrastructure, and access needed to run the application.

| Activity | Owner | Artefact |
| --- | --- | --- |
| Create environment (local/preview/staging/production) | Release owner | Environment matrix entry |
| Provision DNS and TLS | Release owner | DNS record, certificate |
| Provision database | Backend owner | Database + user with least privilege |
| Provision secret store | Security owner | Secret inventory entry |
| Create process manager unit | Release owner | systemd/supervisor config |
| Create monitoring endpoints | Operations owner | Health check URL |

**Exit criteria:** Health endpoint is reachable and returns 200.

### 2.2 Configure

**Goal:** Set all environment variables, feature flags, and tenant context.

| Activity | Owner | Artefact |
| --- | --- | --- |
| Set environment variables | Release owner | Env var checklist |
| Set feature flags | Platform lead | Flag registry |
| Provision tenant (if applicable) | Backend owner | Tenant ULID |
| Run `platform:doctor --json` | Backend owner | Doctor report |
| Verify secret pairing (session signing) | Security owner | Verification record |

**Exit criteria:** `platform:doctor --json` passes; all required env vars present.

### 2.3 Deploy

**Goal:** Build, package, and deploy the application to the target environment.

See [`../deployment/README.md`](../deployment/README.md) and [`../operations/DEPLOYMENT.md`](../operations/DEPLOYMENT.md).

| Activity | Owner | Artefact |
| --- | --- | --- |
| Build with `NEXT_STANDALONE=1` | CI / Release owner | Build log |
| Package standalone output | CI | Tarball + checksum |
| Attest provenance | CI | SLSA attestation |
| Verify checksums | Deploy script | Checksum verification log |
| Unpack to versioned directory | Deploy script | Release directory |
| Switch symlink | Deploy script | Symlink target |
| Restart process | Deploy script | Process restart log |
| Smoke health endpoints | Deploy script | Smoke check results |

**Exit criteria:** All smoke checks pass; 15-minute clean watch window.

> **Target note:** Steps 4-8 (verify checksums, unpack, switch symlink, restart, smoke) depend on deploy scripts that are not implemented yet. Steps 1-3 (build, package, attest) are implemented in CI.

### 2.4 Monitor

**Goal:** Continuously observe application health, performance, and errors.

| Activity | Owner | Artefact |
| --- | --- | --- |
| Health checks (liveness + readiness) | Operations | Health check config |
| Log aggregation | Operations | Log retention policy |
| Alert rules | Operations | Alert definitions |
| SLO tracking | Operations | SLO dashboard |
| Incident response | On-call | Incident records |

**Exit criteria:** All alerts routed; dashboards populated; runbooks linked.

### 2.5 Scale

**Goal:** Increase capacity to meet demand without degrading SLOs.

| Trigger | Action | Owner |
| --- | --- | --- |
| CPU > 70% sustained 10 min | Add node | Operations |
| DB connections > 80% pool | Increase pool or add replica | Backend owner |
| Queue depth > baseline 10 min | Add worker | Backend owner |
| Response time p95 > 2x target | Investigate and optimize | Engineering |
| Storage > 80% capacity | Expand storage | Operations |

**Exit criteria:** SLOs maintained under increased load; cost within budget.

### 2.6 Update

**Goal:** Apply patches, minor, and major version upgrades safely.

| Activity | Owner | Artefact |
| --- | --- | --- |
| Dependency updates | Engineer | PR with changelog |
| Security patches | Security owner | Security advisory |
| Major version upgrades | Tech lead | ADR + migration plan |
| Framework upgrades | Platform lead | Upgrade runbook |

**Exit criteria:** All tests pass; no SLO regression; changelog updated.

### 2.7 Retire

**Goal:** Safely decommission an application or environment.

| Activity | Owner | Artefact |
| --- | --- | --- |
| Drain traffic (flag-off or DNS change) | Release owner | Traffic drain record |
| Archive data | Backend owner | Backup archive |
| Revoke secrets | Security owner | Revocation record |
| Remove DNS | Release owner | DNS change record |
| Remove monitoring | Operations | Alert cleanup |
| File postmortem | Platform lead | Postmortem document |

**Exit criteria:** No references remain; postmortem filed; lessons captured.

---

## 3. Environment lifecycle

Each environment (local, preview, staging, production) follows a subset of the application lifecycle:

| Stage | Local | Preview | Staging | Production |
| --- | --- | --- | --- | --- |
| Provision | Developer | CI | Release owner | Release owner |
| Configure | Developer | CI | Release owner | Release owner |
| Deploy | Developer | CI (auto) | Manual approval | Manual approval |
| Monitor | Developer | CI | Operations | Operations |
| Scale | N/A | N/A | Operations | Operations |
| Update | Developer | CI | Release owner | Release owner |
| Retire | Developer | CI (auto) | Release owner | Platform lead |

---

## 4. Tenant lifecycle

Tenants follow their own lifecycle within the platform:

```
provisioned → active ⇄ suspended → offboarded
(promoting is a transient state during plan/blueprint changes)
```

| State | Description | Transition |
| --- | --- | --- |
| provisioned | Tenant record created; schema applied | → active on success |
| active | Tenant is operational | ⇄ suspended on payment failure or admin action; → offboarded on termination |
| suspended | Tenant access blocked; data retained | → active on payment resolution |
| offboarded | Tenant terminated; data retained per compliance, then purged | Terminal state |
| promoting | Transient state during plan/blueprint changes | → active on success |

See `platform-navdhya/docs/22-runbooks/provisioning.md` for the provisioning pipeline.

---

## 5. Related

- [`release-management.md`](release-management.md) — release process
- [`change-management.md`](change-management.md) — change control
- [`../deployment/README.md`](../deployment/README.md) — per-app deployment
- [`../operations/DEPLOYMENT.md`](../operations/DEPLOYMENT.md) — canonical deploy guide
- [`../operations/ROLLBACK.md`](../operations/ROLLBACK.md) — recovery
- [`../MNC-GRADE-PLAN.md`](../MNC-GRADE-PLAN.md) — master plan
