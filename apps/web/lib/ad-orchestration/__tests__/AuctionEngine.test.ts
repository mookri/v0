import type { RankedCandidate } from "@/lib/ad-orchestration";
import { AuctionEngine } from "@/lib/ad-orchestration/AuctionEngine";

function rankedCandidate(id: string, score: number, revenueEcpm: number, model: any): RankedCandidate {
  return {
    ad: {
      id,
      title: id,
      ctaText: "cta",
      ctaUrl: "/",
      layouts: ["native_in_content"],
      placements: ["article_inline_1"],
      budget: { model, bidFloor: revenueEcpm, bidShadeAlpha: 0.7 },
    },
    predictedCtr: 0.02,
    predictedCvr: 0.003,
    relevance: 0.5,
    qualityScore: 0.6,
    revenueEcpm,
    uxPenalty: 0,
    totalScore: score,
  };
}

const opportunity = {
  slotId: "article-inline",
  surface: "article" as const,
  placementHints: ["article_inline_1"] as const,
  anchorId: "p4",
  anchorIndex: 4,
  reservedSize: { width: "fluid" as const, height: 180, aspectRatio: "4:3" },
  depthBucket: "mid" as const,
  lazy: false,
};

describe("AuctionEngine", () => {
  it("clears second-price auctions against the next bid plus epsilon", () => {
    const engine = new AuctionEngine();
    const outcome = engine.run(
      [
        rankedCandidate("winner", 1.2, 600, "second_price_auction"),
        rankedCandidate("runner-up", 1.1, 510, "CPM"),
      ],
      opportunity,
    );

    expect(outcome.winner?.ad.id).toBe("winner");
    expect(outcome.auctionType).toBe("second_price");
    expect(outcome.clearingPrice).toBeGreaterThanOrEqual(510);
  });

  it("honors guaranteed demand before open auction candidates", () => {
    const engine = new AuctionEngine();
    const outcome = engine.run(
      [
        rankedCandidate("auction", 1.5, 800, "first_price_auction"),
        rankedCandidate("guaranteed", 1.1, 500, "programmatic_guaranteed_cpm"),
      ],
      opportunity,
    );

    expect(outcome.winner?.ad.id).toBe("guaranteed");
    expect(outcome.auctionType).toBe("guaranteed");
  });
});

