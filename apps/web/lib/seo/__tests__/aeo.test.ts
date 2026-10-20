// lib/seo/__tests__/aeo.test.ts
// Unit tests for the AEO schema generator.

import type { SEOPage } from "@/lib/content/types";
import {
  buildSpeakable,
  buildKeyFacts,
  buildTLDR,
  buildClaimReview,
  buildAuthorEntity,
  generateAEO,
  deriveSpeakableFaqs,
} from "@/lib/seo/aeo";

const basePage: SEOPage = {
  id: "test-1",
  slug: "test-page",
  cluster: "ritual-guides",
  title: "What is Sankalpa",
  metaTitle: "What is Sankalpa",
  metaDescription: "A short, citable definition of sankalpa in Vedic puja.",
  primaryKeyword: "sankalpa",
  secondaryKeywords: [],
  searchIntent: "informational",
  schemaType: "Article",
  content:
    "<h2>Why it matters</h2><p>Sankalpa is the spoken intention at the start of every puja. It frames the ceremony.</p>",
  outline: ["Why it matters"],
  relatedSlugs: [],
  lastUpdated: "2026-05-27",
  author: "Navdhya Editorial Team",
  readingTime: 4,
  faqs: [
    { question: "What is sankalpa?", answer: "The spoken intention that begins a Vedic ceremony." },
    { question: "Why is it spoken aloud?", answer: "Speaking the sankalpa makes the intention explicit." },
  ],
};

const URL = "https://www.navdhya.com/ritual-guides/test-page";

describe("buildSpeakable", () => {
  it("always returns a SpeakableSpecification with article-body selectors", () => {
    const result = buildSpeakable(basePage);
    expect(result).toBeDefined();
    expect(result?.["@type"]).toBe("SpeakableSpecification");
    const cssSelectors = result?.cssSelector as string[];
    expect(cssSelectors).toContain("#article-content h2");
    expect(cssSelectors).toContain("#article-content h3");
    expect(cssSelectors).toContain("#article-content p");
  });

  it("appends FAQ selectors when faqs are present", () => {
    const result = buildSpeakable(basePage);
    const cssSelectors = result?.cssSelector as string[];
    expect(cssSelectors).toContain("#faq-item-1");
    expect(cssSelectors).toContain("#faq-item-2");
  });

  it("falls back to body selectors when no faqs are present", () => {
    const result = buildSpeakable({ ...basePage, faqs: [], speakableFaqs: [] });
    const cssSelectors = result?.cssSelector as string[];
    expect(cssSelectors).toContain("#article-content h2");
    expect(cssSelectors).not.toContain("#faq-item-1");
  });

  it("caps selectors at 6 entries", () => {
    const manyFaqs = Array.from({ length: 10 }, (_, i) => ({
      question: `Q${i + 1}?`,
      answer: `A${i + 1}.`,
    }));
    const result = buildSpeakable({ ...basePage, faqs: manyFaqs });
    const cssSelectors = result?.cssSelector as string[];
    expect(cssSelectors.length).toBeLessThanOrEqual(6);
  });
});

describe("buildKeyFacts", () => {
  it("prefers editorially curated proofPoints when present", () => {
    const result = buildKeyFacts(
      {
        ...basePage,
        proofPoints: ["Fact one.", "Fact two.", "Fact three."],
      },
      URL,
    );
    expect(result).toBeDefined();
    expect(result?.["@type"]).toBe("ItemList");
    const items = result?.itemListElement as Array<{ name: string }>;
    expect(items[0].name).toBe("Fact one");
    expect(items.length).toBeLessThanOrEqual(5);
  });

  it("falls back to serviceDetails.highlights when proofPoints absent", () => {
    const result = buildKeyFacts(
      {
        ...basePage,
        serviceDetails: {
          highlights: ["Highlight A.", "Highlight B."],
        },
      },
      URL,
    );
    const items = result?.itemListElement as Array<{ name: string }>;
    expect(items[0].name).toBe("Highlight A");
  });

  it("falls back to auto-extracted sentences when no curated data", () => {
    const result = buildKeyFacts(
      {
        ...basePage,
        content:
          "<h2>Heading</h2><p>First sentence that is long enough. Second sentence that is also long enough. Third sentence here too.</p>",
      },
      URL,
    );
    const items = result?.itemListElement as Array<{ name: string }>;
    expect(items.length).toBeGreaterThan(0);
    expect(items[0].name.length).toBeGreaterThan(20);
  });

  it("returns undefined when nothing usable is available", () => {
    const result = buildKeyFacts(
      {
        ...basePage,
        content: "",
        faqs: [],
        proofPoints: undefined,
        serviceDetails: undefined,
      },
      URL,
    );
    expect(result).toBeUndefined();
  });
});

describe("buildTLDR", () => {
  it("uses editorial excerpt when present", () => {
    const result = buildTLDR(
      { ...basePage, excerpt: "Short and direct." },
      URL,
    );
    expect(result?.text).toBe("Short and direct.");
    expect(result?.["@type"]).toBe("WebPageElement");
  });

  it("falls back to metaDescription when excerpt absent", () => {
    const result = buildTLDR(basePage, URL);
    expect(result?.text).toBe(basePage.metaDescription);
  });

  it("returns undefined when neither excerpt nor description", () => {
    const result = buildTLDR(
      { ...basePage, excerpt: undefined, metaDescription: "" },
      URL,
    );
    expect(result).toBeUndefined();
  });
});

describe("buildClaimReview", () => {
  it("emits a ClaimReview when content is reviewed and approved", () => {
    const result = buildClaimReview(
      {
        ...basePage,
        reviewedBy: "Vinod Sharma",
        lastReviewed: "2026-05-27",
        claimReviewStatus: "approved",
      },
      URL,
    );
    expect(result?.["@type"]).toBe("ClaimReview");
    expect(result?.reviewRating).toMatchObject({ ratingValue: 5 });
  });

  it("uses rating 4 for reviewed-but-not-approved content", () => {
    const result = buildClaimReview(
      {
        ...basePage,
        reviewedBy: "Vinod Sharma",
        lastReviewed: "2026-05-27",
        claimReviewStatus: "reviewed",
      },
      URL,
    );
    expect(result?.reviewRating).toMatchObject({ ratingValue: 4 });
  });

  it("skips draft and restricted content", () => {
    for (const status of ["draft", "restricted"] as const) {
      const result = buildClaimReview(
        { ...basePage, reviewedBy: "X", lastReviewed: "2026-01-01", claimReviewStatus: status },
        URL,
      );
      expect(result).toBeUndefined();
    }
  });

  it("skips when no reviewer is recorded", () => {
    const result = buildClaimReview({ ...basePage, reviewedBy: undefined }, URL);
    expect(result).toBeUndefined();
  });
});

describe("buildAuthorEntity", () => {
  it("returns a Person when reviewedBy is set", () => {
    const result = buildAuthorEntity({ ...basePage, reviewedBy: "Vinod Sharma" });
    expect(result["@type"]).toBe("Person");
    expect(result.name).toBe("Vinod Sharma");
  });

  it("returns an Organization when only the editorial author is set", () => {
    const result = buildAuthorEntity({ ...basePage, reviewedBy: undefined });
    expect(result["@type"]).toBe("Organization");
    expect(result.name).toBe("Navdhya Editorial Team");
  });
});

describe("generateAEO (composite)", () => {
  it("returns a coherent AEO bundle for a typical article", () => {
    const aeo = generateAEO({ page: basePage, url: URL });
    expect(aeo.speakable).toBeDefined();
    expect(aeo.keyFacts).toBeDefined();
    expect(aeo.tldr).toBeDefined();
    expect(aeo.authorEntity).toBeDefined();
  });

  it("is idempotent — running twice yields structurally identical output", () => {
    const a = generateAEO({ page: basePage, url: URL });
    const b = generateAEO({ page: basePage, url: URL });
    expect(JSON.stringify(a)).toBe(JSON.stringify(b));
  });

  it("handles a minimal page without throwing", () => {
    const minimal: SEOPage = {
      ...basePage,
      faqs: [],
      content: "",
      proofPoints: undefined,
      serviceDetails: undefined,
      excerpt: undefined,
      metaDescription: "",
      reviewedBy: undefined,
    };
    expect(() => generateAEO({ page: minimal, url: URL })).not.toThrow();
    const aeo = generateAEO({ page: minimal, url: URL });
    expect(aeo.speakable).toBeDefined();
    expect(aeo.keyFacts).toBeUndefined();
    expect(aeo.tldr).toBeUndefined();
    expect(aeo.claimReview).toBeUndefined();
  });
});

describe("deriveSpeakableFaqs", () => {
  it("truncates long answers to 160 characters", () => {
    const long = "x".repeat(200);
    const result = deriveSpeakableFaqs([{ question: "Q?", answer: long }]);
    expect(result[0].summary?.length).toBeLessThanOrEqual(160);
    expect(result[0].summary?.endsWith("…")).toBe(true);
  });

  it("keeps short answers as-is", () => {
    const result = deriveSpeakableFaqs([{ question: "Q?", answer: "Short." }]);
    expect(result[0].summary).toBe("Short.");
    expect(result[0].summary?.endsWith("…")).toBe(false);
  });

  it("caps at 5 entries", () => {
    const faqs = Array.from({ length: 10 }, (_, i) => ({
      question: `Q${i}?`,
      answer: `A${i}.`,
    }));
    expect(deriveSpeakableFaqs(faqs).length).toBe(5);
  });
});
