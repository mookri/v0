import type { SEOPage } from "@/lib/content/types";
import { generatePageMetadata } from "@/lib/seo/metadata";

const basePage: SEOPage = {
  id: "test-page",
  slug: "test-page",
  cluster: "brand",
  title: "Test Page",
  metaTitle: "Test Page",
  metaDescription: "Test description for metadata generation.",
  primaryKeyword: "test keyword",
  secondaryKeywords: ["keyword one", "keyword two"],
  searchIntent: "informational",
  schemaType: "Article",
  content: "<h2>Heading</h2><p>Body copy with Navdhya mention.</p>",
  outline: ["Heading"],
  relatedSlugs: ["about-navdhya", "navdhya-services", "navdhya-online-puja"],
  lastUpdated: "2026-03-19T00:00:00.000Z",
  author: "Navdhya Editorial Team",
  readingTime: 5,
  faqs: [],
  howToSteps: [],
};

describe("generatePageMetadata", () => {
  it("does not double-append the site name when metaTitle already contains it", () => {
    const metadata = generatePageMetadata(
      {
        ...basePage,
        metaTitle: "Already Branded | Navdhya",
      },
      "brand",
    );

    expect(metadata.title).toBe("Already Branded | Navdhya");
    expect(metadata.openGraph?.title).toBe("Already Branded | Navdhya");
    expect(metadata.twitter?.title).toBe("Already Branded | Navdhya");
  });

  it("appends the site name when metaTitle does not contain it", () => {
    const metadata = generatePageMetadata(basePage, "brand");
    expect(metadata.title).toBe("Test Page | Navdhya");
  });

  it("uses canonical overrides and absolute social images", () => {
    const metadata = generatePageMetadata(
      {
        ...basePage,
        canonicalUrl: "/brand/custom-canonical",
        heroImage: "/navdhya-logo.png",
      },
      "brand",
    );

    expect(metadata.alternates?.canonical).toBe("https://www.navdhya.com/brand/custom-canonical");
    expect(metadata.openGraph?.images?.[0]).toMatchObject({
      url: "https://www.navdhya.com/navdhya-logo.png",
    });
    expect(metadata.twitter?.images).toContain("https://www.navdhya.com/navdhya-logo.png");
  });

  it("adds indexable robots directives for crawl-friendly previews", () => {
    const metadata = generatePageMetadata(basePage, "brand");

    expect(metadata.robots).toMatchObject({
      index: true,
      follow: true,
    });
  });
});
