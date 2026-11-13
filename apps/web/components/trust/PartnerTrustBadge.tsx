"use client";

import React from "react";
import { ShieldCheck, FileCheck2, Clock, AlertCircle } from "lucide-react";

export type VerificationBadgeTier =
  | "official_verified"
  | "financial_verified"
  | "manually_reviewed"
  | "legacy_revalidation_pending"
  | "self_declared"
  | "unverified";

export interface VerificationClaimBadgeDTO {
  badge_tier: VerificationBadgeTier | string;
  label?: string;
  highest_mode?: "official" | "financial" | "manual" | "native" | null;
  legacy_asserted?: boolean;
  verified_at?: string | null;
  expires_at?: string | null;
  policy_version?: string | null;
  claim_kinds?: string[];
}

interface PartnerTrustBadgeProps {
  badge: VerificationClaimBadgeDTO;
  className?: string;
}

/**
 * Evidence-backed partner trust badge (T-TR08-05 / Closes T-02).
 * Styled exclusively with Sacred Ember palette (AGENTS.md) — zero green tokens.
 */
export function PartnerTrustBadge({ badge, className = "" }: PartnerTrustBadgeProps) {
  const isOfficial =
    badge.badge_tier === "official_verified" || badge.highest_mode === "official";
  const isFinancial =
    badge.badge_tier === "financial_verified" || badge.highest_mode === "financial";
  const isLegacy =
    badge.badge_tier === "legacy_revalidation_pending" || badge.legacy_asserted === true;
  const isManual =
    badge.badge_tier === "manually_reviewed" || badge.highest_mode === "manual";

  if (isOfficial || isFinancial) {
    const label = isOfficial
      ? badge.label || "Verified (Official Registry)"
      : badge.label || "Verified (Financial Instrument)";

    return (
      <span
        data-testid="partner-trust-badge"
        data-tier={badge.badge_tier}
        className={`inline-flex items-center gap-1.5 rounded-full border border-[#F16A24] bg-[#2C2033] px-3 py-1 text-xs font-semibold text-[#FFF7ED] ${className}`}
      >
        <ShieldCheck className="h-3.5 w-3.5 text-[#F16A24]" aria-hidden="true" />
        <span>{label}</span>
      </span>
    );
  }

  if (isLegacy) {
    return (
      <span
        data-testid="partner-trust-badge"
        data-tier="legacy_revalidation_pending"
        className={`inline-flex items-center gap-1.5 rounded-full border border-[#EFE3D3] bg-[#FFF7ED] px-3 py-1 text-xs font-medium text-[#2A2421] ${className}`}
      >
        <Clock className="h-3.5 w-3.5 text-[#B74717]" aria-hidden="true" />
        <span>Previously Listed — Re-review Pending</span>
      </span>
    );
  }

  if (isManual) {
    return (
      <span
        data-testid="partner-trust-badge"
        data-tier="manually_reviewed"
        className={`inline-flex items-center gap-1.5 rounded-full border border-[#B74717] bg-[#EFE3D3] px-3 py-1 text-xs font-medium text-[#2C2033] ${className}`}
      >
        <FileCheck2 className="h-3.5 w-3.5 text-[#B74717]" aria-hidden="true" />
        <span>
          {badge.label || "Manually Reviewed"}
          {badge.verified_at ? ` (${badge.verified_at.slice(0, 7)})` : ""}
        </span>
      </span>
    );
  }

  return (
    <span
      data-testid="partner-trust-badge"
      data-tier="unverified"
      className={`inline-flex items-center gap-1.5 rounded-full border border-[#EFE3D3] bg-[#FFF7ED] px-3 py-1 text-xs font-medium text-[#2A2421] ${className}`}
    >
      <AlertCircle className="h-3.5 w-3.5 text-[#2A2421]" aria-hidden="true" />
      <span>{badge.label || "Unverified"}</span>
    </span>
  );
}

export default PartnerTrustBadge;

