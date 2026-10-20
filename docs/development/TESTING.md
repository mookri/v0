# Testing

**Status:** Current
**Scope:** Test layers, commands, CI mapping, test-data rules and known limitations.

The goal is not "more tests"; it is the right gate at the right layer. Fast unit and contract tests run on every PR. Slow full-stack, performance and mutation tests run nightly and are explicitly opt-in.

---

## 1. Test layers

| Layer | Tool | Location | Runs on |
| --- | --- | --- | --- |
| Web unit/component | Jest | `apps/web` | Every PR (`unit-web`) |
| Customer/partner/dashboard unit | Vitest | `apps/{customer,partner,dashboard}` | Every PR (`unit-apps`) |
| Package unit | Vitest | `packages/*` | Every PR (`packages`) |
| API/contract | Vitest | `tests/api` | Every PR (`gates`) |
| Architecture | Vitest + domain manifests | `tests/architecture`, `tooling/scripts/domain-check.js` | Every PR (`gates`, `check:architecture`) |
| Backend unit/integration | PHPUnit | `platform-navdhya/tests` | Backend CI (dormant in monorepo) |
| Full-stack E2E | PowerShell + Playwright | `e2e/` | Nightly, gated by `E2E_ENABLED` |
| API performance | Node script | `e2e/api-perf.mjs` | Nightly, gated by `PERF_ENABLED` |
| Mutation | Stryker (not configured) | — | Nightly, gated by `MUTATION_ENABLED` |
| Security | gitleaks, `pnpm audit`, Semgrep, Trivy, SBOM | `.github/workflows/security.yml` | Every PR + weekly |

---

## 2. Commands

From the repository root:

```sh
pnpm verify                 # typecheck + lint + test (Turbo)
pnpm check:architecture     # architecture tests + domain manifests
pnpm test                   # all package/app tests through Turbo
pnpm lint
pnpm typecheck
```

Targeted:

```sh
pnpm --filter @navdhya/web test
pnpm --filter @navdhya/customer test
pnpm --filter @navdhya/partner-app test
pnpm --filter @navdhya/dashboard test
pnpm exec vitest run tests/api/customer-contract.test.ts
pnpm exec vitest run tests/architecture
```

Backend:

```sh
cd platform-navdhya
php artisan test
./vendor/bin/pint --test
./vendor/bin/phpstan analyse --no-progress --memory-limit=1G
./vendor/bin/deptrac analyse --no-cache
php artisan api:compose --check
php artisan platform:doctor --json
```

Full-stack E2E (Windows; real MySQL, Laravel and BFF):

```powershell
powershell -ExecutionPolicy Bypass -File e2e\run.ps1
powershell -ExecutionPolicy Bypass -File e2e\run.ps1 -HttpOnly
powershell -ExecutionPolicy Bypass -File e2e\smoke.ps1
```

See [`../../e2e/README.md`](../../e2e/README.md) for prerequisites and safety rails.

---

## 3. What each gate protects

### `ci.yml`

| Job | Protects |
| --- | --- |
| `meta` | Changed-app detection and workflow sanity |
| `typecheck` | Type errors in all four apps |
| `unit-apps` | Customer, dashboard and partner behavior |
| `unit-web` | Web/BFF behavior (Jest) |
| `packages` | Shared capability packages |
| `gates` | API contracts, architecture and drift |
| `lint` | ESLint |
| `i18n` | Bundle parity (soft until promoted) |
| `build` | Production build for all four apps; uploads standalone artifacts |
| `ci-gate` | Aggregates the above into one required check |

Build uses `NEXT_STANDALONE=1`. `NEXT_STANDALONE=0` is a Windows local escape hatch and must never appear in CI.

### `security.yml`

| Job | Protects |
| --- | --- |
| `secrets` | gitleaks, diff-aware on PRs and full history on schedule |
| `deps` | Critical advisories in production dependencies |
| `sast` | Semgrep OSS (soft until triage completes) |
| `supply-chain` | SPDX SBOM + Trivy filesystem scan |
| `codeql` | CodeQL (GHAS-gated via `CI_ADVANCED_SECURITY`) |
| `dependency-review` | Dependency review (GHAS-gated) |
| `security-gate` | Aggregates the security ring; skipped conditional jobs pass |

### Nightly

| Job | Gate |
| --- | --- |
| `coverage` | Always runs; Jest coverage for web |
| `e2e` | `E2E_ENABLED=true` **and** `e2e/run.sh` present |
| `perf` | `PERF_ENABLED=true`; runs `node e2e/api-perf.mjs` |
| `mutation` | `MUTATION_ENABLED=true`; requires a Stryker config |
| `report` | Opens/updates the `ci/nightly` tracking issue on failure |

A skipped job is honest. Do not enable a gate until its prerequisite exists; the workflow fails loudly by design.

### Release

`release.yml` reuses `ci.yml` for `verify`, so a tag cannot bypass the PR gate. It then builds artifacts and attests provenance. Deploy is gated separately by `DEPLOY_ENABLED` and environment approval.

---

## 4. Contract and architecture rules

- **BFF paths must resolve to real Laravel routes.** The contract-test allow-list stays empty; do not add an exception to make a test pass.
- **Dependency direction is enforced.** Apps consume packages, not other apps. Packages do not import infrastructure drivers directly.
- **No route ownership drift.** `tests/architecture` and `tooling/scripts/domain-check.js` enforce declared manifests.
- **Additive API changes.** Breaking changes need a versioned contract and a migration plan.

If a rule is wrong, amend the rule with a documented rationale; do not weaken the test silently.

---

## 5. Test data and safety

- **Never run `migrate:fresh` on the e2e databases.** `e2e/run.ps1` provisions only `navdhya_e2e_central` and `navdhya_e2e_tenant`, with an explicit guard; PHPUnit's MySQL lane uses `navdhya_test_*`.
- **Do not write `.env` or the SQLite files during E2E.** Overrides ride process environment variables; the immutable Dotenv repository wins over `.env`.
- **No live MSG91.** Drill mode uses `OTP_LOG_CODES=true` and reads codes from the local log.
- **No live Razorpay.** Use test/fake drivers; webhook verification still runs.
- **No production data** in fixtures, preview, staging or E2E.
- **No real secrets** in test fixtures. `gitleaks` allowlists are for obviously fake placeholders only.
- **Deterministic clocks/IDs** where the test asserts time or ordering; otherwise use the existing test utilities.

---

## 6. Writing tests

- Put unit tests beside the code they cover, following the app/package convention.
- Put cross-app contract tests in `tests/api`; put architecture rules in `tests/architecture`.
- Name tests by behavior, not implementation ("rejects a replayed refresh token", not "calls method X").
- No network calls in unit tests. Stub at the adapter boundary.
- Assert the error envelope code and status, not just "not 200".
- For a bug fix, add the failing test first and keep it after the fix.
- For a contract change, update the Laravel route and the BFF test in the same PR.

---

## 7. Known limitations

| Limitation | Impact |
| --- | --- |
| `e2e/run.sh` is missing | Nightly e2e cannot run on Linux; only the PowerShell runner exists |
| Backend CI is dormant in the monorepo | Backend tests/static analysis are not a GitHub gate until wired |
| Backend `migrate:tenants` is documented but not registered | Tenant migration execution needs confirmation; central `php artisan migrate` is the verified path |
| No Stryker config | `MUTATION_ENABLED` must stay false |
| No branch protection | A red check does not physically block merge on the current plan |
| DPoP key generation can fail on some local OpenSSL builds | Record the environment; do not weaken the test |
| Moderate dependency advisories may be accepted temporarily | Track them in the security review; do not silently ignore criticals |

---

## 8. CI troubleshooting

| Symptom | Check |
| --- | --- |
| `ci-gate` fails but all visible jobs are green | Read `tooling/scripts/ci/gate.mjs` output and the `GATE_*` variables |
| Build passes locally, fails in CI | Node 20, case-sensitive imports, missing env, `NEXT_STANDALONE=1` |
| Customer contract test fails on a route | The Laravel route may be missing or renamed; keep the allow-list empty |
| Backend MySQL lane fails | MySQL service health, both database names, migrations |
| Security gate fails on a skipped job | `security-gate` should treat conditionally skipped GHAS jobs as passes; check `CI_ADVANCED_SECURITY` |
| E2E fails only in nightly | `e2e/run.sh` is missing, or the MySQL service/user is not provisioned |

---

## 9. Related

- [`SETUP.md`](SETUP.md) — local install
- [`../CI-CD-PIPELINE-PLAN.md`](../CI-CD-PIPELINE-PLAN.md) — target pipeline
- [`../../e2e/README.md`](../../e2e/README.md) — full-stack E2E
- [`../operations/DEPLOYMENT.md`](../operations/DEPLOYMENT.md) — release gates
- [`../architecture/dependency-rules.md`](../architecture/dependency-rules.md) — architecture rules
