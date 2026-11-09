'use client';

import React, { useState } from 'react';

export interface CouncilApprovalItem {
  id: string;
  booking_id: string;
  temple_id: string;
  state: string;
  devotee_name?: string;
  sankalpa?: string;
  gotra?: string;
  nakshatra?: string;
  scheduled_at?: string;
  sla_expires_at?: string;
  created_at: string;
}

interface TempleCouncilApprovalsViewProps {
  templeId: string;
  initialApprovals: CouncilApprovalItem[];
  onDecide?: (approvalId: string, decision: 'APPROVED' | 'DECLINED', reason?: string) => Promise<void>;
}

export function TempleCouncilApprovalsView({
  templeId,
  initialApprovals,
  onDecide,
}: TempleCouncilApprovalsViewProps) {
  const [approvals, setApprovals] = useState<CouncilApprovalItem[]>(initialApprovals);
  const [busyId, setBusyId] = useState<string | null>(null);
  const [declineDialogId, setDeclineDialogId] = useState<string | null>(null);
  const [declineReason, setDeclineReason] = useState('');
  const [statusMessage, setStatusMessage] = useState<{ text: string; type: 'success' | 'error' } | null>(null);

  async function handleApprove(approvalId: string) {
    if (busyId) return;
    setBusyId(approvalId);
    setStatusMessage(null);
    try {
      if (onDecide) {
        await onDecide(approvalId, 'APPROVED');
      } else {
        const res = await fetch(`/api/temples/${templeId}/council/approvals/${approvalId}/decide`, {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ decision: 'APPROVED' }),
        });
        if (!res.ok) throw new Error('Failed to record approval');
      }
      setApprovals((prev) => prev.filter((a) => a.id !== approvalId));
      setStatusMessage({ text: 'Ritual booking approved by Temple Council.', type: 'success' });
    } catch (e: any) {
      setStatusMessage({ text: e?.message ?? 'Could not approve booking.', type: 'error' });
    } finally {
      setBusyId(null);
    }
  }

  async function handleDeclineConfirm() {
    if (!declineDialogId || busyId) return;
    setBusyId(declineDialogId);
    setStatusMessage(null);
    try {
      if (onDecide) {
        await onDecide(declineDialogId, 'DECLINED', declineReason || undefined);
      } else {
        const res = await fetch(`/api/temples/${templeId}/council/approvals/${declineDialogId}/decide`, {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ decision: 'DECLINED', reason: declineReason || undefined }),
        });
        if (!res.ok) throw new Error('Failed to record decline');
      }
      setApprovals((prev) => prev.filter((a) => a.id !== declineDialogId));
      setStatusMessage({ text: 'Booking declined with reason. Devotee has been notified.', type: 'success' });
      setDeclineDialogId(null);
      setDeclineReason('');
    } catch (e: any) {
      setStatusMessage({ text: e?.message ?? 'Could not decline booking.', type: 'error' });
    } finally {
      setBusyId(null);
    }
  }

  return (
    <div className="bg-[#FFF7ED] min-h-screen p-6 text-[#2A2421]">
      <div className="max-w-5xl mx-auto">
        <div className="mb-6">
          <h1 className="text-2xl font-bold text-[#2C2033]">Temple Council Approval Queue</h1>
          <p className="text-sm text-[#2A2421]/70 mt-1">
            Review and officiate major homa and sacred ritual bookings requiring Head Priest and Council sanction.
          </p>
        </div>

        {statusMessage && (
          <div
            className={`mb-6 p-4 rounded-xl border text-sm font-medium ${
              statusMessage.type === 'success'
                ? 'bg-[#EFE3D3] text-[#2C2033] border-[#2C2033]/20'
                : 'bg-[#B74717]/10 text-[#B74717] border-[#B74717]/30'
            }`}
          >
            {statusMessage.text}
          </div>
        )}

        {approvals.length === 0 ? (
          <div className="bg-white border border-[#EFE3D3] rounded-2xl p-12 text-center shadow-sm">
            <p className="text-lg font-medium text-[#2C2033]">All Temple Council approvals cleared.</p>
            <p className="text-sm text-[#2A2421]/60 mt-1">No pending ritual sanctions awaiting review.</p>
          </div>
        ) : (
          <div className="space-y-4">
            {approvals.map((approval) => (
              <div
                key={approval.id}
                className="bg-white border border-[#EFE3D3] rounded-2xl p-6 shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-6"
              >
                <div className="space-y-2 flex-1">
                  <div className="flex items-center gap-3">
                    <span className="px-3 py-1 rounded-full text-xs font-semibold bg-[#2C2033] text-[#FFF7ED]">
                      Council Sanction Required
                    </span>
                    {approval.sla_expires_at && (
                      <span className="text-xs text-[#B74717] font-medium">
                        SLA Deadline: {new Date(approval.sla_expires_at).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })}
                      </span>
                    )}
                  </div>

                  <h3 className="text-lg font-bold text-[#2C2033]">
                    {approval.devotee_name ?? 'Sacred Devotee'} · Booking #{approval.booking_id.slice(-6)}
                  </h3>

                  {approval.sankalpa && (
                    <p className="text-sm text-[#2A2421] italic bg-[#FFF7ED] p-3 rounded-lg border border-[#EFE3D3]">
                      &quot;{approval.sankalpa}&quot;
                    </p>
                  )}

                  <div className="flex flex-wrap gap-4 text-xs text-[#2A2421]/70 pt-1">
                    {approval.gotra && (
                      <div>
                        <span className="font-semibold text-[#2C2033]">Gotra:</span> {approval.gotra}
                      </div>
                    )}
                    {approval.nakshatra && (
                      <div>
                        <span className="font-semibold text-[#2C2033]">Nakshatra:</span> {approval.nakshatra}
                      </div>
                    )}
                    {approval.scheduled_at && (
                      <div>
                        <span className="font-semibold text-[#2C2033]">Muhurat:</span>{' '}
                        {new Date(approval.scheduled_at).toLocaleString()}
                      </div>
                    )}
                  </div>
                </div>

                <div className="flex items-center gap-3">
                  <button
                    onClick={() => handleApprove(approval.id)}
                    disabled={busyId !== null}
                    className="px-5 py-2.5 rounded-xl bg-[#2C2033] text-[#FFF7ED] text-sm font-semibold hover:bg-[#F16A24] transition-colors disabled:opacity-50"
                  >
                    {busyId === approval.id ? 'Sanctioning…' : 'Approve Ritual'}
                  </button>
                  <button
                    onClick={() => {
                      setDeclineDialogId(approval.id);
                      setDeclineReason('');
                    }}
                    disabled={busyId !== null}
                    className="px-4 py-2.5 rounded-xl border border-[#B74717] text-[#B74717] text-sm font-semibold hover:bg-[#B74717]/10 transition-colors disabled:opacity-50"
                  >
                    Decline
                  </button>
                </div>
              </div>
            ))}
          </div>
        )}

        {/* Decline Confirmation Modal */}
        {declineDialogId && (
          <div className="fixed inset-0 bg-black/40 backdrop-blur-sm z-50 flex items-center justify-center p-4">
            <div className="bg-white border border-[#EFE3D3] rounded-2xl p-6 max-w-md w-full shadow-xl">
              <h3 className="text-lg font-bold text-[#2C2033] mb-2">Decline Council Sanction</h3>
              <p className="text-sm text-[#2A2421]/70 mb-4">
                Please state the reason for declining this sacred ceremony. This will be shared with the devotee along with an automatic refund.
              </p>

              <textarea
                value={declineReason}
                onChange={(e) => setDeclineReason(e.target.value)}
                placeholder="e.g. Inauspicious tithi alignment, temple mandap under renovation..."
                rows={3}
                className="w-full rounded-xl border border-[#EFE3D3] p-3 text-sm text-[#2A2421] focus:border-[#F16A24] focus:outline-none mb-4"
              />

              <div className="flex justify-end gap-3">
                <button
                  onClick={() => setDeclineDialogId(null)}
                  disabled={busyId !== null}
                  className="px-4 py-2 rounded-xl text-sm font-medium text-[#2A2421]/60 hover:text-[#2C2033]"
                >
                  Cancel
                </button>
                <button
                  onClick={handleDeclineConfirm}
                  disabled={busyId !== null}
                  className="px-4 py-2 rounded-xl bg-[#B74717] text-white text-sm font-semibold hover:bg-[#B74717]/90 disabled:opacity-50"
                >
                  {busyId !== null ? 'Declining…' : 'Confirm Decline'}
                </button>
              </div>
            </div>
          </div>
        )}
      </div>
    </div>
  );
}
