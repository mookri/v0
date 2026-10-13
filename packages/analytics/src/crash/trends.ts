// Release-scoped crash trends + regression alerts (plan B8 crash bullet):
// error-group counts and error-rate / affected-session trends per release,
// plus the post-release regression check that produces an ACTIONABLE alert
// (owner + runbook), feeding the canary guard.
//
// Honesty note (plan wording, test-pinned): crash-free-session metrics are
// described by what the WEB instrumentation actually measures — sessions
// observed by the SDK with zero captured errors/unhandled rejections. Native
// app crashes never reach this pipeline and must never be equated with it.

export const WEB_CRASH_FREE_BASIS =
  'web-instrumented sessions only — sessions observed by the web SDK with zero captured errors or unhandled rejections; native app crashes are out of scope and must never be equated with this metric';

export interface SessionWindow {
  release: string;
  /** Distinct sessions with >= 1 captured error (sampling-corrected). */
  affectedSessions: number;
  /** Distinct instrumented sessions observed in the window. */
  sessions: number;
}

export interface CrashFreeRate {
  release: string;
  /** Fraction of sessions without captured errors, in [0, 1]. */
  value: number;
  basis: string;
}

export function crashFreeSessionRate(input: SessionWindow): CrashFreeRate {
  if (input.sessions <= 0) {
    return { release: input.release, value: 1, basis: WEB_CRASH_FREE_BASIS };
  }
  const affected = Math.min(input.affectedSessions, input.sessions);
  return {
    release: input.release,
    value: (input.sessions - affected) / input.sessions,
    basis: WEB_CRASH_FREE_BASIS,
  };
}

export interface ReleaseErrorWindow extends SessionWindow {
  /** Total captured errors in the window (sampling-corrected count). */
  errors: number;
}

export interface RegressionVerdict {
  release: string;
  spiked: boolean;
  currentRate: number;
  baselineRate: number;
  ratio: number | null;
  reason: string;
}

export interface RegressionOptions {
  /** Minimum error count before a spike is credible (default 10). */
  minErrors?: number;
  /** Rate ratio vs baseline that counts as a spike (default 3). */
  minRatio?: number;
}

function errorRate(input: ReleaseErrorWindow): number {
  return input.sessions > 0 ? input.errors / input.sessions : 0;
}

/** Compare a release's error rate against its predecessor. */
export function detectReleaseRegression(
  current: ReleaseErrorWindow,
  baseline: ReleaseErrorWindow,
  options: RegressionOptions = {},
): RegressionVerdict {
  const minErrors = options.minErrors ?? 10;
  const minRatio = options.minRatio ?? 3;
  const currentRate = errorRate(current);
  const baselineRate = errorRate(baseline);
  const ratio = baselineRate > 0 ? currentRate / baselineRate : null;

  if (current.errors < minErrors) {
    return {
      release: current.release,
      spiked: false,
      currentRate,
      baselineRate,
      ratio,
      reason: `only ${current.errors} errors (< ${minErrors}) — below the credibility floor, not flagged`,
    };
  }
  const spiked = ratio === null ? currentRate > 0 : ratio >= minRatio;
  return {
    release: current.release,
    spiked,
    currentRate,
    baselineRate,
    ratio,
    reason: spiked
      ? `error rate ${currentRate.toFixed(4)}/session is ${ratio === null ? 'non-zero with no baseline' : `${ratio.toFixed(1)}x`} the baseline ${baselineRate.toFixed(4)} (>= ${minRatio}x, ${current.errors} errors)`
      : `error rate ${currentRate.toFixed(4)}/session vs baseline ${baselineRate.toFixed(4)} (${ratio === null ? 'n/a' : `${ratio.toFixed(2)}x`}) — within bounds`,
  };
}

export interface CrashAlert {
  title: string;
  release: string;
  diagnostics: string[];
  runbook: string;
}

/** Actionable alert: who acts and how — a spike without a runbook is noise. */
export function renderCrashAlert(verdict: RegressionVerdict): CrashAlert | null {
  if (!verdict.spiked) return null;
  return {
    title: `Crash-rate spike on release ${verdict.release}`,
    release: verdict.release,
    diagnostics: [verdict.reason],
    runbook: 'docs/analytics/runbooks/crash-spike.md — compare error_groups mart by release, identify the top new group, roll back or ship a fix, then re-check the canary window',
  };
}
