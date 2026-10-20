'use client';

import { useState, useTransition } from 'react';

/**
 * W3 capability surfacing: approve / reject actions on a review queue row.
 *
 * Both verbs are `stepup:2` gated. When the backend answers
 * 403 `FORBIDDEN` with a step-up-required signal, this component collects a
 * TOTP code, verifies it through `/api/mfa/verify` (which rotates the session
 * cookie on success), and retries the original action.
 */
export function ReviewActions({ applicationId }: { applicationId: string }) {
  const [status, setStatus] = useState<'idle' | 'pending' | 'done' | 'error'>('idle');
  const [message, setMessage] = useState<string>('');
  const [stepUpOpen, setStepUpOpen] = useState(false);
  const [stepUpCode, setStepUpCode] = useState('');
  const [pendingAction, setPendingAction] = useState<'approve' | 'reject' | null>(null);
  const [isPending, startTransition] = useTransition();

  async function runAction(action: 'approve' | 'reject', reasonCode?: string) {
    setStatus('pending');
    const url = `/api/admin/managers/${encodeURIComponent(applicationId)}/${action}`;
    const init: RequestInit =
      action === 'reject'
        ? {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ reason_code: reasonCode ?? 'manual_review', note: null, second_reviewer: '' }),
          }
        : { method: 'POST', headers: { 'Content-Type': 'application/json' } };

    const res = await fetch(url, init);
    const body = (await res.json().catch(() => null)) as { data?: unknown; error?: { code?: string; message?: string } } | null;

    if (res.status === 403 && body?.error?.code === 'FORBIDDEN') {
      // Step-up required (or a real denial). Open the challenge; if it was a
      // genuine denial the verify step will still fail and we surface it.
      setPendingAction(action);
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
        // Session cookie rotated by the BFF; retry the original action.
        setStepUpOpen(false);
        setStepUpCode('');
        await runAction(pendingAction);
        setPendingAction(null);
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
        className="rounded-md border border-emerald-500/40 bg-emerald-500/10 px-2 py-1 text-xs font-medium text-emerald-700 hover:bg-emerald-500/20 disabled:opacity-50"
      >
        {status === 'pending' ? '…' : status === 'done' && message === 'Approved' ? '✓ Approved' : 'Approve'}
      </button>
      <button
        type="button"
        onClick={() => runAction('reject')}
        disabled={isPending || status === 'done'}
        data-testid="reject-button"
        className="rounded-md border border-red-500/40 bg-red-500/10 px-2 py-1 text-xs font-medium text-red-700 hover:bg-red-500/20 disabled:opacity-50"
      >
        {status === 'done' && message === 'Rejected' ? '✓ Rejected' : 'Reject'}
      </button>
      {status === 'error' ? <span className="text-xs text-red-600">{message}</span> : null}

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
