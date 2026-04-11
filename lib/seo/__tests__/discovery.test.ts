import { buildRobotsPolicy } from "@/lib/seo/robots";
import { buildSitemapChunks, buildSitemapEntries, buildSitemapIndexXml, SITEMAP_CHUNK_SIZE } from "@/lib/seo/sitemap";

describe("SEO discovery", () => {
  it("includes core, SEO content, and puja detail routes in the sitemap", () => {
    const entries = buildSitemapEntries();
    const urls = entries.map((entry) => entry.url);

    expect(urls).toContain("https://www.navdhya.com/");
    expect(urls).toContain("https://www.navdhya.com/explore/how-navdhya-works");
    expect(urls).toContain("https://www.navdhya.com/ceremonies/hindu-wedding-ceremony-guide");
    expect(urls).toContain("https://www.navdhya.com/pujas/griha-pravesh");
  });

  it("does not duplicate sitemap URLs", () => {
    const urls = buildSitemapEntries().map((entry) => entry.url);
    expect(new Set(urls).size).toBe(urls.length);
  });

  it("splits sitemap output into deterministic chunks of at most 40 URLs", () => {
    const entries = buildSitemapEntries();
    const chunks = buildSitemapChunks(entries);

    expect(chunks.length).toBeGreaterThan(0);
    expect(chunks.every((chunk) => chunk.length <= SITEMAP_CHUNK_SIZE)).toBe(true);
    expect(chunks.flat().map((entry) => entry.url)).toEqual(entries.map((entry) => entry.url));
  });

  it("builds a sitemap index that points to child sitemap files", () => {
    const xml = buildSitemapIndexXml(buildSitemapEntries());

    expect(xml).toContain("<sitemapindex");
    expect(xml).toContain("https://www.navdhya.com/sitemaps/0.xml");
  });

  it("serves an advanced robots policy with sitemap and protected paths", () => {
    const robots = buildRobotsPolicy();
    const rules = Array.isArray(robots.rules) ? robots.rules : [robots.rules];

    expect(robots.sitemap).toContain("https://www.navdhya.com/sitemap.xml");
    expect(rules[0].disallow).toEqual(
      expect.arrayContaining(["/api/", "/auth/", "/drafts/", "/admin/"]),
    );
  });
});
