'use client';

import { useState, useTransition } from 'react';

export const REJECTION_REASON_CODES = [
  { value: 'incomplete_identity', label: 'Incomplete or mismatched identity evidence' },
  { value: 'unverifiable_credential', label: 'Unverifiable qualification or lineage claim' },
  { value: 'authority_conflict', label: 'Conflicting or unproven institutional authority' },
  { value: 'bank_mismatch', label: 'Bank instrument or payout name mismatch' },
  { value: 'sanctions_or_aml', label: 'Regulatory or AML screening concern' },
  { value: 'other', label: 'Other documented compliance ground' },
] as const;

type RejectPayload = {
  reason_code: string;
  note: string | null;
  second_reviewer: string;
};

/**
 * W3 capability surfacing: approve / reject actions on a review queue row.
 *
 * Both verbs are `stepup:2` gated. When the backend answers
 * 403 `FORBIDDEN` with a step-up-required signal, this component collects a
 * TOTP code, verifies it through `/api/mfa/verify` (which rotates the session
 * cookie on success), and retries the original action with its full payload.
 */
export function ReviewActions({ applicationId }: { applicationId: string }) {
  const [status, setStatus] = useState<'idle' | 'pending' | 'done' | 'error'>('idle');
  const [message, setMessage] = useState<string>('');
  const [stepUpOpen, setStepUpOpen] = useState(false);
  const [stepUpCode, setStepUpCode] = useState('');
  const [pendingAction, setPendingAction] = useState<'approve' | 'reject' | null>(null);
  const [pendingRejectPayload, setPendingRejectPayload] = useState<RejectPayload | null>(null);
  const [rejectOpen, setRejectOpen] = useState(false);
  const [reasonCode, setReasonCode] = useState<string>(REJECTION_REASON_CODES[0].value);
  const [rejectNote, setRejectNote] = useState<string>('');
  const [secondReviewer, setSecondReviewer] = useState<string>('');
  const [rejectError, setRejectError] = useState<string>('');
  const [isPending, startTransition] = useTransition();

  async function runAction(action: 'approve' | 'reject', payload?: RejectPayload) {
    if (action === 'reject' && (!payload || !payload.reason_code.trim() || !payload.second_reviewer.trim())) {
      setStatus('error');
      setMessage('COUNTERSIGN_REQUIRED');
      return;
    }

    setStatus('pending');
    const url = `/api/admin/managers/${encodeURIComponent(applicationId)}/${action}`;
    const init: RequestInit =
      action === 'reject'
        ? {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload),
          }
        : { method: 'POST', headers: { 'Content-Type': 'application/json' } };

    const res = await fetch(url, init);
    const body = (await res.json().catch(() => null)) as { data?: unknown; error?: { code?: string; message?: string } } | null;

    if (res.status === 403 && body?.error?.code === 'FORBIDDEN') {
      setPendingAction(action);
      setPendingRejectPayload(payload ?? null);
      setStepUpOpen(true);
      setStatus('idle');
      return;
    }

    if (!res.ok) {
      setStatus('error');
      setMessage(body?.error?.code ?? 'REQUEST_FAILED');
      return;
    }

    setStatus('done');
    setMessage(action === 'approve' ? 'Approved' : 'Rejected');
  }

  async function submitReject() {
    if (!reasonCode.trim()) {
      setRejectError('Select a structured rejection reason.');
      return;
    }
    if (!secondReviewer.trim()) {
      setRejectError('Second reviewer identifier is required for countersigned rejection.');
      return;
    }
    setRejectError('');
    const payload: RejectPayload = {
      reason_code: reasonCode.trim(),
      note: rejectNote.trim() ? rejectNote.trim() : null,
      second_reviewer: secondReviewer.trim(),
    };
    setRejectOpen(false);
    await runAction('reject', payload);
  }

  async function submitStepUp() {
    if (!stepUpCode || !pendingAction) return;
    startTransition(async () => {
      try {
        const verifyRes = await fetch('/api/mfa/verify', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ code: stepUpCode }),
        });
        if (!verifyRes.ok) {
          setStatus('error');
          setMessage('STEP_UP_FAILED');
          setStepUpOpen(false);
          return;
        }
        // Session cookie rotated by the BFF; retry the original action with payload.
        const retryAction = pendingAction;
        const retryPayload = pendingRejectPayload ?? undefined;
        setStepUpOpen(false);
        setStepUpCode('');
        setPendingAction(null);
        setPendingRejectPayload(null);
        await runAction(retryAction, retryPayload);
      } catch {
        setStatus('error');
        setMessage('NETWORK_ERROR');
        setStepUpOpen(false);
      }
    });
  }

  return (
    <div className="flex items-center gap-2">
      <button
        type="button"
        onClick={() => runAction('approve')}
        disabled={isPending || status === 'done'}
        data-testid="approve-button"
        className="rounded-md border border-[#B74717]/40 bg-[#F16A24]/15 px-2 py-1 text-xs font-medium text-[#2C2033] hover:bg-[#F16A24]/25 disabled:opacity-50"
      >
        {status === 'pending' ? '…' : status === 'done' && message === 'Approved' ? '✓ Approved' : 'Approve'}
      </button>
      <button
        type="button"
        onClick={() => {
          setRejectError('');
          setRejectOpen(true);
        }}
        disabled={isPending || status === 'done'}
        data-testid="reject-button"
        className="rounded-md border border-red-500/40 bg-red-500/10 px-2 py-1 text-xs font-medium text-red-700 hover:bg-red-500/20 disabled:opacity-50"
      >
        {status === 'done' && message === 'Rejected' ? '✓ Rejected' : 'Reject'}
      </button>
      {status === 'error' ? <span className="text-xs text-red-600" data-testid="review-action-error">{message}</span> : null}

      {rejectOpen ? (
        <div
          className="fixed inset-0 z-50 flex items-center justify-center bg-black/40"
          role="dialog"
          aria-modal="true"
          aria-label="Reject application"
        >
          <div className="w-full max-w-md rounded-2xl border border-[#EFE3D3] bg-[#FFF7ED] p-6 text-[#2A2421] shadow-lg">
            <h2 className="text-lg font-semibold text-[#2C2033]">Reject application</h2>
            <p className="mt-1 text-sm text-[#2A2421]/80">
              Rejection requires a structured reason code and an independent second reviewer countersignature.
            </p>
            <div className="mt-4 space-y-3">
              <label className="block text-xs font-medium text-[#2C2033]">
                Reason code
                <select
                  value={reasonCode}
                  onChange={(e) => setReasonCode(e.target.value)}
                  data-testid="reject-reason-select"
                  className="mt-1 w-full rounded-md border border-[#EFE3D3] bg-white px-3 py-2 text-sm text-[#2A2421]"
                >
                  {REJECTION_REASON_CODES.map((opt) => (
                    <option key={opt.value} value={opt.value}>
                      {opt.label}
                    </option>
                  ))}
                </select>
              </label>
              <label className="block text-xs font-medium text-[#2C2033]">
                Second reviewer ID / email
                <input
                  type="text"
                  value={secondReviewer}
                  onChange={(e) => setSecondReviewer(e.target.value)}
                  placeholder="reviewer-2@navdhya.in"
                  data-testid="reject-second-reviewer"
                  className="mt-1 w-full rounded-md border border-[#EFE3D3] bg-white px-3 py-2 text-sm text-[#2A2421]"
                />
              </label>
              <label className="block text-xs font-medium text-[#2C2033]">
                Reviewer note (optional)
                <textarea
                  value={rejectNote}
                  onChange={(e) => setRejectNote(e.target.value)}
                  rows={2}
                  data-testid="reject-note"
                  className="mt-1 w-full rounded-md border border-[#EFE3D3] bg-white px-3 py-2 text-sm text-[#2A2421]"
                />
              </label>
              {rejectError ? (
                <p className="text-xs font-medium text-[#B74717]" data-testid="reject-validation-error">
                  {rejectError}
                </p>
              ) : null}
            </div>
            <div className="mt-4 flex justify-end gap-2">
              <button
                type="button"
                onClick={() => setRejectOpen(false)}
                className="rounded-md border border-[#EFE3D3] px-3 py-1.5 text-xs text-[#2A2421]"
              >
                Cancel
              </button>
              <button
                type="button"
                onClick={submitReject}
                disabled={isPending}
                data-testid="reject-confirm-submit"
                className="rounded-md bg-[#B74717] px-3 py-1.5 text-xs font-medium text-[#FFF7ED] hover:bg-[#F16A24] disabled:opacity-50"
              >
                Submit rejection
              </button>
            </div>
          </div>
        </div>
      ) : null}

      {stepUpOpen ? (
        <div
          className="fixed inset-0 z-50 flex items-center justify-center bg-black/40"
          role="dialog"
          aria-modal="true"
          aria-label="Step-up verification"
        >
          <div className="w-full max-w-sm rounded-2xl border border-border bg-background p-6 shadow-lg">
            <h2 className="text-lg font-semibold">Step-up verification</h2>
            <p className="mt-1 text-sm text-muted-foreground">
              Enter your TOTP code to authorise this sensitive action.
            </p>
            <input
              type="text"
              inputMode="numeric"
              autoComplete="one-time-code"
              value={stepUpCode}
              onChange={(e) => setStepUpCode(e.target.value)}
              placeholder="123456"
              className="mt-4 w-full rounded-md border border-border bg-background px-3 py-2 text-sm"
              data-testid="stepup-code"
            />
            <div className="mt-4 flex justify-end gap-2">
              <button
                type="button"
                onClick={() => {
                  setStepUpOpen(false);
                  setStepUpCode('');
                  setPendingAction(null);
                }}
                className="rounded-md border border-border px-3 py-1.5 text-xs"
              >
                Cancel
              </button>
              <button
                type="button"
                onClick={submitStepUp}
                disabled={isPending || !stepUpCode}
                data-testid="stepup-submit"
                className="rounded-md bg-primary px-3 py-1.5 text-xs font-medium text-primary-foreground disabled:opacity-50"
              >
                {isPending ? 'Verifying…' : 'Verify'}
              </button>
            </div>
          </div>
        </div>
      ) : null}
    </div>
  );
}
