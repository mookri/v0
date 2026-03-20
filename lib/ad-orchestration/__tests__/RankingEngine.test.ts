import type { Ad } from "@/components/ad-network/core/types";
import { RankingEngine } from "@/lib/ad-orchestration/RankingEngine";
import type { RankingContext } from "@/lib/ad-orchestration";

function buildAd(overrides: Partial<Ad>): Ad {
  return {
    id: overrides.id ?? "ad",
    title: overrides.title ?? "Ad title",
    ctaText: overrides.ctaText ?? "Learn more",
    ctaUrl: overrides.ctaUrl ?? "/book",
    layouts: overrides.layouts ?? ["native_in_content"],
    placements: overrides.placements ?? ["article_inline_1"],
    status: overrides.status ?? "active",
    type: overrides.type ?? "native",
    budget: overrides.budget ?? { model: "CPC", bidFloor: 400 },
    ...overrides,
  };
}

const context: RankingContext = {
  opportunity: {
    slotId: "article-inline",
    surface: "article",
    placementHints: ["article_inline_1"],
    anchorId: "paragraph-4",
    anchorIndex: 4,
    reservedSize: { width: "fluid", height: 180, aspectRatio: "4:3" },
    depthBucket: "mid",
    lazy: false,
  },
  page: {
    title: "Book a griha pravesh pandit",
    keywords: ["griha", "pandit", "booking"],
    categories: ["services"],
    language: "en",
  },
  user: {
    isReturning: true,
  },
  client: {},
  session: {
    id: "s1",
    engagementScore: 0.8,
    scrollDepth: 0.4,
  },
  priorSponsorCounts: {},
  priorLayoutCounts: {},
  priorFamilyCounts: {},
  priorTemplateCounts: {},
};

describe("RankingEngine", () => {
  it("normalizes value and favors the higher-value native candidate", () => {
    const engine = new RankingEngine();
    const candidates = engine.rank(
      [
        buildAd({
          id: "display-low",
          type: "display",
          sponsorName: "Other",
          budget: { model: "CPM", bidFloor: 180 },
        }),
        buildAd({
          id: "house-high",
          type: "house",
          sponsorName: "Navdhya",
          verified: true,
          tags: ["pandit", "booking"],
          budget: { model: "CPC", bidFloor: 700 },
          creative: { imageUrl: "/traditional-puja-ceremony-setup.jpg", alt: "Pandit booking" },
        }),
      ],
      context,
    );

    expect(candidates[0]?.ad.id).toBe("house-high");
    expect(candidates[0]?.revenueEcpm).toBeGreaterThan(candidates[1]?.revenueEcpm ?? 0);
  });

  it("applies sponsor fatigue penalties", () => {
    const engine = new RankingEngine();
    const repeatedSponsorContext: RankingContext = {
      ...context,
      priorSponsorCounts: {
        Navdhya: 2,
      },
    };

    const [candidate] = engine.rank(
      [
        buildAd({
          id: "fatigue",
          sponsorName: "Navdhya",
          budget: { model: "CPC", bidFloor: 450 },
          creative: { imageUrl: "/ritual-knowledge-blog.jpg", alt: "Ritual blog" },
        }),
      ],
      repeatedSponsorContext,
    );

    expect(candidate.uxPenalty).toBeGreaterThan(0);
  });
});
