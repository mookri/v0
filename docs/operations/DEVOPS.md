# DevOps

**Status:** Current
**Scope:** DevOps practices, tooling, and operational standards for the Navdhya monorepo.

---

## 1. CI/CD

| Pipeline | File | Purpose | Required check |
| --- | --- | --- | --- |
| CI | `.github/workflows/ci.yml` | Typecheck, lint, unit, contract, architecture, build | `ci-gate` |
| Security | `.github/workflows/security.yml` | Secrets, deps, SAST, supply chain, SBOM | `security-gate` |
| PR hygiene | `.github/workflows/pr-hygiene.yml` | Conventional PR title | `PR title` |
| Nightly | `.github/workflows/nightly.yml` | Coverage, E2E, perf, mutation | `ci/nightly` issue on failure |
| Release | `.github/workflows/release.yml` | Verify, artefacts, attestation, publish, deploy | `verify` |

See [`../CI-CD-PIPELINE-PLAN.md`](../CI-CD-PIPELINE-PLAN.md) for the target design.

---

## 2. Environments

See [`ENVIRONMENTS.md`](ENVIRONMENTS.md) for the full matrix. Summary:

| Environment | Frontend | Backend | Deploy trigger |
| --- | --- | --- | --- |
| Local | ports 3000-3003 | localhost:8000 | Manual |
| Preview | Vercel preview URL | Mock / test backend | PR / push |
| Staging | Partly documented | TBD | Tag + approval |
| Production | www/app/partner | api.navdhya.com | Tag + approval |

---

## 3. Deployment

- Per-app guides: [`../deployment/README.md`](../deployment/README.md)
- Canonical deploy: [`DEPLOYMENT.md`](DEPLOYMENT.md)
- Host deploy scripts: [`../../scripts/deploy/README.md`](../../scripts/deploy/README.md) (not implemented yet)
- Vercel: [`VERCEL.md`](VERCEL.md)
- Backend: [`BACKEND.md`](BACKEND.md)

---

## 4. Infrastructure as code

There is no Terraform, Kubernetes, or Dockerfile in this repository. The current infrastructure is:

- **Frontend:** Vercel projects (per app).
- **Backend:** Host-based (PHP-FPM, MySQL 8, Supervisor/systemd).
- **CI/CD:** GitHub Actions.
- **Secrets:** GitHub Environments + host `.env` (Vault is target, not current).

A container strategy is not yet defined (see `MNC-GRADE-PLAN.md` open decision D6).

---

## 5. Monitoring and observability

See [`MONITORING.md`](MONITORING.md). Key points:

- Health endpoints per app (liveness + readiness).
- Structured JSON logs with `request_id`, `tenant_id`, `user_id`, `client_id`.
- Alert classes: P1 (security/availability), P2 (degraded), P3 (hygiene).
- SLOs: 99.9% API availability, RPO <= 5 min, RTO <= 1 hour.
- No APM/paging provider recorded yet (P0 gap).

---

## 6. Security

See [`SECRETS.md`](SECRETS.md) and [`../SECURITY-AND-THREAT-MODEL.md`](../SECURITY-AND-THREAT-MODEL.md).

- gitleaks (pre-commit + CI) for secret scanning.
- `pnpm audit` for dependency advisories.
- Semgrep OSS, Trivy, SBOM for supply chain.
- CodeQL and dependency review when GHAS is enabled.

---

## 7. Database operations

- Central + tenant-shared MySQL 8 in production.
- SQLite for local/test.
- Migrations are additive only in staging/production.
- `migrate:fresh` is forbidden on shared/e2e databases.
- Backup/PITR targets: RPO <= 5 min, RTO <= 1 hour.

---

## 8. On-call

See [`README.md`](README.md) (operations index) for the on-call first-five-minutes procedure. Escalation: [`ROLLBACK.md`](ROLLBACK.md) and the backend incident runbook.

---

## 9. Tooling

| Tool | Purpose | Location |
| --- | --- | --- |
| Turbo | Monorepo task runner | Root `turbo.json` |
| pnpm | Package manager | `packageManager` field |
| gitleaks | Secret scanning | `.githooks/pre-commit`, CI |
| detect-secrets | Entropy-based scanning | `.githooks/pre-commit` |
| Vitest | Unit/contract/architecture tests | `vitest.config.ts` |
| Jest | Web unit tests | `apps/web/jest.config.js` |
| PHPUnit | Backend tests | `platform-navdhya/phpunit.xml` |
| Playwright | E2E tests | `e2e/` |
| Pint | PHP formatting | `platform-navdhya` |
| PHPStan | Static analysis | `platform-navdhya/phpstan.neon` |
| Deptrac | Dependency rules | `platform-navdhya/deptrac.yaml` |

---

## 10. Related

- [`../MNC-GRADE-PLAN.md`](../MNC-GRADE-PLAN.md) — master plan
- [`DEPLOYMENT.md`](DEPLOYMENT.md) — deployment guide
- [`ENVIRONMENTS.md`](ENVIRONMENTS.md) — environment matrix
- [`MONITORING.md`](MONITORING.md) — observability
- [`SECRETS.md`](SECRETS.md) — secret management
- [`ROLLBACK.md`](ROLLBACK.md) — recovery
- [`../CI-CD-PIPELINE-PLAN.md`](../CI-CD-PIPELINE-PLAN.md) — CI/CD target design
- [`../../scripts/deploy/README.md`](../../scripts/deploy/README.md) — deploy script contract
