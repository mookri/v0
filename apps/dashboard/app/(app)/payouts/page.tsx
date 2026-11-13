'use client';

import React, { useCallback, useEffect, useState } from 'react';
import {
  StepUpChallengeModal,
  type StepUpChallengeRequest,
} from '@/components/StepUpChallengeModal';

export interface PayoutQueueItem {
  id: string;
  beneficiary_id: string;
  amount_minor: number;
  currency: string;
  status: string;
  maker_id: string;
  checker_id?: string | null;
  checklist?: {
    dual_custody_eligible: boolean;
    period_open: boolean;
  };
}

const FIVE_POINT_CHECKS = [
  { id: 'beneficiary', label: 'Beneficiary identity verified via KYB/KYC claims' },
  { id: 'bank_instrument', label: 'Active masked bank instrument matches penny-drop record' },
  { id: 'tax_withholding', label: 'TDS withholding / GST invoice verified on settlement' },
  { id: 'period_open', label: 'Accounting period is OPEN (no locked period post)' },
  { id: 'dual_custody', label: 'Approver (Checker) is distinct from Maker' },
] as const;

export default function PayoutsPage() {
  const [payouts, setPayouts] = useState<PayoutQueueItem[]>([]);
  const [selectedId, setSelectedId] = useState<string | null>(null);
  const [snapshotBeneficiary, setSnapshotBeneficiary] = useState<string | null>(null);
  const [checkedItems, setCheckedItems] = useState<Record<string, boolean>>({});
  const [statusError, setStatusError] = useState<string | null>(null);
  const [actionBanner, setActionBanner] = useState<string | null>(null);
  const [loading, setLoading] = useState(true);
  const [submitting, setSubmitting] = useState(false);
  const [challenge, setChallenge] = useState<StepUpChallengeRequest | null>(null);

  const loadQueue = useCallback(async () => {
    setLoading(true);
    setStatusError(null);
    try {
      const res = await fetch('/api/ops/payouts', { cache: 'no-store' });
      const body = (await res.json().catch(() => ({}))) as {
        maker_checker_payouts?: PayoutQueueItem[];
        message?: string;
      };
      if (!res.ok) {
        setStatusError(body.message || `Payout queue unavailable (${res.status})`);
        setLoading(false);
        return;
      }

      const nextList = body.maker_checker_payouts ?? [];
      setPayouts(nextList);

      // Invalidate 5-point checklist if selected beneficiary mutated since queue snapshot.
      if (selectedId && snapshotBeneficiary) {
        const current = nextList.find((p) => p.id === selectedId);
        if (current && current.beneficiary_id !== snapshotBeneficiary) {
          setCheckedItems({});
          setSnapshotBeneficiary(current.beneficiary_id);
          setStatusError('Beneficiary changed since verification started — checklist reset; re-verify all 5 items.');
        }
      }
    } catch {
      setStatusError('Network error loading maker/checker payouts.');
    } finally {
      setLoading(false);
    }
  }, [selectedId, snapshotBeneficiary]);

  useEffect(() => {
    void loadQueue();
  }, [loadQueue]);

  const selectedPayout = payouts.find((p) => p.id === selectedId) ?? null;
  const allFiveChecked = FIVE_POINT_CHECKS.every((c) => Boolean(checkedItems[c.id]));

  const selectPayout = (item: PayoutQueueItem) => {
    setSelectedId(item.id);
    setSnapshotBeneficiary(item.beneficiary_id);
    setCheckedItems({});
    setActionBanner(null);
    setStatusError(null);
  };

  const executeApproval = async (payout: PayoutQueueItem, idempotencyKey: string) => {
    setSubmitting(true);
    setStatusError(null);
    setActionBanner(null);
    try {
      const res = await fetch(`/api/ops/payouts/${encodeURIComponent(payout.id)}/approve`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Idempotency-Key': idempotencyKey,
        },
        body: JSON.stringify({
          expected_beneficiary_id: snapshotBeneficiary ?? payout.beneficiary_id,
          checklist_confirmed: true,
        }),
      });
      const body = (await res.json().catch(() => ({}))) as {
        error?: string;
        message?: string;
        challengeUrl?: string;
      };

      if (res.status === 403 && body.error === 'step_up_required') {
        setChallenge({
          actionLabel: `Payout ${payout.id}`,
          challengeUrl: body.challengeUrl,
          message: body.message,
          idempotencyKey,
          onRetryAfterStepUp: (preservedKey) => executeApproval(payout, preservedKey),
        });
        return;
      }

      if (!res.ok) {
        if (body.error === 'beneficiary_changed') {
          setCheckedItems({});
          await loadQueue();
        }
        throw new Error(body.message || `Approval rejected (${res.status})`);
      }

      setActionBanner(`Approved payout ${payout.id} (Idempotency-Key: ${idempotencyKey}).`);
      setCheckedItems({});
      await loadQueue();
    } catch (err) {
      setStatusError(err instanceof Error ? err.message : 'Approval request failed.');
    } finally {
      setSubmitting(false);
    }
  };

  return (
    <div className="space-y-6 text-[#2A2421]">
      <header className="flex flex-wrap items-center justify-between gap-4 border-b border-[#EFE3D3] pb-4">
        <div>
          <p className="text-xs font-semibold uppercase tracking-widest text-[#B74717]">Dual-Custody Treasury</p>
          <h1 className="text-2xl font-bold text-[#2C2033]">Maker / Checker Payouts</h1>
        </div>
        <button
          type="button"
          onClick={() => void loadQueue()}
          className="rounded-lg border border-[#2C2033] px-4 py-2 text-sm font-semibold text-[#2C2033] hover:bg-[#EFE3D3]"
        >
          Refresh Queue
        </button>
      </header>

      {statusError ? (
        <div role="alert" className="rounded-xl border border-[#B74717] bg-[#EFE3D3] px-4 py-3 text-sm font-medium text-[#B74717]">
          {statusError}
        </div>
      ) : null}

      {actionBanner ? (
        <div role="status" className="rounded-xl border border-[#2C2033] bg-white px-4 py-3 text-sm font-medium text-[#2C2033]">
          {actionBanner}
        </div>
      ) : null}

      <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <section className="lg:col-span-2 rounded-xl border border-[#EFE3D3] bg-white overflow-hidden">
          <div className="border-b border-[#EFE3D3] bg-[#EFE3D3]/40 px-5 py-3">
            <h2 className="text-sm font-semibold text-[#2C2033]">Pending & Recent Dual-Custody Payouts</h2>
          </div>
          {loading ? (
            <p className="p-6 text-sm text-[#2A2421]">Loading payout queue...</p>
          ) : payouts.length === 0 ? (
            <p className="p-6 text-sm text-[#2A2421]">No maker/checker payouts found for this tenant.</p>
          ) : (
            <table className="w-full text-left text-sm">
              <thead className="border-b border-[#EFE3D3] bg-[#FFF7ED] text-xs uppercase text-[#2C2033]">
                <tr>
                  <th className="px-4 py-3">Payout</th>
                  <th className="px-4 py-3">Beneficiary</th>
                  <th className="px-4 py-3">Amount</th>
                  <th className="px-4 py-3">Maker</th>
                  <th className="px-4 py-3">Status</th>
                  <th className="px-4 py-3">Action</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-[#EFE3D3]">
                {payouts.map((item) => (
                  <tr key={item.id} className={selectedId === item.id ? 'bg-[#FFF7ED]' : ''}>
                    <td className="px-4 py-3 font-mono text-xs text-[#2C2033]">{item.id}</td>
                    <td className="px-4 py-3 font-mono text-xs">{item.beneficiary_id}</td>
                    <td className="px-4 py-3 font-semibold text-[#2C2033]">
                      {item.currency} {(item.amount_minor / 100).toFixed(2)}
                    </td>
                    <td className="px-4 py-3 text-xs">{item.maker_id}</td>
                    <td className="px-4 py-3">
                      <span className="inline-flex rounded-full bg-[#EFE3D3] px-2.5 py-0.5 text-xs font-semibold text-[#2C2033]">
                        {item.status}
                      </span>
                    </td>
                    <td className="px-4 py-3">
                      <button
                        type="button"
                        onClick={() => selectPayout(item)}
                        className="rounded-lg bg-[#2C2033] px-3 py-1.5 text-xs font-semibold text-[#FFF7ED] hover:bg-[#3d2e47]"
                      >
                        Review
                      </button>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          )}
        </section>

        <aside className="rounded-xl border border-[#EFE3D3] bg-white p-5 space-y-4">
          <h2 className="text-base font-bold text-[#2C2033]">5-Point Checker Checklist</h2>
          {!selectedPayout ? (
            <p className="text-sm text-[#2A2421]">Select a payout from the queue to inspect dual-custody controls.</p>
          ) : (
            <>
              <div className="rounded-lg bg-[#FFF7ED] p-3 text-xs space-y-1 border border-[#EFE3D3]">
                <p><span className="font-semibold text-[#2C2033]">ID:</span> {selectedPayout.id}</p>
                <p><span className="font-semibold text-[#2C2033]">Beneficiary Snapshot:</span> {snapshotBeneficiary}</p>
                <p><span className="font-semibold text-[#2C2033]">Maker:</span> {selectedPayout.maker_id}</p>
              </div>

              <div className="space-y-2.5">
                {FIVE_POINT_CHECKS.map((check) => (
                  <label key={check.id} className="flex items-start gap-2.5 text-xs text-[#2A2421] cursor-pointer">
                    <input
                      type="checkbox"
                      checked={Boolean(checkedItems[check.id])}
                      onChange={(e) =>
                        setCheckedItems((prev) => ({ ...prev, [check.id]: e.target.checked }))
                      }
                      className="mt-0.5 h-4 w-4 accent-[#F16A24]"
                    />
                    <span>{check.label}</span>
                  </label>
                ))}
              </div>

              <button
                type="button"
                disabled={!allFiveChecked || submitting || selectedPayout.status !== 'PENDING_APPROVAL'}
                onClick={() => {
                  const key = typeof crypto !== 'undefined' && crypto.randomUUID
                    ? `payout-approve-${crypto.randomUUID()}`
                    : `payout-approve-${Date.now()}`;
                  void executeApproval(selectedPayout, key);
                }}
                className="w-full rounded-lg bg-[#F16A24] px-4 py-2.5 text-sm font-semibold text-[#FFF7ED] hover:bg-[#B74717] disabled:opacity-40"
              >
                {submitting ? 'Submitting Approval...' : 'Approve Payout (ACR 2)'}
              </button>
            </>
          )}
        </aside>
      </div>

      <StepUpChallengeModal challenge={challenge} onClose={() => setChallenge(null)} />
    </div>
  );
}

