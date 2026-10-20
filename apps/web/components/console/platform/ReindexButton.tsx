'use client';

import { useState, useTransition } from 'react';

/**
 * W3 capability surfacing: a "Reindex search" control. Triggers
 * POST /api/admin/search/reindex and reports the resulting stats. The
 * capability `search:index:reindex` gates the backend; a denial surfaces as
 * the mapped error code.
 */
export function ReindexButton() {
  const [status, setStatus] = useState<'idle' | 'pending' | 'done' | 'error'>('idle');
  const [message, setMessage] = useState<string>('');
  const [isPending, startTransition] = useTransition();

  function handleClick() {
    startTransition(async () => {
      setStatus('pending');
      try {
        const res = await fetch('/api/admin/search/reindex', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({}),
        });
        const body = (await res.json().catch(() => null)) as { data?: { data?: { total?: number; upserted?: number; deleted?: number; terms?: number; version?: number }; meta?: unknown }; error?: { code?: string; message?: string } } | null;
        if (!res.ok) {
          setStatus('error');
          setMessage(body?.error?.code ?? 'REQUEST_FAILED');
          return;
        }
        const d = body?.data?.data ?? {};
        const total = (d as { total?: number })?.total ?? 0;
        const upserted = (d as { upserted?: number })?.upserted ?? 0;
        const deleted = (d as { deleted?: number })?.deleted ?? 0;
        const terms = (d as { terms?: number })?.terms ?? 0;
        setStatus('done');
        setMessage(`Indexed ${total} (upserted ${upserted}, deleted ${deleted}, terms ${terms})`);
      } catch {
        setStatus('error');
        setMessage('NETWORK_ERROR');
      }
    });
  }

  return (
    <div className="flex items-center gap-3">
      <button
        type="button"
        onClick={handleClick}
        disabled={isPending}
        data-testid="reindex-button"
        className="rounded-md border border-border bg-background px-3 py-1.5 text-xs font-medium text-foreground hover:bg-accent/10 disabled:opacity-50"
      >
        {isPending ? 'Reindexing…' : 'Reindex search'}
      </button>
      {status === 'done' ? <span className="text-xs text-muted-foreground">{message}</span> : null}
      {status === 'error' ? <span className="text-xs text-red-600">{message}</span> : null}
    </div>
  );
}
