import React from "react";
import { render, screen } from "@testing-library/react";
import "@testing-library/jest-dom";
import { PartnerTrustBadge } from "../PartnerTrustBadge";
import { OnboardingReviewWorkspace } from "@/components/console/admin/OnboardingReviewWorkspace";

describe("Sacred Ember PartnerTrustBadge & OnboardingReviewWorkspace (T-02)", () => {
  it("renders official and manual trust badges without any green CSS classes", () => {
    const { container: c1 } = render(
      <PartnerTrustBadge
        badge={{
          badge_tier: "official_verified",
          highest_mode: "official",
          label: "Verified (Official Registry)",
        }}
      />
    );
    expect(screen.getByText("Verified (Official Registry)")).toBeInTheDocument();
    expect(c1.innerHTML).not.toMatch(/emerald|green/i);
    expect(c1.innerHTML).toContain("#2C2033");

    const { container: c2 } = render(
      <PartnerTrustBadge
        badge={{
          badge_tier: "manually_reviewed",
          highest_mode: "manual",
          verified_at: "2026-11-11T10:00:00Z",
        }}
      />
    );
    expect(screen.getByText(/Manually Reviewed/)).toBeInTheDocument();
    expect(c2.innerHTML).not.toMatch(/emerald|green/i);
    expect(c2.innerHTML).toContain("#EFE3D3");
  });

  it("renders OnboardingReviewWorkspace with masked bank details and mode pills", () => {
    const { container } = render(
      <OnboardingReviewWorkspace
        applicationId="app_123"
        track="pandit"
        status="UNDER_REVIEW"
        badge={{ badge_tier: "manually_reviewed", highest_mode: "manual" }}
        claims={[
          {
            id: "clm_1",
            claim_kind: "qualification",
            mode: "manual",
            status: "granted",
            confidence: "medium",
          },
        ]}
        documents={[
          {
            id: "doc_1",
            kind: "certification",
            status: "VERIFIED",
            integrity_state: "VERIFIED",
            sha256: "abcdef1234567890",
          },
        ]}
        maskedBankAccount="••••5544"
        maskedIfsc="HDFC0••••••"
        explanationHeadline="Manual review completed"
      />
    );

    expect(screen.getByText(/Application app_123/)).toBeInTheDocument();
    expect(screen.getByText(/••••5544/)).toBeInTheDocument();
    expect(screen.getByText(/Manual Review/)).toBeInTheDocument();
    expect(container.innerHTML).not.toMatch(/emerald|green/i);
  });
});

