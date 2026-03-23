import { buildArticleAdExperience } from "@/components/seo/article-ad-experience";
import type { ClusterMeta, SEOPage } from "@/lib/content/types";

const page = {
  id: "seo-page-1",
  slug: "book-pandit-online",
  cluster: "services",
  title: "Book a Pandit Online",
  metaTitle: "Book a Pandit Online | Navdhya",
  metaDescription: "Compare rituals, pricing, and priest availability in one place.",
  primaryKeyword: "book pandit online",
  secondaryKeywords: ["online puja booking", "verified pandit service"],
  searchIntent: "transactional",
  schemaType: "Article",
  content: `
    <h2>Understand the ritual</h2>
    <p>This introduction explains why choosing the right pandit matters for family rituals across cities and traditions.</p>
    <p>This second paragraph continues the setup and gives enough text for the planner to parse article quality and structure.</p>
    <p>This long paragraph gives detailed context about ceremony planning, materials, timing, tradition matching, and family preferences so the ad placement planner has enough readable content to score a mid-article insertion safely.</p>
    <p>This additional paragraph covers priest verification, logistics coordination, and preparation steps in enough detail to support another insertion candidate while still reading naturally for users.</p>
    <p>This deeper paragraph explores pricing, packages, and delivery timelines with enough copy to help the engine evaluate a later article opportunity.</p>
    <p>This closing paragraph summarizes the process and should stay free from aggressive insertion density.</p>
  `,
  outline: ["Understand the ritual"],
  relatedSlugs: [],
  lastUpdated: "2026-03-19T00:00:00.000Z",
  author: "Navdhya Editorial",
  readingTime: 6,
} as SEOPage;

const clusterMeta: ClusterMeta = {
  label: "Services",
  basePath: "/services",
};

describe("buildArticleAdExperience", () => {
  it("builds a server-side article plan and precomputes initial decisions", async () => {
    const experience = await buildArticleAdExperience(page, clusterMeta);
    const articleOpportunities = experience.contentPlan.opportunities.filter((opportunity) => opportunity.surface === "article");
    const articlePrefetches = experience.prefetchedSlotIds.filter((slotId) => slotId.startsWith("article_inline_"));

    expect(experience.contentPlan.blocks.length).toBeGreaterThan(0);
    expect(experience.contentPlan.opportunities.length).toBeGreaterThan(0);
    expect(articleOpportunities.length).toBeGreaterThanOrEqual(2);
    expect(new Set(articleOpportunities.map((opportunity) => opportunity.designIntent)).size).toBeGreaterThanOrEqual(2);
    expect(experience.initialDecisions.length).toBeGreaterThan(0);
    expect(articlePrefetches.length).toBeGreaterThanOrEqual(2);
    expect(experience.prefetchedSlotIds).toEqual(experience.initialDecisions.map((decision) => decision.slotId));
    expect(experience.baseRequest.page.url).toBe("/services/book-pandit-online");
  });
});
