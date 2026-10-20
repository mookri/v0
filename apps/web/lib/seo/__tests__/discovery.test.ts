import { buildRobotsPolicy } from "@/lib/seo/robots";
import { buildSitemapChunks, buildSitemapEntries, buildSitemapIndexXml } from "@/lib/seo/sitemap";
import { SITEMAP_CHUNK_SIZE } from "@/lib/seo/sitemap.constants";

let entries = buildSitemapEntries();

describe("SEO discovery", () => {
  it("includes core, SEO content, and puja detail routes in the sitemap", () => {
    const urls = entries.map((entry) => entry.url);

    expect(urls).toContain("https://www.navdhya.com/");
    expect(urls).toContain("https://www.navdhya.com/explore/how-navdhya-works");
    expect(urls).toContain("https://www.navdhya.com/ceremonies/hindu-wedding-ceremony-guide");
    expect(urls).toContain("https://www.navdhya.com/pujas/griha-pravesh");
  });

  it("does not duplicate sitemap URLs", () => {
    const urls = entries.map((entry) => entry.url);
    expect(new Set(urls).size).toBe(urls.length);
    expect(urls).not.toContain("https://www.navdhya.com/auth/login");
    expect(urls).not.toContain("https://www.navdhya.com/dashboard");
  });

  it("splits sitemap output into deterministic chunks of at most 40 URLs", () => {
    const chunks = buildSitemapChunks(entries);

    expect(chunks.length).toBeGreaterThan(0);
    expect(chunks.every((chunk) => chunk.length <= SITEMAP_CHUNK_SIZE)).toBe(true);
    expect(chunks.flat().map((entry) => entry.url)).toEqual(entries.map((entry) => entry.url));
  });

  it("builds a sitemap index that points to child sitemap files", () => {
    const xml = buildSitemapIndexXml(entries);

    expect(xml).toContain("<sitemapindex");
    expect(xml).toContain("https://www.navdhya.com/sitemaps/0.xml");
  });

  it("serves an advanced robots policy with sitemap and protected paths", () => {
    const robots = buildRobotsPolicy();
    const rules = Array.isArray(robots.rules) ? robots.rules : [robots.rules];

    expect(robots.sitemap).toContain("https://www.navdhya.com/sitemap.xml");
    expect(rules[0].disallow).toEqual(
      expect.arrayContaining(["/api/", "/auth/", "/dashboard/", "/drafts/", "/admin/"]),
    );
  });
});
