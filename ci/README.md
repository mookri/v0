# `ci/` — pipeline contracts

Operational companion to [`docs/CI-CD-PIPELINE-PLAN.md`](../docs/CI-CD-PIPELINE-PLAN.md).
Read the plan for *why*; read this for *how to change the pipeline without
breaking the merge queue*.

## 1. Required checks

Branch protection should list exactly these:

| Check | Workflow | Meaning |
|---|---|---|
| `ci-gate` | `.github/workflows/ci.yml` | typecheck, unit, architecture, build |
| `security-gate` | `.github/workflows/security.yml` | secrets, advisories, SAST, supply chain |
| `PR title` | `.github/workflows/pr-hygiene.yml` | conventional-commit title |

Never add a matrix job name (e.g. `unit (web (jest))`) as a required check: if a
path filter skips it, the required check stays *skipped* and blocks every merge.
The aggregator exists precisely so this cannot happen.

## 2. Soft gates

A soft gate is a **ratchet**, not a bypass. It is:

1. named with a `(soft)` suffix in the workflow,
2. listed in `GATE_SOFT` on the aggregator,
3. listed in the plan's soft-gate register (§10.2) with an owner and a flip
   criterion.

To make one blocking, set the repository variable `CI_STRICT` to a comma list of
job names (or `all`):

```text
CI_STRICT=i18n            # only i18n blocks
CI_STRICT=i18n,perf       # two gates block
CI_STRICT=all             # the register is empty
```

No workflow edit is needed, and the change is visible in the repo settings audit
log.

**Current register:** `i18n` only. `lint`, `packages` and `unit (web)` were
flipped to blocking in P0 once each was verified green (see the plan's gate
ladder). `unit (web)` is blocking because it runs under a known-failure ratchet,
not because the suite is clean.

**Never** silence a gate with `|| true`, `continue-on-error: true`, or by
removing it from `needs`. The aggregator script fails closed on an empty `needs`
map specifically to catch that.

### Known-failure manifests

A soft gate hides new regressions inside old ones. For suites with accepted debt,
use a manifest instead: the gate stays **blocking**, and `ci/known-failures/<suite>.txt`
lists exactly which failures are tolerated.

`node tooling/scripts/ci/jest-known-failures.mjs` fails when:

| Condition | Why |
|---|---|
| a failing test is not in the manifest | new regression |
| a manifest entry now passes | stale entry — the ratchet must not rot |
| the manifest `# expires:` date has passed | debt needs a renewal decision |
| a suite cannot run at all | parser/module breakage has no assertion results to match |

The manifest header carries an owner and an expiry. `--update` rewrites it and
refuses to run when `CI=true`: changing the manifest is always a reviewed diff,
never a CI side effect. `--reuse <report.json>` evaluates an existing jest report
(used by the tests and for fast local iteration).

Current manifest: `ci/known-failures/web-jest.txt` — 26 tests, owner `@eng-b`,
expires 2026-12-31.



```bash
pnpm install --frozen-lockfile

# R1 — bugs
pnpm exec turbo run typecheck --continue=always
node tooling/scripts/ci/jest-known-failures.mjs   # apps/web + ratchet
pnpm --filter @navdhya/customer run test
pnpm --filter @navdhya/dashboard run test
pnpm --filter @navdhya/partner-app run test
pnpm exec turbo run test --filter='./packages/*' --continue=always
pnpm exec vitest run tests/architecture tests/ci
pnpm exec node tooling/scripts/domain-check.js availability booking pandit pricing puja recommendations search user payment

# R2 — quality
pnpm run lint

# R4 — build (standalone is the deployable artefact)
NEXT_STANDALONE=1 pnpm --filter @navdhya/web run build

# The aggregator, locally
GATE_NEEDS='{"build":{"result":"success"}}' node tooling/scripts/ci/gate.mjs
```

Backend (inside `platform-navdhya/`):

```bash
./vendor/bin/pint --test
./vendor/bin/phpstan analyse --no-progress
./vendor/bin/deptrac analyse --no-cache
php artisan api:compose --check
php artisan test
```

## 4. Changing the pipeline

* Every workflow change must keep `actionlint` green (`meta` job).
* New job → add it to the aggregator's `needs`, or it is not a gate.
* New gate → decide **blocking or soft** before merging. "We'll see" is not a
  state: an unclassified gate is a blocking gate that nobody agreed to.
* Actions are tag-pinned today and SHA-pinned in P3. Do not introduce `@latest`
  or `@main`.
* Fork PRs get no secrets and read-only tokens. If a job needs a secret, it does
  not belong in `pull_request`.

## 5. Test data rules

| Rule | Why |
|---|---|
| MSG91 is always the local stub on `:9101` | no real provider traffic from CI, ever |
| `APP_ENV=testing` in every PHP lane | dev shortcuts are ON in local/testing, OFF in production |
| `migrate:fresh` only against `navdhya_ci_*` | a typo must not be able to delete a real database |
| Playwright `workers: 1` | the stack shares one MySQL state across phases |
| No jest and vitest in the same job | two runners in one workspace produce spurious failures |
| Jest capped at `--maxWorkers=2` in CI | a 2-vCPU runner can OOM a jest worker on this Next-heavy suite |

## 6. Gate script

`tooling/scripts/ci/gate.mjs` is covered by `tests/ci/gate.test.ts` and runs in
the `gates` job. `tests/ci/workflow-contract.test.ts` additionally enforces the
pipeline's design rules on the workflow files themselves:

| Rule | Why |
|---|---|
| every job in a workflow is in its aggregator's `needs` | an unwired job can fail without failing the required check |
| every job has `timeout-minutes` | a hung job must not burn the monthly budget |
| every workflow declares `permissions:` | least privilege by default |
| no `@main` / `@latest` / `@HEAD` action refs | reproducible, reviewable supply chain |
| no truthy fallbacks | a gate that cannot fail is not a gate |
| every `GATE_SOFT` entry exists and is named `(soft)` | the soft register cannot drift from the workflow |

## 7. Gate script internals

| Input | Behaviour |
|---|---|
| `GATE_NEEDS` unset | exit 0, local dry run |
| `GATE_NEEDS` invalid JSON | exit 1 |
| `GATE_NEEDS` `{}`, `[]`, `null` | exit 1 (fail closed) |
| `result: success \| skipped` | pass |
| any other result | fail, unless soft and not strict |
| `GATE_STRICT=lint,packages` | those soft gates fail again |
| `GATE_STRICT=all` | every soft gate fails again |
