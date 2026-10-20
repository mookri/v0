// Canary guard (plan B8 crash bullet): post-release regression →
// actionable alert → rollback decision. Auto-rollback is
// **tested before enabled** (plan wording): the default mode is
// `disabled`, `dry_run` computes and records the decision WITHOUT
// executing the hook, and `enabled` requires a configured hook. The
// enablement is a deployment decision made after the dry-run trail has
// proven the hook behaves.

import type { CrashAlert, RegressionVerdict } from './trends';
import { renderCrashAlert } from './trends';

export interface RollbackResult {
  ok: boolean;
  detail: string;
}

export interface RollbackHook {
  /** Roll the named release back. Must be idempotent — the guard may
   *  retry on a redeployed canary window. */
  execute(release: string, alert: CrashAlert): Promise<RollbackResult>;
}

/** Default: OFF. Auto-rollback flips to 'enabled' only after the dry-run
 *  trail has been reviewed (plan: tested before enabled). */
export type RollbackMode = 'disabled' | 'dry_run' | 'enabled';

export interface CanaryDecision {
  release: string;
  alert: CrashAlert | null;
  verdict: RegressionVerdict;
  rollback: {
    mode: RollbackMode;
    /** True when a rollback was (or would be) executed. */
    fired: boolean;
    /** True only when the hook actually ran. */
    hookExecuted: boolean;
    detail: string;
  };
}

export interface CanaryGuardDeps {
  hook?: RollbackHook;
  mode?: RollbackMode;
}

export class CanaryGuard {
  constructor(private readonly deps: CanaryGuardDeps = {}) {}

  async evaluate(verdict: RegressionVerdict): Promise<CanaryDecision> {
    const alert = renderCrashAlert(verdict);
    const mode = this.deps.mode ?? 'disabled';

    if (!alert) {
      return {
        release: verdict.release,
        alert: null,
        verdict,
        rollback: { mode, fired: false, hookExecuted: false, detail: 'no regression — nothing to roll back' },
      };
    }

    if (mode === 'disabled') {
      return {
        release: verdict.release,
        alert,
        verdict,
        rollback: {
          mode,
          fired: false,
          hookExecuted: false,
          detail: `alert raised for ${verdict.release}; auto-rollback is not enabled — operator action required`,
        },
      };
    }

    if (mode === 'dry_run') {
      // The hook is NOT executed — this is the verification trail that
      // must exist before anyone flips the mode to 'enabled'.
      return {
        release: verdict.release,
        alert,
        verdict,
        rollback: {
          mode,
          fired: true,
          hookExecuted: false,
          detail: `DRY-RUN: rollback of ${verdict.release} would execute (hook not invoked) — verify, then enable`,
        },
      };
    }

    // mode === 'enabled': a hook is mandatory; refusing silently would
    // turn a crash spike into an unattended outage.
    if (!this.deps.hook) {
      return {
        release: verdict.release,
        alert,
        verdict,
        rollback: {
          mode,
          fired: false,
          hookExecuted: false,
          detail: 'rollback mode enabled but no hook configured — refusing to claim a rollback',
        },
      };
    }
    const result = await this.deps.hook.execute(verdict.release, alert);
    return {
      release: verdict.release,
      alert,
      verdict,
      rollback: {
        mode,
        fired: result.ok,
        hookExecuted: true,
        detail: result.detail,
      },
    };
  }
}
