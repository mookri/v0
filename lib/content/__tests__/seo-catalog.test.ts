import { getAllSlugs, getClusterMeta, getRegistryEntry } from "@/lib/content/registry";
import { getPage } from "@/lib/content/loader";
import { generateSchemas } from "@/lib/schema/generator";
import { validateEntitySEO } from "@/lib/seo/entity";

const BASE_SLUGS = new Set(["what-is-navdhya", "about-navdhya", "navdhya-services"]);

function stripHtml(value: string): string {
  return value.replace(/<[^>]+>/g, " ").replace(/\s+/g, " ").trim();
}

function countRenderableWords(page: NonNullable<ReturnType<typeof getPage>>): number {
  const faqText = (page.faqs ?? [])
    .map((faq) => `${faq.question} ${faq.answer}`)
    .join(" ");
  const howToText = (page.howToSteps ?? [])
    .map((step) => `${step.name} ${step.text}`)
    .join(" ");
  const merged = `${stripHtml(page.content)} ${faqText} ${howToText}`.trim();
  return merged.split(/\s+/).filter(Boolean).length;
}

function countH2s(content: string): number {
  return (content.match(/<h2>/g) ?? []).length;
}

describe("SEO catalog rollout", () => {
  const rolloutSlugs = getAllSlugs().filter((slug) => !BASE_SLUGS.has(slug));

  it("registers the full requested rollout", () => {
    expect(rolloutSlugs.length).toBeGreaterThanOrEqual(286);
  });

  it("loads every rollout page with required fields", () => {
    for (const slug of rolloutSlugs) {
      const entry = getRegistryEntry(slug);
      const page = getPage(slug);

      expect(entry).toBeTruthy();
      expect(page).toBeTruthy();
      expect(page?.slug).toBe(slug);
      expect(page?.title).toBeTruthy();
      expect(page?.metaTitle).toBeTruthy();
      expect(page?.metaDescription).toBeTruthy();
      expect(page?.primaryKeyword).toBeTruthy();
      expect(page?.outline?.length).toBeGreaterThan(0);
      expect(page?.relatedSlugs?.length).toBeGreaterThanOrEqual(3);
      expect(page?.author).toBeTruthy();
      expect(page?.readingTime).toBeGreaterThan(0);
      expect(page?.adSlot1).toBeUndefined();
      expect(page?.adSlot2).toBeUndefined();
      expect(page?.adSlots).toBeUndefined();
    }
  });

  it("keeps quality and structure within rollout contracts", () => {
    for (const slug of rolloutSlugs) {
      const page = getPage(slug);
      expect(page).toBeTruthy();
      if (!page) continue;

      const words = countRenderableWords(page);
      const h2s = countH2s(page.content);

      if (page.schemaType === "FAQPage") {
        expect(words).toBeGreaterThanOrEqual(450);
        expect(h2s).toBeGreaterThanOrEqual(2);
        expect((page.faqs ?? []).length).toBeGreaterThanOrEqual(8);
        expect((page.faqs ?? []).length).toBeLessThanOrEqual(12);
      } else {
        expect(words).toBeGreaterThanOrEqual(700);
        expect(words).toBeLessThanOrEqual(1400);
        expect(h2s).toBeGreaterThanOrEqual(5);
        expect((page.faqs ?? []).length).toBeGreaterThanOrEqual(3);
        expect(validateEntitySEO(page.content).pass).toBe(true);
      }

      if (page.schemaType === "HowTo") {
        expect((page.howToSteps ?? []).length).toBeGreaterThanOrEqual(5);
      }

      expect(page.content.toLowerCase()).toContain(page.primaryKeyword.toLowerCase().split(" ")[0]);
    }
  });

  it("generates non-empty schemas for every rollout page", () => {
    for (const slug of rolloutSlugs) {
      const entry = getRegistryEntry(slug);
      const page = getPage(slug);
      expect(entry).toBeTruthy();
      expect(page).toBeTruthy();
      if (!entry || !page) continue;

      const clusterMeta = getClusterMeta(entry.cluster);
      expect(clusterMeta).toBeTruthy();
      if (!clusterMeta) continue;

      const schemas = generateSchemas(page, clusterMeta.basePath, clusterMeta.label);
      expect(schemas.length).toBeGreaterThan(0);

      const schemaTypes = schemas.map((schema) => schema["@type"]);
      expect(schemaTypes).toContain("WebPage");
      expect(schemaTypes).toContain("BreadcrumbList");
      if (page.schemaType === "FAQPage") {
        expect(schemaTypes).toContain("FAQPage");
      }
      if (page.schemaType === "HowTo") {
        expect(schemaTypes).toContain("HowTo");
      }
      if (page.schemaType === "Service") {
        expect(schemaTypes).toContain("Service");
      }
      if (page.schemaType === "Organization") {
        expect(schemaTypes).toContain("Organization");
      }
      if (page.schemaType === "LocalBusiness") {
        expect(schemaTypes).toContain("LocalBusiness");
      }
      if ((page.faqs ?? []).length > 0) {
        expect(schemaTypes).toContain("FAQPage");
      }
    }
  });

  it("keeps related links resolvable and self-link free", () => {
    for (const slug of rolloutSlugs) {
      const page = getPage(slug);
      expect(page).toBeTruthy();
      if (!page) continue;

      for (const relatedSlug of page.relatedSlugs) {
        expect(relatedSlug).not.toBe(slug);
        expect(getPage(relatedSlug)).toBeTruthy();
      }
    }
  });
});
