# Runbook — crash-rate spike (post-release regression)

**Drill evidence:** `tests/signals/crash-release.test.ts` (canary: injected spike → alert fires, rollback dry-run verified); B8 package units `packages/analytics/src/crash/__tests__/trends-canary.test.ts`.

## Symptoms / detection

- Canary alert: `Crash-rate spike on release <X>` from `detectReleaseRegression` (error rate ≥ 3× baseline with ≥ 10 errors). Alert carries diagnostics + this runbook.
- `signals_mart.error_groups`: a new or sharply-rising error group scoped to the suspect release.
- Crash-free web-session rate dropping vs. the previous release (web-instrumented sessions only — native app crashes never appear here and must never be equated with this metric).

## Expected degradation mode

- Affected sessions see the erroring flow fail (e.g. checkout submit throws); unaffected flows continue.
- Error ingest is adaptively sampled under volume — group RATES stay comparable even when counts are sampled; rates are sampling-corrected by the recorded probability.
- Auto-rollback default is **disabled**: the alert fires, an operator acts. `dry_run` mode records the would-rollback decision WITHOUT invoking the hook (the tested-before-enabled trail); `enabled` mode requires a configured hook and refuses to claim a rollback without one.

## Immediate actions

1. Open the alert; read the top new error group from the `error_groups` mart (group, class, affected sessions, release).
2. Reproduce from the group's frames (build hashes and framework frames are already stripped — the fingerprint points at app code).
3. Decide: hotfix forward vs. rollback. If rolling back and auto-rollback is `enabled`, the hook executes exactly once; if `disabled`/`dry_run`, execute the deploy rollback manually.
4. If the spike is in an experiment variant, pause the experiment (`status: stopped`) — crash regressions and experiment readouts must not be confused.

## Recovery

- Roll back or ship the fix; the canary window re-evaluates on the next release's error rate vs. its baseline.
- Suppress nothing: do not raise the regression threshold or the sampling floor to make the alert quiet — fix the error.

## Verification

- New release's error rate within `minRatio` of its baseline; alert clears.
- The offending error group's count flatlines at zero new occurrences.
- Crash-free rate recovers; the basis statement (web-instrumented only) unchanged.
