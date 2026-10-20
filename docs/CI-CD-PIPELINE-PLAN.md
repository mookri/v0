# CI/CD Pipeline Plan — GitHub Actions

**Status:** Proposed (audited against the working tree)
**Date:** 2026-10-20
**Scope:** repository-root CI (4 Next.js apps, 45 packages, architecture gates) + Laravel kernel CI (`platform-navdhya/`)
**Owner:** `@platform-lead` (governance), `@eng-a` (security/infra), `@eng-c` (delivery)
**Related:** `docs/SECURITY-AND-THREAT-MODEL.md`, `docs/operations/staging-deploy.md`,
`platform-navdhya/docs/22-runbooks/deploy.md`, `platform-navdhya/docs/19-testing/`,
ADR-0020 (unified auth), ADR-0023 (fail-closed revocation), ADR-0024…0027 (search).

> Everything in §1 was read or executed in this working tree. Where a number is a
> **target** rather than a measurement it is labelled *target*. No gate is claimed
> to be green here unless it was run.

---

## 0. TL;DR

The repo has a CI file, but it is a **smoke-shaped** pipeline: one lint/typecheck/test
job, one architecture job, gitleaks, one build. It blocks on nothing it should block on,
runs on branches nobody pushes to, cannot see the backend at all, and would fail on the
first step if it ever ran (`next lint` no longer exists in Next 16).

This plan replaces it with a **five-ring gate ladder** (bugs → quality → security →
reliability → release), each ring with a named aggregator check that is safe to mark
*required* in branch protection, and a **ratchet** mechanism so gates can ship before
the codebase is clean without ever being silently bypassed (`|| true` / blanket
`continue-on-error` are banned by policy, §10).

Two structural items dominate everything else:

* **F1 — the backend is invisible to CI.** `platform-navdhya/` is gitignored and is a
  standalone git repo with no remote. Root CI can never test it. **(resolved 2026-10-20:
  merged into this repo with history; D1 = option B)**
* **F2 — lint is dead across all four apps.** `next lint` was removed in the installed
  Next 16.0.10 and `eslint` is not installed anywhere. **(fixed in P0, 2026-10-20:**
  ESLint 9 + `eslint-config-next@16.0.10`, `pnpm run lint` blocking at 0 errors**)**

Neither is fixed by writing YAML. They are P0/P1 workstreams with exit criteria (§14).

---

## 1. Evidence — current state audit

| # | Verified fact | How verified | Impact |
|---|---|---|---|
| E1 | `platform-navdhya/` is listed in `.gitignore` and `git ls-files platform-navdhya` returns **0** files | `git ls-files`, `.gitignore` | Root CI cannot compile/test the API, migrations, or Laravel architecture tests |
| E2 | `platform-navdhya/.git` exists; `git -C platform-navdhya remote -v` is empty | git | Backend has no remote, no PRs, no review, no CI history |
| E3 | `platform-navdhya/.github/workflows/ci.yml` exists but is explicitly a *specification* ("workflows execute from the repo-root `.github` only") | file header | The backend gates have **never executed** |
| E4 | `apps/*/package.json` `lint` = `next lint`; Next is `16.0.10`; `node_modules/next/dist/cli/next-lint.js` is absent | `pnpm --filter @navdhya/web run lint` → `Invalid project directory … \apps\web\lint` | `turbo run lint` and therefore `pnpm run verify` fail for all 4 apps |
| E5 | `eslint` is not installed (`node_modules/eslint` missing) although `eslint.config.mjs` exists | `require.resolve('eslint/package.json')` | The flat config is dead code; no import-boundary lint rule is enforced |
| E6 | `ci.yml` triggers only on `[main, minus]`; the active branch is `architecture/phase-3-web-extraction` | `.github/workflows/ci.yml`, `git branch` | None of the current work has ever been CI'd |
| E7 | `ci.yml` sets `actions/setup-node` `cache: npm` but installs with **pnpm**; a stale root `package-lock.json` also exists | `ci.yml:31,34` | Cache saves nothing; two lockfiles can drift |
| E8 | `ci.yml` has no `permissions:` block; actions are tag-pinned, not SHA-pinned | `ci.yml` | Default token permissions; supply-chain surface |
| E9 | No `merge_group` trigger anywhere | repo search | Merge queue cannot be enabled |
| E10 | Build uploads **only** `apps/web/.next/standalone` | `ci.yml:105-110` | customer / partner / dashboard builds are unverified artifacts |
| E11 | `e2e/playwright.config.ts` `testMatch: /ui\.spec\.ts/` | file | `e2e/console.spec.ts` (21 console sections) **never runs** |
| E12 | E2E orchestration is Windows-only PowerShell (`e2e/run.ps1`, MySQL at `C:\Program Files\…`) | file | Cannot run on `ubuntu-latest` without a port |
| E13 | No `Dockerfile` anywhere; `docker-compose.yml` has only postgres + redis | glob + file | No image build, no container scan, no image-based deploy target |
| E14 | Security coverage = gitleaks only (no SCA, SAST, SBOM, provenance, licence, dependency review) | `ci.yml` | Security ring is 1/7 implemented |
| E15 | 123 architecture test files, 64 jest files in `apps/web`, 33 package test files, no coverage report is collected | `Get-ChildItem` counts | No coverage baseline, no ratchet possible |
| E16 | `phpstan.neon` level 6 + 16-entry baseline; `pint.json` psr12; `deptrac.yaml` layers exist | files | Backend static gates are fully specified and ready to run |
| E17 | `phpunit.xml` pins SQLite `:memory:` for both tenancy connections unless env overrides; backend CI spec uses MySQL 8 | file | Two valid lanes: fast SQLite, parity MySQL — plan uses both |
| E18 | Repository is **not anonymously readable** (`api.github.com` → 404) | API call | Private repo ⇒ GHAS-gated features (CodeQL, dependency review, merge queue) need a plan check (§8.2) |
| E19 | 43 of 45 packages have **no test files**; `vitest run` exits 1 on "No test files found" | `turbo run test --filter='./packages/*'` → 36/38 failed in 2 m 13 s; only `analytics` (31 files) and `otp` (2 files) have tests | `packages` gate is red by construction, not by regression — it must ship soft (P2 adds `--passWithNoTests` plus real coverage) |
| E20 | `apps/web` jest is **red**: `components/__tests__/preloader/test-preloader/page.tsx` is matched as a suite and fails with "Your test suite must contain at least one test" | `jest --ci --silent` (64 files) | Jest's default `testMatch` treats fixtures as suites; gate must ship soft until P0 narrows `testMatch` |

**Resolved in P0 (2026-10-20):** E4, E5, E11, E19, E20 — see §14 for the measured
exit state. **E1–E3 resolved the same day** by merging the backend into this
repository (§6.1). E12 (Linux E2E), E13 (no image) and E18 (private repo) remain
open and still constrain the pipeline.

---

## 2. Why the current pipeline cannot be "made required"

Branch protection needs check names that are **always present and always meaningful**.
The current design cannot provide that:

* If a job is path-filtered or conditionally skipped, GitHub reports *skipped*, and a
  required check that is skipped blocks merges forever.
* `validate` bundles typecheck + lint + test; one broken linter fails all three signals.
* The backend has no checks at all, so "green" never meant "the API compiles".
* There is no single summary check, so enabling a new gate means editing branch
  protection every time.

**Rule adopted:** every workflow ends in exactly one aggregator job
(`ci-gate`, `security-gate`, `release-gate`) that runs `if: always()`, consumes
`needs`, and fails closed. Those three names are the only required checks.

---

## 3. Target architecture — the five rings

```
                         ┌──────────────────────────────────────────┐
   PR opened ──────────▶ │ R0 meta  (changes, actionlint, preflight)│
                         └───────────────┬──────────────────────────┘
        ┌────────────────────────────────┼─────────────────────────────────┐
        ▼                                ▼                                 ▼
 ┌─────────────┐                 ┌──────────────┐                  ┌──────────────┐
 │ R1 BUGS     │                 │ R2 QUALITY   │                  │ R3 SECURITY  │
 │ typecheck   │                 │ lint (soft)  │                  │ gitleaks     │
 │ unit ×4     │                 │ format drift │                  │ pnpm audit   │
 │ packages    │                 │ i18n parity  │                  │ semgrep/SAST │
 │ arch gates  │                 │ bundle size  │                  │ trivy fs     │
 │ BFF contract│                 │ a11y (night) │                  │ SBOM         │
 └──────┬──────┘                 └──────┬───────┘                  │ dep-review* │
        │                               │                          └──────┬───────┘
        └───────────────┬───────────────┴─────────────────────────────────┘
                        ▼
                 ┌─────────────┐        * GHAS-gated, degrades gracefully
                 │ R4 BUILD    │
                 │ 4× next     │
                 └──────┬──────┘
                        ▼
                 ┌─────────────┐
                 │  ci-gate    │  ◀── the only required check
                 └──────┬──────┘
                        ▼
                 ┌─────────────┐     ┌──────────────┐    ┌──────────────┐
                 │ merge queue │────▶│ R5 RELEASE   │───▶│ deploy gate  │
                 │ (merge_group)│     │ tag → attest │    │ env approval │
                 └─────────────┘     └──────────────┘    └──────────────┘
```

### 3.1 Gate ladder

| Gate | When | Budget (target) | Blocks merge | Failure mode |
|---|---|---|---|---|
| **G0** pre-commit | local commit | ≤ 20 s | no | warn |
| **G1** fast (R0+R1) | every PR / `merge_group` | ≤ 8 min p50 | **yes** | red |
| **G2** full (R1–R4) | every PR / `merge_group` | ≤ 25 min p50 | **yes** | red |
| **G3** security | PR + push to main + weekly | ≤ 15 min | **yes** (`security-gate`) | red |
| **G4** nightly | 02:30 IST daily | ≤ 90 min | no — opens/updates a tracking issue | issue |
| **G5** release | `v*` tag | ≤ 40 min | release blocked | red |

**Non-negotiable:** a gate is either blocking and green, or soft, named `… (soft)`,
and listed in §10.2 with a flip date. There is no third state.

---

## 4. Workflow inventory

| File | Triggers | Jobs | Required check |
|---|---|---|---|
| `.github/workflows/ci.yml` | `pull_request`, `push: [main]`, `merge_group`, `workflow_call` | meta, typecheck, unit-apps, unit-web, packages, gates, lint, i18n, build, ci-gate | `ci-gate` |
| `.github/workflows/security.yml` | `pull_request`, `push: [main]`, `schedule` weekly, `workflow_call` | secrets, deps, sast, supply-chain, security-gate | `security-gate` |
| `.github/workflows/nightly.yml` | `schedule`, `workflow_dispatch` | coverage, e2e, perf, mutation, a11y, flake-report, issue | — |
| `.github/workflows/release.yml` | `push: tags v*`, `workflow_dispatch` | verify (calls `ci.yml`), artifacts, attest, publish, deploy | `release-gate` |
| `.github/workflows/pr-hygiene.yml` | `pull_request` (title/size only) | semantic title | `PR title` |
| `platform-navdhya/.github/workflows/ci.yml` | backend repo only | static, contracts, test-sqlite, test-mysql, backend-gate | `backend-gate` |
| `.github/dependabot.yml` | schedule | pnpm, npm (`e2e/`), github-actions | — |

`ci.yml` also exposes `workflow_call` so `release.yml` reuses the exact same gate
instead of re-implementing it.

---

## 5. Job specification — root `ci.yml`

### R0 — `meta`
* `dorny/paths-filter@v3` → outputs `web`, `apps`, `packages`, `ci`, `docs`.
* `docker run rhysd/actionlint:1.7.7` → workflow YAML is code; lint it.
* Preflight invariants (fail closed):
  * `pnpm-lock.yaml` present and `package-lock.json` absent from the diff;
  * `packageManager` in `package.json` matches `pnpm/action-setup` version;
  * no `platform-navdhya/**` path appears in `git ls-files` (guards accidental
    commit of the nested repo).

### R1 — `typecheck` (matrix: web, customer, dashboard, partner)
```
pnpm exec turbo run typecheck --filter=./apps/<app> --output-logs=errors-only
```
`fetch-depth: 0` so `--affected` works; `TURBO_TELEMETRY_DISABLED=1`.

### R1 — `unit-apps` (matrix: customer, dashboard, partner) + `unit-web`
| Target | Command | Baseline |
|---|---|---|
| `apps/web` | `node tooling/scripts/ci/jest-known-failures.mjs` | 65 suites, 652 tests; **26 failures quarantined** in `ci/known-failures/web-jest.txt` (blocking ratchet, §10.1) |
| `apps/customer` | `pnpm --filter @navdhya/customer run test` | 5 tests, green |
| `apps/dashboard` | `pnpm --filter @navdhya/dashboard run test` | 55 tests, green |
| `apps/partner` | `pnpm --filter @navdhya/partner-app run test` | 49 tests, green |
| `packages/*` | `turbo run test --filter='./packages/*' --continue=always` | 38/38 tasks green; 43 packages carry `--passWithNoTests`, `analytics` (200) and `otp` (12) stay strict |

`unit-apps` (matrix of three) and `unit-web` are separate jobs **on purpose**:
a matrix shares one job id, so a per-app softness rule would also hide a
regression in the three suites that are green today.

The web suite takes ~13 min locally, so `unit-web` carries `timeout-minutes: 35`
and is **not** sharded: the ratchet compares one report against one manifest, and
shards would report each other's tests as missing (stale).

Jest and Vitest must **not** run concurrently in the same job (§11.4).

### R1 — `gates` (architecture + drift + contract)
```
pnpm exec vitest run tests/architecture tests/ci
node tooling/scripts/domain-check.js availability booking pandit pricing puja recommendations search user payment
```
`tests/architecture` carries the BFF↔Laravel contract test whose allow-list must stay
empty (W0). `tests/ci` carries the gate-evaluator tests and the workflow contract
tests (every job wired into its aggregator, every job timed out, no floating refs).

### R2 — `lint`
`pnpm exec turbo run lint --continue=always` — **blocking since P0**.

The v1 pipeline never linted: `next lint` was removed in Next 16 and `eslint`
was not installed (E4/E5). P0 restored ESLint 9 + `eslint-config-next@16.0.10`
with a single root flat config:

* `next/core-web-vitals` for React/Next/a11y rules;
* the repo import boundary (`no-restricted-imports` on `..`) now actually runs;
* nine migration rules downgraded to **warn** with measured counts and a pointer
  to this document — they are product migrations, not architecture rules
  (§10.2). Architecture enforcement stays in `tests/architecture`.

### R2 — `i18n (soft)`
`pnpm run i18n:check` — soft until the bundle-parity baseline is recorded.

### R4 — `build` (matrix: 4 apps)
```
NEXT_TELEMETRY_DISABLED=1 NEXT_STANDALONE=1 pnpm --filter <app> run build
```
* per-app artifact `.next/standalone` + `.next/BUILD_ID`, retention 3 days;
* job summary records `.next` size per app; the committed baseline and the
  15 % growth budget arrive in P3 (no invented numbers in v1);
* web is the long pole (1 363 static pages) → `timeout-minutes: 30`.

### Aggregator — `ci-gate`
```yaml
if: always()
needs: [meta, typecheck, unit-apps, unit-web, packages, gates, lint, i18n, build]
env:
  GATE_NEEDS: ${{ toJSON(needs) }}
  GATE_SOFT: i18n
  GATE_STRICT: ${{ vars.CI_STRICT }}   # comma list, or `all` when the register is empty
run: node tooling/scripts/ci/gate.mjs
```
`gate.mjs` (new, tested by `tests/ci/gate.test.ts`):
* fails if `needs` is empty, `null` or an array (misconfigured gate);
* fails on `failure` / `cancelled` / `timed_out` unless the job is in `GATE_SOFT`
  **and** not named in `GATE_STRICT`;
* prints a Markdown table into the step summary;
* emits `::error::` / `::warning::` annotations so a soft regression is still visible.

---

## 6. Backend CI (`platform-navdhya`)

### 6.1 Topology decision (blocking — D1)

Three options; the plan recommends **A**, with **B** as the fallback if the team
refuses to touch `.gitignore`.

| Option | Mechanism | Pros | Cons |
|---|---|---|---|
| **A — submodule** (recommended) | `git submodule add <platform-remote> platform-navdhya`, remove the ignore rule | Root CI can `submodules: recursive` and run backend jobs; commit pinning | Requires a backend remote first; one extra clone step |
| B — merge into monorepo | `git filter-repo` / subtree merge under `apps/api` | One pipeline, atomic cross-stack PRs | Rewrites history, large one-off cost |
| C — polyrepo + reusable workflow | `platform-navdhya` gets its own remote; both repos call `.github/workflows/reusable-laravel.yml@main` | Clean ownership | Cross-repo changes need two PRs and a pinned `@ref` |

**Decision (2026-10-20): B — merged.** The backend now lives at `platform-navdhya/`
inside this repository, imported with its full 118-commit history via a subtree-style
merge (`git read-tree --prefix`). The nested `.git` was removed and the ignore rule
was dropped. Consequences:

* the `meta` preflight now enforces the inverse invariant: the path must be tracked
  and must not contain its own `.git`;
* backend jobs may now appear in root `ci.yml` (P1) because they can actually run —
  the "permanently skipped required check" objection is gone;
* `platform-navdhya/.gitignore` still excludes `vendor/`, `.env` and regenerable
  caches, and git honours it inside this repo;
* the backend's own `.github/workflows/ci.yml` is inert (GitHub reads only the root
  `.github/workflows`); it stays as the specification the root jobs are ported from.

### 6.2 Backend job graph (port target for root `ci.yml`)

| Job | Command | Lane |
|---|---|---|
| `static` | `vendor/bin/pint --test` · `vendor/bin/phpstan analyse --no-progress` · `vendor/bin/deptrac analyse --no-cache` | every PR |
| `contracts` | `php artisan api:compose --check` · `php artisan route:cache` · `php artisan config:cache` | every PR |
| `test-sqlite` | `php artisan test --testsuite=Unit,Feature,Platform,Architecture,TenantIsolation,Modules` (SQLite `:memory:`) | every PR |
| `test-mysql` | same suites against MySQL 8 service, both connections | `main` + `merge_group` + nightly |
| `backend-gate` | aggregator | required |

Static analysis on Linux is also where the **18 local `openssl error:80000003` DPoP
failures** get settled: they are a Windows OpenSSL artefact (key generation), not a
code defect. The MySQL lane is the authority; if they still fail there, they are
quarantined via §10.1, not ignored.

---

## 7. Test-data determinism

| Concern | Rule |
|---|---|
| MSG91 | Always the local stub `e2e/msg91-stub.mjs` on `:9101`; `MSG91_VERIFY_BASE_URL=http://127.0.0.1:9101`; never a real key in CI |
| Dev shortcuts | `APP_ENV=testing` only; `MSG91` shortcuts are ON in local/testing and OFF in production (final decision, unchanged) |
| Databases | Unit/feature: SQLite `:memory:`. Parity: MySQL 8 service. `migrate:fresh` **never** against a shared DB; CI DBs are `navdhya_ci_*` and recreated per job |
| Tenant context | `TenantContext` is a singleton — tests clear it; the gate enforces no `TenantContext` leak by re-running the architecture suite after the feature suite in `test-sqlite` |
| Playwright | `workers: 1`, `retries: 0` in CI for the first 2 weeks, then `retries: 1` with `trace: on-first-retry`; traces uploaded on failure |
| Rate limiters | BFF restarted between HTTP and UI phases (`run.*` already does this) |

---

## 8. Security ring

### 8.1 Mapping to the threat model (`docs/SECURITY-AND-THREAT-MODEL.md`)

| Threat | Control in CI |
|---|---|
| Credential leakage (MSG91, Razorpay, PAN/PII) | `gitleaks` on every PR + full history nightly; custom rules already in `.gitleaks.toml`; GitHub push protection when GHAS is available |
| Vulnerable dependency (supply chain) | `pnpm audit --prod --audit-level high`, `composer audit`, Dependabot, `dependency-review` when GHAS |
| Malicious/compromised action | Actions pinned to SHA (P3), `permissions: read-all` default, no `pull_request_target` + head checkout, `actionlint` |
| Token/session forgery | `tests/architecture/*` + `apps/web` cookie-policy jest suites already encode `__Host-` / `Secure` / HMAC rules; they run in `unit-web` + `gates` |
| BFF leaking backend internals | `console-bff-contract` (allow-list empty) + envelope assertions in route tests |
| Build tampering | SBOM (SPDX) + `attest-build-provenance` on release artefacts |
| Secret sprawl in artefacts | build job has no secrets; deploy job is the only one with environment secrets |

### 8.2 Private-repo capability matrix (repo is not publicly readable — E18)

| Capability | Free/Pro private | Team/Enterprise | Substitute shipped here |
|---|---|---|---|
| CodeQL code scanning | ✗ (needs GHAS) | ✓ | Semgrep OSS in Docker (`semgrep/semgrep:latest`, `--config=auto`) — **soft** until triaged |
| Dependency review action | ✗ | ✓ | `pnpm audit` / `composer audit` + OSV-Scanner (nightly) |
| Secret scanning / push protection | ✗ | ✓ | gitleaks (already the standard here) |
| Merge queue | ✗ | ✓ | single `ci-gate` + "require branches up to date" |
| OSSF Scorecard | public only | — | skipped, documented |

Every GHAS-only job is gated by `vars.CI_ADVANCED_SECURITY == 'true'` so the workflow
does not fail on a plan limitation, and the plan records the upgrade trigger.

### 8.3 `security.yml` jobs

1. **`secrets`** — `gitleaks/gitleaks-action@v2`, `fetch-depth: 0`, existing config.
2. **`deps`** — `pnpm audit --prod --audit-level high` (blocking `critical` from day 1,
   `high` soft until the first burn-down), `composer audit` in the backend repo.
3. **`sast`** — Semgrep OSS (`--error` on `ERROR` severity), SARIF uploaded only when
   GHAS is on.
4. **`supply-chain`** — `anchore/sbom-action@v0` (SPDX JSON artifact) +
   `aquasecurity/trivy-action` fs scan (`CRITICAL,HIGH`, `exit-code: 0` in v1,
   blocking in P4 after triage).
5. **`codeql` / `dependency-review`** — GHAS-gated (`vars.CI_ADVANCED_SECURITY`),
   skipped rather than faked on plans without Advanced Security.
6. **`security-gate`** — aggregator, same `gate.mjs`.

---

## 9. Quality ring details

| Check | Tool | v1 status | Ratchet |
|---|---|---|---|
| Lint | ESLint 9 flat config + `eslint-config-next@16.0.10` | **blocking** — 0 errors, 482 warnings (measured 2026-10-20) | warnings are the ratchet: a migration rule returns to `error` when its count reaches 0 |
| Format drift | Prettier (not installed) | not shipped | optional; Pint covers PHP |
| Duplication / complexity | `jscpd`, `eslint complexity` | not shipped | P3 |
| Bundle size | job summary + `ci/bundle-baseline.json` | report-only | fail on >15 % growth |
| a11y | `@axe-core/playwright` in nightly | not installed | P4 |
| i18n parity | existing `scripts/check-bundle-parity.ts` | soft | P2 |
| Contract allow-list | `console-bff-contract` | blocking, allow-list empty | must stay empty |
| Workflow contract | `tests/ci/workflow-contract.test.ts` | blocking | a new job must be wired into its aggregator, carry a timeout, and stay pinned |
| Architecture | `tests/architecture` (123 files) | blocking | new files must add a rule, not relax one |

---

## 10. Ratchet & baseline policy

### 10.1 Known-failure manifests
`ci/known-failures/<gate>.txt` — one exact `path :: test name` entry per line,
with a mandatory `# owner:` and `# expires:` header.

* A gate fails on **any failure not listed**.
* A listed test that *passes* fails the gate (`stale entry`) — ratchets cannot rot.
* Expired manifest ⇒ gate fails regardless of results.
* A suite that cannot run at all (no assertion results) always fails: parser and
  module breakage is never listed debt.

Runner: `tooling/scripts/ci/jest-known-failures.mjs`, tested by
`tests/ci/known-failures.test.ts` (all-known ⇒ 0, stale ⇒ 1, unknown ⇒ 1,
unrunnable suite ⇒ 1, `--update` refused in CI). `--reuse <report.json>` evaluates
an existing report; `--update` rewrites the manifest and refuses when `CI=true`.

Live manifest: `ci/known-failures/web-jest.txt` — 26 tests, owner `@eng-b`,
expires **2026-12-31**. The debt is concentrated in ad-orchestration layout
tests, admin API route tests, auth contract schemas, header components and the
SEO catalog. This is the same philosophy already used for the BFF contract
allow-list (15 → 0).

### 10.2 Soft-gate register

| Gate | Why soft | Flip criterion | Owner | Status |
|---|---|---|---|---|
| `lint` | `next lint` removed; eslint not installed | ESLint restored, error count 0 | `@eng-b` | **flipped 2026-10-20** — 0 errors, 482 warnings |
| `unit-web` | jest default `testMatch` treats fixtures as suites (E20) | `testMatch` narrowed; suite green 3× | `@eng-b` | **flipped 2026-10-20** via known-failure ratchet (26 entries) |
| `packages` tests | 43/45 packages have no test files; `vitest run` exits 1 on empty (E19) | `--passWithNoTests` convention + 3 consecutive green runs | `@eng-a` | **flipped 2026-10-20** — 38/38 tasks green |
| `i18n` | parity baseline unrecorded | baseline committed, no diff | `@eng-c` | open |
| `sast` (Semgrep) | first-run triage | 0 `ERROR` findings | `@eng-a` | open |
| `trivy` | first-run triage | 0 unscoped `CRITICAL` | `@eng-a` | open |
| `high` audit | pre-existing advisories | 0 high advisories | `@eng-a` | open |

Lint warnings are the next ratchet, by rule family (2026-10-20):

| Rule | Warnings | Kind |
|---|---|---|
| `react/no-unescaped-entities` | 246 | cosmetic |
| `react-hooks/error-boundaries` | 94 | React Compiler (hooks v7) |
| `@next/next/inline-script-id` | 75 | JSON-LD `<Script>` ids |
| `@next/next/no-html-link-for-pages` | 30 | `<a>` → `<Link>` |
| `react-hooks/set-state-in-effect` | 12 | React Compiler |
| `react-hooks/immutability` | 4 | React Compiler |
| `react-hooks/refs` / `purity` / `globals` | 3 | React Compiler |

Downgrading these to `warn` is a *visible* ratchet: they still print on every
run. They are product migrations, not architecture rules — the architecture
suites and the import boundary stay hard errors.

### 10.3 Banned patterns (reviewed in `meta`)
* `|| true`, `continue-on-error: true` on a gate (only the aggregator may encode
  softness, from `GATE_SOFT`);
* `pull_request_target` with PR-head checkout;
* unpinned `@latest` / `@main` action refs;
* secrets in `pull_request` jobs from forks;
* `migrate:fresh` against anything not matching `navdhya_ci_*`.

---

## 11. Reliability & flake policy

1. **Quarantine, never mute.** A flaky test moves to `ci/known-failures` with an owner
   and a 14-day expiry; it still runs and is still reported.
2. **No retries in PR lane.** Retries hide nondeterminism. Nightly uses `retries: 1`
   and publishes a flake rate per spec file.
3. **Deterministic ports.** E2E binds `127.0.0.1:8000/3000/9101`; no dynamic ports
   (the BFF and stub URLs are baked into env).
4. **Serial runners.** Jest and Vitest in the same workspace must not run concurrently
   (two parallel runs produce spurious failures — observed).
5. **Timeouts everywhere.** Every job has `timeout-minutes`; every step that can hang
   has an explicit timeout.
6. **No Windows runners.** CI is Linux, matching the deploy target. Windows-only
   scripts are a portability bug, tracked in P2 (`e2e/run.sh`).
7. **Bounded jest memory.** The web suite is Next-heavy. On a 2-vCPU runner the
   default `cores-1` worker count plus parallel transforms can OOM a worker
   (observed locally: *"Jest worker ran out of memory and crashed"* while a second
   jest ran on the same machine). The ratchet therefore passes `--maxWorkers=2`
   when `CI=true` and `--workerIdleMemoryLimit=1024MB` always. An OOM-crashed
   suite has no assertion results, so the ratchet reports it as
   `<suite failed to run>` and **fails** — it can never be mistaken for known debt.
8. **A flake is a finding.** The `gates` suite failed once in 17 runs on
   2026-10-20, both times while a 65-suite jest run saturated the machine; 15
   consecutive runs were green with the machine otherwise idle. The cause is not
   yet identified, so the gate caps `--maxWorkers=2` rather than retrying. If it
   recurs with the cap in place, it gets a quarantine entry with an owner.

---

## 12. Release & supply chain

```
tag v1.2.0 ──▶ verify (workflow_call: ci.yml) ──▶ build 4× standalone
                                                  ├─▶ sha256sums.txt
                                                  ├─▶ SBOM (spdx.json)
                                                  ├─▶ attest-build-provenance
                                                  └─▶ GitHub Release (notes from git log)
                                                        └─▶ deploy-staging (environment: staging, manual)
                                                              └─▶ deploy-production (environment: production, manual)
```

* No Dockerfile exists (E13): artefacts are `.tar.gz` of each app's standalone output
  plus `sha256sums.txt`; the deploy job calls `scripts/deploy/<env>.sh` and **fails
  loudly if the script is absent** rather than pretending to deploy.
* Deploy jobs are the only place with write credentials; they run behind
  `environment:` approvals and are disabled until `vars.DEPLOY_ENABLED == 'true'`.
* Backend deploy keeps the documented runbook order (keys → migrate → scheduler →
  smoke) and is triggered from the backend repo, not from the web release.

---

## 13. Required configuration

### 13.1 Repository variables

| Variable | Purpose | Default |
|---|---|---|
| `CI_STRICT` | comma list of soft gates that must now block (`i18n`, `sast`, or `all`) — `lint`, `packages` and `unit-web` already block | unset (`i18n` soft) |
| `CI_ADVANCED_SECURITY` | enables GHAS-only jobs (CodeQL, dependency review) | unset |
| `E2E_ENABLED` | enables the nightly full-stack job | unset |
| `MUTATION_ENABLED` | enables the nightly mutation job | unset |
| `DEPLOY_ENABLED` | enables deploy jobs | unset |
| `TURBO_TOKEN` / `TURBO_TEAM` | optional remote cache | unset |

### 13.2 Secrets

| Secret | Used by | Notes |
|---|---|---|
| `GITLEAKS_LICENSE` | gitleaks-action | only for org-owned repos |
| `SEMGREP_APP_TOKEN` | optional Semgrep App | OSS mode needs none |
| `STAGING_SSH_KEY` / `PRODUCTION_SSH_KEY` | deploy jobs | environment-scoped |
| `CODECOV_TOKEN` | optional coverage upload | not required in v1 |

No application secret (`MSG91_*`, `RAZORPAY_*`, `IDENTITY_HMAC_SECRET`) is ever
available to a PR job.

### 13.3 Branch protection (manual, one-time)

| Setting | Value |
|---|---|
| Required checks | `ci-gate`, `security-gate`, `PR title` |
| Require branches up to date | on |
| Require review | 1 (CODEOWNERS) |
| Dismiss stale approvals | on |
| Allow force push | off |
| Merge queue | on when plan supports it, with `ci-gate` required |

---

## 14. Rollout

| Phase | Work | Exit criteria | Effort |
|---|---|---|---|
| **P0 — Make lint/test real** ✅ **done 2026-10-20** | ESLint 9 + `eslint-config-next@16.0.10`; single root flat config with the import boundary restored; `next lint` → `eslint .` in 4 apps; jest `testMatch` narrowed to `*.test.*`; 36 empty package suites → `--passWithNoTests`; Playwright `testMatch` now includes `console.spec.ts`; known-failure ratchet for the 26 accepted jest failures | `pnpm run lint` 0 errors / 4 tasks; `packages` 38/38; `unit (web)` green under the ratchet; `lint`, `packages`, `unit (web)` are blocking | 1 d (actual) |
| **P1 — Topology (D1)** | create backend remote; submodule (A) or merge (B); land `platform-navdhya/.github/workflows/ci.yml` | backend `backend-gate` green on a real PR | 1–2 d |
| **P2 — Backend lanes** | SQLite lane blocking, MySQL parity on `main`; settle/quarantine DPoP tests; Pint/PHPStan/Deptrac gates | 3 green `main` runs; manifest empty | 2 d |
| **P3 — Security hardening** | SHA-pin actions + Dependabot/Renovate; Semgrep/Trivy triage; bundle budget fails; supply-chain attestation | `security-gate` blocking with 0 untriaged | 3 d |
| **P4 — E2E on Linux** | port `run.ps1` → `e2e/run.sh`; nightly e2e + traces; a11y; perf budgets (`testMatch` now covers `console.spec.ts`, done in P0) | nightly green 5×; flake rate < 2 % | 4–5 d |
| **P5 — Release** | `release.yml`, artifact signing, environment approvals, deploy scripts | one tag → staging artefact deployed and verified | 2 d |

**Sequencing constraint:** P0 before any required `lint`; P1 before any backend gate is
named required; P3 after P2 so signing covers a deterministic build.

---

## 15. Cost & limits

| Item | Estimate |
|---|---|
| PR lane compute | ~22 job-minutes p50 (typecheck 4× ~2, unit 5× ~3, gates 2, build 4× ~6) |
| `apps/web` build | ~6–9 min (1 363 pages) — the single biggest cost |
| Nightly | ~70–90 job-minutes (MySQL, Playwright, coverage) |
| Storage | artifacts 3-day retention (≈ 400 MB/run), SBOM 90 days, coverage 7 days |
| Free-tier fit | PR lane fits comfortably in 2 000 min/month for a team of 5 at ~4 PRs/day **only if** build caching + `--affected` are enabled; otherwise build is the budget breaker |

Cache strategy: pnpm store (`setup-node cache: pnpm`), Turso/Turbo `.turbo` cache
(`actions/cache` keyed on lockfile hash), Playwright browser cache, composer cache.
Remote Turbo cache is optional (P3) and requires `TURBO_TOKEN`/`TURBO_TEAM`.

---

## 16. Risks

| Risk | Likelihood | Impact | Mitigation |
|---|---|---|---|
| Lint burn-down larger than estimated | high | P0 slips, `lint` stays soft | Ship soft gate + manifest; burn down per app |
| D1 topology stalls | medium | backend never CI'd | Timebox to P1; fallback C (reusable workflow) is cheap |
| Private plan lacks merge queue / GHAS | high | no queue, weaker SAST | §8.2 substitutes already shipped |
| E2E port to Linux surfaces Windows assumptions | high | nightly red | P4 isolated; `E2E_ENABLED` stays off until green 5× |
| CI minutes blow-up from `apps/web` build | medium | cost | `--affected`, `.turbo` cache, build only on PRs touching `apps/**` |
| Secrets accidentally added to PR jobs | low | high | §10.3 review rule + `meta` preflight |
| Gate aggregator misconfigured (empty `needs`) | low | false green | `gate.mjs` fails on empty `needs`; covered by `tests/ci` |

---

## 17. Open decisions

| ID | Decision | Recommendation |
|---|---|---|
| **D1** | Backend repo topology | A — submodule, single pipeline |
| **D2** | Do we pay for GHAS / Team? | Only if CodeQL + merge queue are required within a quarter; otherwise §8.2 stands |
| **D3** | Merge queue or "up to date" rule | Queue if the plan allows; otherwise require up-to-date + `ci-gate` |
| **D4** | Coverage threshold | Start at report-only; ratchet to `web ≥ 60 %`, `packages ≥ 80 %`, `platform ≥ 70 %` after 4 weeks |
| **D5** | Nightly window | 02:30 IST (off-peak for the deploy host) |

---

## 18. Proposed ADRs

* **ADR-0030 — CI gate topology** (single aggregator per workflow; required checks are
  aggregators only).
* **ADR-0031 — Fail-closed with ratchets** (no `|| true`; known-failure manifests with
  owner + expiry; stale entries fail).
* **ADR-0032 — Supply-chain policy** (SHA-pinned actions, SBOM + provenance on release,
  no `pull_request_target` + head checkout).

*(ADR-0028/0029 are reserved by `docs/PARTNER-FRONTEND-PLAN.md`.)*

---

## 19. Local parity

Every gate must be runnable on a laptop; CI must never be the first place a failure is
seen.

```bash
pnpm install --frozen-lockfile
pnpm exec turbo run typecheck --continue=always
pnpm exec vitest run tests/architecture tests/ci
pnpm --filter @navdhya/web exec jest --ci
pnpm --filter @navdhya/partner-app run test
pnpm exec node tooling/scripts/domain-check.js availability booking pandit pricing puja recommendations search user payment
node tooling/scripts/ci/gate.mjs            # expects GATE_NEEDS to be unset → local dry-run
```

Backend:

```bash
cd platform-navdhya
./vendor/bin/pint --test
./vendor/bin/phpstan analyse --no-progress
./vendor/bin/deptrac analyse --no-cache
php artisan api:compose --check
php artisan test
```
