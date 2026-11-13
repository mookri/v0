"use client";

import React, { useState } from "react";
import { PartnerTrustBadge, type VerificationClaimBadgeDTO } from "@/components/trust/PartnerTrustBadge";

export interface ReviewClaimItem {
  id: string;
  claim_kind: string;
  mode: "native" | "manual" | "official" | "financial";
  status: string;
  confidence: string;
  legacy_asserted?: boolean;
  reason_code?: string | null;
  policy_version?: string;
  expires_at?: string | null;
}

export interface ReviewDocumentItem {
  id: string;
  kind: string;
  status: string;
  integrity_state: string;
  sha256?: string;
}

export interface OnboardingReviewWorkspaceProps {
  applicationId: string;
  track: string;
  status: string;
  badge: VerificationClaimBadgeDTO;
  claims: ReviewClaimItem[];
  documents: ReviewDocumentItem[];
  explanationHeadline?: string;
  explanationBody?: string;
  maskedBankAccount?: string | null;
  maskedIfsc?: string | null;
  onRequestAccessLink?: (documentId: string) => Promise<{ url: string }>;
}

const MODE_LABELS: Record<string, string> = {
  official: "Official Confirmed",
  financial: "Financial Verified",
  manual: "Manual Review",
  native: "Native Check",
};

/**
 * Mode-honest Admin Onboarding Review Workspace (§12.3 / T-TR08-01).
 * Surfaces claim evidence modes, SHA-256 document integrity, private signed access links,
 * and plain-language decision explanation preview using Sacred Ember tokens only.
 */
export function OnboardingReviewWorkspace({
  applicationId,
  track,
  status,
  badge,
  claims,
  documents,
  explanationHeadline,
  explanationBody,
  maskedBankAccount,
  maskedIfsc,
  onRequestAccessLink,
}: OnboardingReviewWorkspaceProps) {
  const [signedLinks, setSignedLinks] = useState<Record<string, string>>({});

  async function handleInspectDoc(docId: string) {
    if (!onRequestAccessLink) return;
    const res = await onRequestAccessLink(docId);
    setSignedLinks((prev) => ({ ...prev, [docId]: res.url }));
  }

  return (
    <section
      data-testid="onboarding-review-workspace"
      className="space-y-4 rounded-xl border border-[#EFE3D3] bg-[#FFF7ED] p-5 text-[#2A2421]"
    >
      <header className="flex flex-wrap items-center justify-between gap-3 border-b border-[#EFE3D3] pb-3">
        <div>
          <p className="text-xs font-semibold uppercase tracking-wide text-[#B74717]">
            Application {applicationId} · {track}
          </p>
          <h2 className="text-lg font-bold text-[#2C2033]">Status: {status}</h2>
        </div>
        <PartnerTrustBadge badge={badge} />
      </header>

      <div className="space-y-2">
        <h3 className="text-xs font-bold uppercase tracking-wider text-[#2C2033]">
          Verification Claims & Evidence Modes
        </h3>
        <div className="grid gap-2 sm:grid-cols-2">
          {claims.map((claim) => (
            <div
              key={claim.id}
              className="rounded-lg border border-[#EFE3D3] bg-white p-3 text-sm"
            >
              <div className="flex items-center justify-between gap-2">
                <span className="font-semibold text-[#2C2033]">{claim.claim_kind}</span>
                <span className="rounded border border-[#B74717] bg-[#EFE3D3] px-2 py-0.5 text-xs font-medium text-[#2C2033]">
                  {claim.legacy_asserted ? "Legacy Asserted" : MODE_LABELS[claim.mode] || claim.mode}
                </span>
              </div>
              <p className="mt-1 text-xs text-[#2A2421]">
                State: {claim.status} · Confidence: {claim.confidence} · Policy:{" "}
                {claim.policy_version || "policy-v1"}
              </p>
            </div>
          ))}
        </div>
      </div>

      {(maskedBankAccount || maskedIfsc) && (
        <div className="rounded-lg border border-[#EFE3D3] bg-white p-3 text-xs">
          <strong className="text-[#2C2033]">Masked Payout Instrument:</strong>{" "}
          <span>{maskedBankAccount || "••••"}</span>{" "}
          {maskedIfsc ? <span>({maskedIfsc})</span> : null}
        </div>
      )}

      <div className="space-y-2">
        <h3 className="text-xs font-bold uppercase tracking-wider text-[#2C2033]">
          Submitted Documents (Private Access Link)
        </h3>
        <ul className="space-y-2">
          {documents.map((doc) => (
            <li
              key={doc.id}
              className="flex flex-wrap items-center justify-between gap-2 rounded-lg border border-[#EFE3D3] bg-white p-3 text-xs"
            >
              <div>
                <span className="font-semibold text-[#2C2033]">{doc.kind}</span> · Review:{" "}
                <span>{doc.status}</span> · Integrity:{" "}
                <span className="font-medium text-[#B74717]">{doc.integrity_state}</span>
                {doc.sha256 ? <span className="ml-2 text-[#2A2421]">sha256:{doc.sha256.slice(0, 10)}…</span> : null}
              </div>
              <div className="flex items-center gap-2">
                <button
                  type="button"
                  onClick={() => handleInspectDoc(doc.id)}
                  className="rounded border border-[#2C2033] bg-[#2C2033] px-2.5 py-1 text-xs font-medium text-[#FFF7ED] hover:bg-[#B74717]"
                >
                  Open Signed Copy
                </button>
                {signedLinks[doc.id] && (
                  <a
                    href={signedLinks[doc.id]}
                    target="_blank"
                    rel="noopener noreferrer"
                    className="underline text-[#B74717]"
                  >
                    Download Attachment
                  </a>
                )}
              </div>
            </li>
          ))}
        </ul>
      </div>

      {explanationHeadline && (
        <div className="rounded-lg border border-[#F16A24] bg-[#FFF7ED] p-3 text-xs">
          <p className="font-semibold text-[#2C2033]">
            Applicant Explanation Preview: {explanationHeadline}
          </p>
          {explanationBody ? <p className="mt-1 text-[#2A2421]">{explanationBody}</p> : null}
        </div>
      )}
    </section>
  );
}

export default OnboardingReviewWorkspace;

