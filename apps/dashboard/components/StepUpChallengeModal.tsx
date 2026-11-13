'use client';

import React, { useState } from 'react';

export interface StepUpChallengeRequest {
  actionLabel: string;
  challengeUrl?: string;
  message?: string;
  idempotencyKey: string;
  onRetryAfterStepUp: (idempotencyKey: string) => Promise<void>;
}

interface StepUpChallengeModalProps {
  challenge: StepUpChallengeRequest | null;
  onClose: () => void;
}

/**
 * Reusable ACR:2 step-up challenge modal (T-AP04-02).
 * Opens the issuer verification step and replays the exact blocked mutation
 * with the preserved `Idempotency-Key` so retries never double-approve or
 * lose client-side confirmation state.
 */
export function StepUpChallengeModal({ challenge, onClose }: StepUpChallengeModalProps) {
  const [replaying, setReplaying] = useState(false);
  const [error, setError] = useState<string | null>(null);

  if (!challenge) return null;

  const handleConfirmAndReplay = async () => {
    setReplaying(true);
    setError(null);
    try {
      await challenge.onRetryAfterStepUp(challenge.idempotencyKey);
      onClose();
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Step-up replay failed. Please verify MFA status.');
    } finally {
      setReplaying(false);
    }
  };

  return (
    <div
      role="dialog"
      aria-modal="true"
      aria-labelledby="step-up-dialog-title"
      className="fixed inset-0 z-50 flex items-center justify-center bg-[#2C2033]/60 p-4"
    >
      <div className="w-full max-w-md rounded-2xl border border-[#EFE3D3] bg-[#FFF7ED] p-6 shadow-xl text-[#2A2421]">
        <div className="mb-3 inline-flex items-center rounded-full bg-[#EFE3D3] px-3 py-1 text-xs font-semibold uppercase tracking-wide text-[#2C2033]">
          Step-Up Required (ACR 2)
        </div>
        <h2 id="step-up-dialog-title" className="text-lg font-bold text-[#2C2033]">
          Verify MFA for {challenge.actionLabel}
        </h2>
        <p className="mt-2 text-sm text-[#2A2421]">
          {challenge.message || 'This dual-custody treasury action requires a fresh MFA step-up verification.'}
        </p>

        <div className="mt-4 rounded-xl border border-[#EFE3D3] bg-white p-3 text-xs text-[#2A2421]">
          <p className="font-semibold text-[#2C2033]">Preserved Idempotency Key</p>
          <code className="mt-1 block break-all font-mono text-[11px] text-[#B74717]">
            {challenge.idempotencyKey}
          </code>
        </div>

        {challenge.challengeUrl ? (
          <div className="mt-4">
            <a
              href={challenge.challengeUrl}
              target="_blank"
              rel="noreferrer"
              className="inline-flex items-center rounded-lg border border-[#2C2033] px-3 py-2 text-xs font-semibold text-[#2C2033] hover:bg-[#EFE3D3]"
            >
              Open MFA Verification Prompt
            </a>
          </div>
        ) : null}

        {error ? (
          <p role="alert" className="mt-3 rounded-lg bg-[#EFE3D3] px-3 py-2 text-xs font-medium text-[#B74717]">
            {error}
          </p>
        ) : null}

        <div className="mt-6 flex items-center justify-end gap-3">
          <button
            type="button"
            onClick={onClose}
            disabled={replaying}
            className="rounded-lg border border-[#EFE3D3] px-4 py-2 text-sm font-medium text-[#2C2033] hover:bg-[#EFE3D3] disabled:opacity-50"
          >
            Cancel
          </button>
          <button
            type="button"
            onClick={handleConfirmAndReplay}
            disabled={replaying}
            className="rounded-lg bg-[#2C2033] px-4 py-2 text-sm font-semibold text-[#FFF7ED] hover:bg-[#3d2e47] disabled:opacity-50"
          >
            {replaying ? 'Replaying Approval...' : 'I Completed MFA — Replay Action'}
          </button>
        </div>
      </div>
    </div>
  );
}

