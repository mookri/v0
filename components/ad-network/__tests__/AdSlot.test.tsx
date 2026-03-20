import { render, screen } from "@testing-library/react";
import AdSlot from "@/components/ad-network/AdSlot";
import { AdRuntimeProvider } from "@/components/ad-network/runtime/AdRuntimeProvider";
import type { DecisionResponse } from "@/lib/ad-orchestration";

beforeAll(() => {
  class MockIntersectionObserver {
    observe() {}
    unobserve() {}
    disconnect() {}
  }

  Object.defineProperty(window, "IntersectionObserver", {
    writable: true,
    value: MockIntersectionObserver,
  });
});

describe("AdSlot", () => {
  it("renders a precomputed inline decision without static placement hints", () => {
    const decision: DecisionResponse = {
      slotId: "article-inline-1",
      requestId: "req",
      decisionId: "decision",
      placement: "article_inline_1",
      layout: "inline_text",
      reservedSize: { width: "fluid", height: 180, aspectRatio: "4:3" },
      tracking: {
        requestId: "req",
        decisionId: "decision",
        auctionId: "auction",
        endpoint: "/api/ads/track",
        beaconEndpoint: "/api/ads/track-beacon",
        experimentAssignments: {},
        impressionKey: "impression",
        viewableKey: "viewable",
        clickKey: "click",
        renderKey: "render",
      },
      experimentAssignments: {},
      ad: {
        id: "navdhya-house-booking",
        title: "Book Experienced Delhi-NCR Pandits",
        description: "Ensure your rituals are performed with strict Vedic authenticity and precision.",
        ctaText: "Book Now",
        ctaUrl: "/book-pandit",
        sponsorName: "Navdhya",
        layouts: ["inline_text"],
        placements: ["article_inline_1"],
      },
      reason: "filled",
    };

    render(
      <AdRuntimeProvider>
        <AdSlot opportunity={{
          slotId: "article-inline-1",
          surface: "article",
          placementHints: ["article_inline_1"],
          anchorId: "p4",
          anchorIndex: 4,
          reservedSize: { width: "fluid", height: 180, aspectRatio: "4:3" },
          depthBucket: "mid",
          lazy: false,
        }} decision={decision} />
      </AdRuntimeProvider>,
    );

    expect(screen.getByText("Book Experienced Delhi-NCR Pandits")).toBeInTheDocument();
  });
});

