import { buildRobotsPolicy } from "@/lib/seo/robots";
import { buildSitemapEntries } from "@/lib/seo/sitemap";

describe("SEO discovery", () => {
  it("includes core, SEO content, and puja detail routes in the sitemap", () => {
    const entries = buildSitemapEntries();
    const urls = entries.map((entry) => entry.url);

    expect(urls).toContain("https://www.navdhya.com/");
    expect(urls).toContain("https://www.navdhya.com/brand/what-is-navdhya");
    expect(urls).toContain("https://www.navdhya.com/ceremonies/hindu-wedding-ceremony-guide");
    expect(urls).toContain("https://www.navdhya.com/pujas/griha-pravesh");
  });

  it("does not duplicate sitemap URLs", () => {
    const urls = buildSitemapEntries().map((entry) => entry.url);
    expect(new Set(urls).size).toBe(urls.length);
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
