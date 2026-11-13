"use client";

import React, { useEffect, useState, useCallback } from "react";
import { PartnerTrustBadge, type VerificationClaimBadgeDTO } from "@/components/trust/PartnerTrustBadge";

interface PartnerClaimStatus {
  id: string;
  claim_kind: string;
  status: string;
  mode: string;
  confidence: string;
  expires_at?: string | null;
}

interface PartnerStatusPayload {
  application_id: string | null;
  application_status: string;
  trust_summary: VerificationClaimBadgeDTO;
  claims: PartnerClaimStatus[];
  bank_instruments: Array<{ id: string; account_masked: string; ifsc_masked: string; verification_status: string }>;
  tax_registrations: Array<{ id: string; gst_status: string; gstin_masked?: string | null; registration_status: string }>;
  explanation: { headline: string; explanation: string; next_action: string; appealable: boolean };
  poll_interval_ms?: number;
}

/**
 * Partner Onboarding Truth & Appeal Status Screen (T-TR08-03, T-TR08-04, T-TR08-06).
 * Polls live claim status transitions without stale cache blind spots.
 */
export default function PartnerOnboardingStatusPage() {
  const [data, setData] = useState<PartnerStatusPayload | null>(null);
  const [appealStatement, setAppealStatement] = useState("");
  const [appealSubmitted, setAppealSubmitted] = useState<string | null>(null);

  const loadStatus = useCallback(async () => {
    const res = await fetch("/api/v1/onboarding/me/onboarding-status", { cache: "no-store" });
    if (!res.ok) return;
    const json = await res.json();
    if (json?.data) {
      setData(json.data);
    }
  }, []);

  useEffect(() => {
    void loadStatus();
    const intervalMs = data?.poll_interval_ms ?? 15000;
    const timer = setInterval(() => {
      void loadStatus();
    }, intervalMs);
    return () => clearInterval(timer);
  }, [loadStatus, data?.poll_interval_ms]);

  async function submitAppeal(e: React.FormEvent) {
    e.preventDefault();
    if (!data?.application_id || appealStatement.trim().length < 10) return;
    const res = await fetch("/api/v1/onboarding/appeals", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({
        application_id: data.application_id,
        grounds_code: "PARTNER_SUBMITTED_CLARIFICATION",
        applicant_statement: appealStatement,
      }),
    });
    if (res.ok) {
      setAppealSubmitted("Appeal submitted for independent compliance review.");
      setAppealStatement("");
      void loadStatus();
    }
  }

  return (
    <main className="mx-auto max-w-4xl space-y-6 bg-[#FFF7ED] p-6 text-[#2A2421]">
      <div className="flex flex-wrap items-center justify-between gap-4 border-b border-[#EFE3D3] pb-4">
        <div>
          <h1 className="text-2xl font-bold text-[#2C2033]">Verification & Claim Status</h1>
          <p className="text-sm text-[#2A2421]">
            Application state: <strong>{data?.application_status || "Loading..."}</strong>
          </p>
        </div>
        {data?.trust_summary && <PartnerTrustBadge badge={data.trust_summary} />}
      </div>

      {data?.explanation && (
        <section className="rounded-xl border border-[#B74717] bg-white p-4">
          <h2 className="text-base font-semibold text-[#2C2033]">{data.explanation.headline}</h2>
          <p className="mt-1 text-sm text-[#2A2421]">{data.explanation.explanation}</p>
          <p className="mt-2 text-xs font-medium text-[#B74717]">
            Next Action: {data.explanation.next_action}
          </p>
        </section>
      )}

      <section className="space-y-3">
        <h2 className="text-sm font-bold uppercase tracking-wide text-[#2C2033]">Active & Historical Claims</h2>
        <div className="grid gap-3 sm:grid-cols-2">
          {(data?.claims || []).map((claim) => (
            <div key={claim.id} className="rounded-lg border border-[#EFE3D3] bg-white p-3 text-sm">
              <div className="flex items-center justify-between">
                <span className="font-semibold text-[#2C2033]">{claim.claim_kind}</span>
                <span className="rounded bg-[#EFE3D3] px-2 py-0.5 text-xs text-[#2C2033]">{claim.mode}</span>
              </div>
              <p className="mt-1 text-xs">
                Status: {claim.status} · Confidence: {claim.confidence}
              </p>
              {claim.expires_at && (
                <p className="mt-0.5 text-xs text-[#B74717]">Valid until: {claim.expires_at.slice(0, 10)}</p>
              )}
            </div>
          ))}
        </div>
      </section>

      {data?.explanation?.appealable && (
        <form onSubmit={submitAppeal} className="space-y-3 rounded-xl border border-[#2C2033] bg-white p-4">
          <h2 className="text-base font-bold text-[#2C2033]">Submit Independent Appeal</h2>
          <textarea
            value={appealStatement}
            onChange={(e) => setAppealStatement(e.target.value)}
            placeholder="Explain clarification or reference uploaded supporting documents..."
            className="w-full rounded border border-[#EFE3D3] p-2 text-sm text-[#2A2421]"
            rows={3}
          />
          <button
            type="submit"
            className="rounded-md bg-[#F16A24] px-4 py-2 text-sm font-semibold text-[#FFF7ED] hover:bg-[#B74717]"
          >
            Submit Appeal
          </button>
          {appealSubmitted && <p className="text-xs font-medium text-[#2C2033]">{appealSubmitted}</p>}
        </form>
      )}
    </main>
  );
}

