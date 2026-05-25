import { getPagesByCluster } from "@/lib/content/loader";
import { buildSitemapEntries } from "@/lib/seo/sitemap";

describe("registry-first sitemap coverage", () => {
  it("includes puja detail routes from the registry", () => {
    const pujaPages = getPagesByCluster("pujas");
    const sitemapUrls = new Set(buildSitemapEntries().map((entry) => entry.url));

    expect(pujaPages.length).toBeGreaterThanOrEqual(12);

    for (const page of pujaPages) {
      expect(sitemapUrls.has(`https://www.navdhya.com/pujas/${page.slug}`)).toBe(true);
    }
  });
});
