// Trends + canary units (plan B8): honest crash-free basis, regression
// verdicts, actionable alerts, and auto-rollback that is dry-run first.

import { describe, expect, it } from 'vitest';
import {
  WEB_CRASH_FREE_BASIS,
  crashFreeSessionRate,
  detectReleaseRegression,
  renderCrashAlert,
} from '@navdhya/analytics/src/crash/trends';
import { CanaryGuard } from '@navdhya/analytics/src/crash/canary';

describe('crash trends (B8)', () => {
  it('crash-free rate is described by web instrumentation only — never native', () => {
    const rate = crashFreeSessionRate({ release: '2026.10.1', affectedSessions: 5, sessions: 100 });
    expect(rate.value).toBeCloseTo(0.95, 6);
    expect(rate.basis).toBe(WEB_CRASH_FREE_BASIS);
    expect(rate.basis).toContain('native app crashes are out of scope');
    // Degenerate window: no sessions observed ⇒ no crash-free claim of failure.
    expect(crashFreeSessionRate({ release: 'x', affectedSessions: 0, sessions: 0 }).value).toBe(1);
  });

  it('detects an injected crash-rate spike and stays quiet within bounds', () => {
    const baseline = { release: '2026.10.0', errors: 10, sessions: 10000, affectedSessions: 9 };
    const spiked = detectReleaseRegression(
      { release: '2026.10.1', errors: 800, sessions: 10000, affectedSessions: 700 },
      baseline,
    );
    expect(spiked.spiked).toBe(true);
    expect(spiked.ratio!).toBeGreaterThan(3);

    const within = detectReleaseRegression(
      { release: '2026.10.1', errors: 20, sessions: 10000, affectedSessions: 18 },
      baseline,
    );
    expect(within.spiked).toBe(false);

    // Below the credibility floor: a spike of 3 errors is noise.
    const noise = detectReleaseRegression(
      { release: '2026.10.1', errors: 3, sessions: 10000, affectedSessions: 3 },
      baseline,
    );
    expect(noise.spiked).toBe(false);
    expect(noise.reason).toContain('credibility floor');
  });

  it('the alert is actionable: owner-facing diagnostics + runbook', () => {
    const verdict = detectReleaseRegression(
      { release: '2026.10.1', errors: 800, sessions: 10000, affectedSessions: 700 },
      { release: '2026.10.0', errors: 10, sessions: 10000, affectedSessions: 9 },
    );
    const alert = renderCrashAlert(verdict)!;
    expect(alert.title).toContain('2026.10.1');
    expect(alert.diagnostics[0]).toContain('error rate');
    expect(alert.runbook).toContain('runbook');
    expect(renderCrashAlert(detectReleaseRegression(
      { release: 'r2', errors: 5, sessions: 1000, affectedSessions: 5 },
      { release: 'r1', errors: 4, sessions: 1000, affectedSessions: 4 },
    ))).toBeNull();
  });
});

describe('canary rollback (B8)', () => {
  const verdict = detectReleaseRegression(
    { release: '2026.10.1', errors: 800, sessions: 10000, affectedSessions: 700 },
    { release: '2026.10.0', errors: 10, sessions: 10000, affectedSessions: 9 },
  );

  it('default mode is DISABLED: alert fires, no rollback claimed', async () => {
    const guard = new CanaryGuard();
    const decision = await guard.evaluate(verdict);
    expect(decision.alert).not.toBeNull();
    expect(decision.rollback.mode).toBe('disabled');
    expect(decision.rollback.fired).toBe(false);
    expect(decision.rollback.hookExecuted).toBe(false);
  });

  it('DRY-RUN verifies the decision WITHOUT executing the hook (the tested-before-enabled trail)', async () => {
    let hookCalls = 0;
    const guard = new CanaryGuard({
      mode: 'dry_run',
      hook: { execute: async () => { hookCalls += 1; return { ok: true, detail: 'rolled back' }; } },
    });
    const decision = await guard.evaluate(verdict);
    expect(decision.rollback.fired).toBe(true);
    expect(decision.rollback.hookExecuted).toBe(false);
    expect(hookCalls).toBe(0); // hook untouched
    expect(decision.rollback.detail).toContain('DRY-RUN');
  });

  it('enabled mode executes the hook exactly once; enabled without a hook refuses to claim rollback', async () => {
    let hookCalls = 0;
    const guard = new CanaryGuard({
      mode: 'enabled',
      hook: {
        execute: async (release) => {
          hookCalls += 1;
          return { ok: true, detail: `rolled back ${release}` };
        },
      },
    });
    const decision = await guard.evaluate(verdict);
    expect(hookCalls).toBe(1);
    expect(decision.rollback.hookExecuted).toBe(true);
    expect(decision.rollback.fired).toBe(true);
    expect(decision.rollback.detail).toBe('rolled back 2026.10.1');

    const naked = new CanaryGuard({ mode: 'enabled' });
    const refused = await naked.evaluate(verdict);
    expect(refused.rollback.hookExecuted).toBe(false);
    expect(refused.rollback.detail).toContain('refusing');
  });

  it('no regression ⇒ no alert, nothing rolled back', async () => {
    const guard = new CanaryGuard({ mode: 'dry_run' });
    const quiet = detectReleaseRegression(
      { release: 'r2', errors: 5, sessions: 1000, affectedSessions: 5 },
      { release: 'r1', errors: 4, sessions: 1000, affectedSessions: 4 },
    );
    const decision = await guard.evaluate(quiet);
    expect(decision.alert).toBeNull();
    expect(decision.rollback.detail).toContain('nothing to roll back');
  });
});
