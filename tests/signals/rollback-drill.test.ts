// B9 acceptance — rollback drill (plan B9 bullet 4): "a release rollback
// drill: release regression → rollback hook → verified return within the
// rollback window".
//
// The drill runs the B8 canary path in ENABLED mode against a fake deploy
// API: a crash-rate spike on the current release triggers the actionable
// alert, the configured hook executes the rollback exactly once, and the
// deploy surface verifiably returns to the previous release inside the
// rollback window. The dry-run trail (hook verified, never invoked) is
// asserted here too — the enablement precondition from B8.

import { describe, expect, it } from 'vitest';
import { CanaryGuard } from '@navdhya/analytics/src/crash/canary';
import { detectReleaseRegression } from '@navdhya/analytics/src/crash/trends';

/** Rollback window: the SLO the drill verifies against (§1 family). */
const ROLLBACK_WINDOW_MS = 15 * 60 * 1000;

/** Fake deploy surface: holds the live release; the hook rolls it back. */
class FakeDeployApi {
  live: string;
  readonly history: Array<{ action: string; release: string; atMs: number }> = [];
  constructor(live: string) {
    this.live = live;
  }
  hook() {
    return {
      execute: async (release: string): Promise<{ ok: boolean; detail: string }> => {
        this.history.push({ action: 'rollback', release, atMs: Date.now() });
        // Rolling back release R means serving its predecessor again.
        this.live = PREDECESSOR[release] ?? 'unknown';
        return { ok: true, detail: `rollback of ${release} complete; serving ${this.live}` };
      },
    };
  }
}

const PREDECESSOR: Record<string, string> = {
  '2026.10.1': '2026.10.0',
};

const spikeVerdict = detectReleaseRegression(
  { release: '2026.10.1', errors: 1500, sessions: 20000, affectedSessions: 1200 },
  { release: '2026.10.0', errors: 12, sessions: 20000, affectedSessions: 11 },
);

describe('B9 acceptance — release rollback drill', () => {
  it('spike → alert → hook rollback executes once → verified return within the window', async () => {
    const deploy = new FakeDeployApi('2026.10.1');

    // Phase 0 (enablement precondition, from B8): the dry-run trail proves
    // the decision path WITHOUT invoking the hook before anyone enables it.
    let dryRunHookCalls = 0;
    const dryRunGuard = new CanaryGuard({
      mode: 'dry_run',
      hook: {
        execute: async () => {
          dryRunHookCalls += 1;
          return { ok: true, detail: 'unused' };
        },
      },
    });
    const dry = await dryRunGuard.evaluate(spikeVerdict);
    expect(dry.rollback.fired).toBe(true);
    expect(dry.rollback.hookExecuted).toBe(false);
    expect(dryRunHookCalls).toBe(0);

    // Phase 1: the drill itself — enabled mode with the deploy hook wired.
    const drillAt = Date.now();
    const guard = new CanaryGuard({ mode: 'enabled', hook: deploy.hook() });
    const decision = await guard.evaluate(spikeVerdict);

    // Alert fires with actionable diagnostics:
    expect(decision.alert).not.toBeNull();
    expect(decision.alert!.release).toBe('2026.10.1');
    expect(decision.alert!.runbook).toContain('runbook');

    // Rollback executed exactly once through the hook:
    expect(decision.rollback.mode).toBe('enabled');
    expect(decision.rollback.hookExecuted).toBe(true);
    expect(decision.rollback.fired).toBe(true);
    expect(deploy.history).toHaveLength(1);
    expect(deploy.history[0]!.action).toBe('rollback');

    // Verified return: the deploy surface serves the predecessor release,
    // inside the rollback window.
    expect(deploy.live).toBe('2026.10.0');
    expect(deploy.history[0]!.atMs - drillAt).toBeLessThan(ROLLBACK_WINDOW_MS);

    // No-regression control: the same guard on a clean release is a no-op.
    const quiet = detectReleaseRegression(
      { release: '2026.10.2', errors: 8, sessions: 20000, affectedSessions: 8 },
      { release: '2026.10.0', errors: 12, sessions: 20000, affectedSessions: 11 },
    );
    const noOp = await guard.evaluate(quiet);
    expect(noOp.alert).toBeNull();
    expect(deploy.history).toHaveLength(1); // still exactly one rollback
  });
});
