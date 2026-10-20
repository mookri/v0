import type { Ad } from "@/components/ad-network/core/types";
import { layoutRegistry } from "@/lib/ad-orchestration/LayoutRegistry";
import { LayoutResolver } from "@/lib/ad-orchestration/LayoutResolver";

const baseAd: Ad = {
  id: "layout-test-ad",
  type: "native",
  title: "Book a Verified Pandit for Your Next Ceremony",
  description: "Trusted priests, transparent logistics, and ceremony support from Navdhya.",
  ctaText: "Book Now",
  ctaUrl: "/book",
  sponsorName: "Navdhya",
  layouts: ["medium_rectangle", "wide_skyscraper", "native_in_content", "low_bandwidth"],
  placements: ["article_inline_1", "sidebar_top"],
  creative: {
    imageUrl: "/traditional-puja-ceremony-setup.jpg",
    thumbnails: [
      "/traditional-puja-ceremony-setup.jpg",
      "/traditional-puja-ceremony-mandap.jpg",
    ],
  },
};

describe("Layout catalog and resolver", () => {
  it("covers more than 100 layouts with deterministic specs", () => {
    const layouts = layoutRegistry.getAllLayouts();
    expect(layouts.length).toBeGreaterThan(100);

    for (const layout of layouts) {
      const spec = layoutRegistry.getSpec(layout);
      expect(spec.family).toBeTruthy();
      expect(spec.template).toBeTruthy();
      expect(spec.reservedSize.height).toBeGreaterThan(0);
      expect(spec.fallbackOrder.length).toBeGreaterThan(0);
    }
  });

  it("maps every placement to at least one default layout", () => {
    for (const placement of layoutRegistry.getAllPlacements()) {
      expect(layoutRegistry.getPlacementDefaults(placement).length).toBeGreaterThan(0);
    }
  });

  it("uses different default layout stacks for the first three article inline slots", () => {
    const inlineOne = layoutRegistry.getPlacementDefaults("article_inline_1");
    const inlineTwo = layoutRegistry.getPlacementDefaults("article_inline_2");
    const inlineThree = layoutRegistry.getPlacementDefaults("article_inline_3");

    expect(inlineOne[0]).toBe("native_in_content");
    expect(inlineTwo[0]).toBe("leaderboard");
    expect(inlineThree[0]).toBe("story_card");
    expect(inlineOne).not.toEqual(inlineTwo);
    expect(inlineTwo).not.toEqual(inlineThree);
  });

  it("prefers low-bandwidth-safe layouts on constrained connections", () => {
    const resolver = new LayoutResolver();
    const selection = resolver.resolve(
      {
        slotId: "article-inline-1",
        surface: "article",
        placementHints: ["article_inline_1"],
        anchorId: "paragraph-5",
        anchorIndex: 5,
        reservedSize: { width: "fluid", height: 180, aspectRatio: "16:6" },
        depthBucket: "mid",
        lazy: false,
      },
      {
        ...baseAd,
        layouts: ["hero_cinematic", "native_in_content", "low_bandwidth"],
      },
      {
        saveData: true,
        connection: "2g",
        viewportWidth: 390,
      },
    );

    expect(selection.layout).toBe("low_bandwidth");
  });

  it("prefers vertical sidebar formats for desktop sidebar opportunities", () => {
    const resolver = new LayoutResolver();
    const selection = resolver.resolve(
      {
        slotId: "sidebar-top",
        surface: "sidebar",
        placementHints: ["sidebar_top"],
        anchorId: "sidebar",
        anchorIndex: 0,
        reservedSize: { width: 300, height: 600, aspectRatio: "1:2" },
        depthBucket: "above_fold",
        lazy: false,
      },
      {
        ...baseAd,
        layouts: ["medium_rectangle", "wide_skyscraper", "portrait"],
      },
      {
        viewportWidth: 1440,
        viewportHeight: 900,
        connection: "wifi",
      },
    );

    expect(["wide_skyscraper", "portrait"]).toContain(selection.layout);
  });

  it("rotates article layouts across editorial, compact, and immersive intents", () => {
    const resolver = new LayoutResolver();
    const ad = {
      ...baseAd,
      layouts: ["native_in_content", "leaderboard", "story_card", "inline_text"],
      placements: ["article_inline_1", "article_inline_2", "article_inline_3"],
    };
    const client = {
      viewportWidth: 1280,
      viewportHeight: 900,
      connection: "wifi" as const,
    };

    const editorial = resolver.resolve(
      {
        slotId: "article-inline-1",
        surface: "article",
        placementHints: ["article_inline_1"],
        anchorId: "paragraph-4",
        anchorIndex: 4,
        reservedSize: { width: "fluid", height: 196, aspectRatio: "16:7" },
        depthBucket: "mid",
        lazy: false,
        designIntent: "editorial",
        preferredFamilies: ["native", "sponsored"],
        preferredTemplates: ["split", "poster"],
      },
      ad,
      client,
    );

    const compact = resolver.resolve(
      {
        slotId: "article-inline-2",
        surface: "article",
        placementHints: ["article_inline_2"],
        anchorId: "paragraph-8",
        anchorIndex: 8,
        reservedSize: { width: "fluid", height: 132, aspectRatio: "12:3" },
        depthBucket: "mid",
        lazy: false,
        designIntent: "compact",
        preferredFamilies: ["display", "sponsored", "fallback", "native"],
        preferredTemplates: ["ribbon", "ticker", "split"],
      },
      ad,
      client,
      undefined,
      {
        priorLayoutCounts: { [editorial.layout]: 1 },
        priorFamilyCounts: { native: 1 },
        priorTemplateCounts: { split: 1 },
      },
    );

    const immersive = resolver.resolve(
      {
        slotId: "article-inline-3",
        surface: "article",
        placementHints: ["article_inline_3"],
        anchorId: "paragraph-12",
        anchorIndex: 12,
        reservedSize: { width: "fluid", height: 284, aspectRatio: "16:9" },
        depthBucket: "deep",
        lazy: true,
        designIntent: "immersive",
        preferredFamilies: ["story", "carousel", "interactive", "native", "commerce"],
        preferredTemplates: ["story", "carousel", "interactive", "poster"],
      },
      ad,
      client,
      undefined,
      {
        priorLayoutCounts: { [editorial.layout]: 1, [compact.layout]: 1 },
        priorFamilyCounts: { native: 1, display: 1 },
        priorTemplateCounts: { split: 1, ribbon: compact.layout === "leaderboard" ? 1 : 0, ticker: compact.layout === "inline_text" ? 1 : 0 },
      },
    );

    expect(editorial.layout).toBe("native_in_content");
    expect(["leaderboard", "inline_text"]).toContain(compact.layout);
    expect(immersive.layout).toBe("story_card");
  });
});
