/**
 * @jest-environment node
 */
import { Bm25Index, tokenize, type IndexableDoc } from "@/lib/search/bm25";

function doc(overrides: Partial<IndexableDoc> & { title: string }): IndexableDoc {
  return {
    id: `seo_page:${overrides.title}`,
    type: "seo_page",
    url: `/guides/${overrides.title.toLowerCase().replace(/\s+/g, "-")}`,
    ...overrides,
  };
}

const CORPUS: IndexableDoc[] = [
  doc({
    title: "Hindu Wedding Ceremony Guide",
    text: "A complete guide to hindu wedding rituals, muhurta timing and pandit duties.",
    url: "/ceremonies/hindu-wedding-ceremony-guide",
  }),
  doc({
    title: "Ganesh Chaturthi Puja at Home",
    text: "How families plan a Ganesh chaturthi puja at home with a pandit.",
    url: "/pujas/ganesh-chaturthi-puja-at-home",
  }),
  doc({
    title: "Diwali Lakshmi Puja Muhurta",
    text: "Diwali dates, muhurta timings and the Lakshmi puja checklist.",
    url: "/festival-rituals/diwali-lakshmi-puja-muhurta",
  }),
  // Body-only mention of "hindu wedding" — must rank below the title match.
  doc({
    title: "Pandit Booking Checklist",
    text: "What to ask before you book a hindu wedding priest for your ceremony.",
    url: "/pandit-services/pandit-booking-checklist",
  }),
];

describe("tokenize", () => {
  it("lowercases, splits on non-alphanumerics and drops stopwords/short tokens", () => {
    expect(tokenize("The Hindu Wedding — Guide!")).toEqual(["hindu", "wedding", "guide"]);
  });

  it("returns an empty array for blank and stopword-only input", () => {
    expect(tokenize("")).toEqual([]);
    expect(tokenize("the and of a")).toEqual([]);
  });
});

describe("Bm25Index", () => {
  const index = new Bm25Index(CORPUS);

  it("ranks a title match above an incidental body mention", () => {
    const results = index.search("hindu wedding");
    expect(results.length).toBeGreaterThanOrEqual(2);
    expect(results[0].doc.url).toBe("/ceremonies/hindu-wedding-ceremony-guide");
    expect(results[0].score).toBeGreaterThan(results[1].score);
  });

  it("matches single-token queries", () => {
    const results = index.search("ganesh");
    expect(results[0]?.doc.title).toBe("Ganesh Chaturthi Puja at Home");
  });

  it("returns nothing for blank and stopword-only queries", () => {
    expect(index.search("")).toEqual([]);
    expect(index.search("the and of")).toEqual([]);
    expect(index.search("zzznotpresent")).toEqual([]);
  });

  it("respects the limit", () => {
    expect(index.search("puja", 1)).toHaveLength(1);
  });

  it("suggests by vocabulary prefix and deduplicates docs", () => {
    const suggestions = index.suggest("hind");
    // "hindu" matches in two docs, "hindering"-style prefixes would too —
    // each doc appears at most once.
    const urls = suggestions.map((s) => s.url);
    expect(new Set(urls).size).toBe(urls.length);
    expect(urls).toContain("/ceremonies/hindu-wedding-ceremony-guide");
    expect(urls).toContain("/pandit-services/pandit-booking-checklist");
  });

  it("suggests nothing for sub-word or empty input", () => {
    expect(index.suggest("x")).toEqual([]);
    expect(index.suggest("")).toEqual([]);
  });
});
