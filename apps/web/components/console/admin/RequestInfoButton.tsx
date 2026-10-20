'use client';

import { useState, useTransition } from 'react';

/**
 * W3 capability surfacing: a "Request more information" action on a review
 * queue row. No step-up is required (`capability:onboarding:review:view`),
 * so it can be pressed directly. Approve/reject are step-up-2 gated and need
 * a step-up verification UI that does not exist in the console yet — those
 * remain documented in `docs/PLATFORM-FEATURES-AND-CONSOLE-PLAN.md` (W3).
 */
export function RequestInfoButton({ applicationId }: { applicationId: string }) {
  const [status, setStatus] = useState<'idle' | 'pending' | 'done' | 'error'>('idle');
  const [message, setMessage] = useState<string>('');
  const [isPending, startTransition] = useTransition();

  function handleClick() {
    startTransition(async () => {
      setStatus('pending');
      try {
        const res = await fetch(`/api/admin/managers/${encodeURIComponent(applicationId)}/request-info`, {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
        });
        const body = (await res.json().catch(() => null)) as { data?: unknown; error?: { code?: string; message?: string } } | null;
        if (!res.ok) {
          setStatus('error');
          setMessage(body?.error?.code ?? 'REQUEST_FAILED');
          return;
        }
        setStatus('done');
        setMessage('Requested');
      } catch {
        setStatus('error');
        setMessage('NETWORK_ERROR');
      }
    });
  }

  return (
    <button
      type="button"
      onClick={handleClick}
      disabled={isPending || status === 'done'}
      data-testid="request-info-button"
      className="rounded-md border border-border bg-background px-2 py-1 text-xs font-medium text-foreground hover:bg-accent/10 disabled:opacity-50"
    >
      {status === 'pending' ? 'Requesting…' : status === 'done' ? `✓ ${message}` : 'Request info'}
      {status === 'error' ? ` (${message})` : ''}
    </button>
  );
}
