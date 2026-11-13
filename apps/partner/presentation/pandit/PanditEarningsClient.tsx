'use client';

import { useEffect, useState } from 'react';
import { PanditEarningsView, type PanditEarningsPayload } from '@/presentation/pandit/PanditEarningsView';

export function PanditEarningsClient({
  initialEarnings,
  initialError,
}: {
  initialEarnings?: PanditEarningsPayload | null;
  initialError?: string | null;
}) {
  const [earnings, setEarnings] = useState<PanditEarningsPayload | null>(initialEarnings ?? null);
  const [error, setError] = useState<string | null>(initialError ?? null);
  const [loading, setLoading] = useState(!initialEarnings && !initialError);

  useEffect(() => {
    if (initialEarnings || initialError) return;
    let cancelled = false;

    async function load() {
      try {
        const res = await fetch('/api/partner/earnings', { cache: 'no-store', credentials: 'same-origin' });
        const body = (await res.json().catch(() => null)) as {
          data?: PanditEarningsPayload;
          error?: { code?: string; message?: string };
        } | null;
        if (cancelled) return;
        if (!res.ok || !body?.data) {
          setError(body?.error?.message ?? `Earnings service returned HTTP ${res.status}.`);
          setEarnings(null);
        } else {
          setEarnings(body.data);
          setError(null);
        }
      } catch {
        if (!cancelled) {
          setError('Unable to reach partner earnings ledger.');
        }
      } finally {
        if (!cancelled) setLoading(false);
      }
    }

    void load();
    return () => {
      cancelled = true;
    };
  }, [initialEarnings, initialError]);

  if (loading) {
    return <p className="text-sm text-[#2A2421]/80">Loading ledger settlements…</p>;
  }

  return <PanditEarningsView earnings={earnings} error={error} />;
}

