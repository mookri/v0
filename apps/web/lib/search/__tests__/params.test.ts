import {
  MAX_QUERY_LENGTH,
  MAX_SUGGEST_QUERY_LENGTH,
  SEARCH_TYPES,
  buildSearchHref,
  clampSearchQuery,
  firstParam,
  flattenSuggestions,
  isSearchType,
  typeLabel,
  type SuggestionGroup,
} from "@/lib/search/params";

describe("search params helpers", () => {
  it("clamps queries to the max length and trims", () => {
    expect(clampSearchQuery("  puja  ")).toBe("puja");
    expect(clampSearchQuery("x".repeat(500)).length).toBe(MAX_QUERY_LENGTH);
    expect(MAX_SUGGEST_QUERY_LENGTH).toBeLessThan(MAX_QUERY_LENGTH);
  });

  it("reads the first value of a repeated parameter", () => {
    expect(firstParam(["second", "first"])).toBe("second");
    expect(firstParam("  diwali ")).toBe("diwali");
    expect(firstParam(undefined)).toBe("");
    expect(firstParam([])).toBe("");
  });

  it("validates types against the allowlist only", () => {
    expect(isSearchType("temple")).toBe(true);
    expect(isSearchType("puja_service")).toBe(true);
    expect(isSearchType("DROP TABLE")).toBe(false);
    expect(isSearchType(null)).toBe(false);
    expect(isSearchType(undefined)).toBe(false);
    for (const type of SEARCH_TYPES) expect(isSearchType(type)).toBe(true);
  });

  it("builds /search hrefs, omitting empty parts", () => {
    expect(buildSearchHref({ q: "diwali" })).toBe("/search?q=diwali");
    expect(buildSearchHref({ q: " diwali ", type: "temple", city: "Delhi" })).toBe(
      "/search?q=diwali&type=temple&city=Delhi",
    );
    expect(buildSearchHref({})).toBe("/search");
    expect(buildSearchHref({ q: "" })).toBe("/search");
    expect(buildSearchHref({ q: "a&b=c" })).toBe("/search?q=a%26b%3Dc");
  });

  it("labels entity types for chips and badges", () => {
    expect(typeLabel("temple")).toBe("Temples");
    expect(typeLabel("seo_page")).toBe("Guides & Blogs");
    expect(typeLabel("mystery_type")).toBe("Mystery type");
  });

  it("flattens suggestion groups in display order", () => {
    const groups: SuggestionGroup[] = [
      { type: "temple", label: "Temples", items: [{ title: "A", url: "/temples/a" }] },
      {
        type: "pandit",
        label: "Pandits",
        items: [
          { title: "B", url: "/pandits/b" },
          { title: "C", url: "/pandits/c" },
        ],
      },
    ];
    expect(flattenSuggestions(groups).map((item) => item.title)).toEqual(["A", "B", "C"]);
    expect(flattenSuggestions([])).toEqual([]);
  });
});
