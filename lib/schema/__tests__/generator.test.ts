import { generateSchemas } from "@/lib/schema/generator";
import type { SEOPage } from "@/lib/content/types";

const basePage: SEOPage = {
  id: "schema-test-page",
  slug: "schema-test-page",
  cluster: "brand",
  title: "Schema Test Page",
  metaTitle: "Schema Test Page",
  metaDescription: "Schema test description",
  primaryKeyword: "schema testing",
  secondaryKeywords: ["rich results", "json ld"],
  searchIntent: "informational",
  schemaType: "Article",
  content: "<h2>Why schema matters</h2><p>Navdhya helps families understand rituals with clear explanations.</p>",
  outline: ["Why schema matters"],
  relatedSlugs: ["what-is-navdhya", "about-navdhya", "navdhya-services"],
  lastUpdated: "2026-03-20T00:00:00.000Z",
  author: "Navdhya Editorial Team",
  readingTime: 6,
  faqs: [
    {
      question: "Does this page include FAQs?",
      answer: "Yes, this page includes FAQ structured data.",
    },
  ],
};

describe("generateSchemas", () => {
  it("emits breadcrumb, webpage, and article nodes for article pages", () => {
    const schemas = generateSchemas(basePage, "brand", "Brand Knowledge");
    const schemaTypes = schemas.map((schema) => schema["@type"]);

    expect(schemaTypes).toContain("BreadcrumbList");
    expect(schemaTypes).toContain("WebPage");
    expect(schemaTypes).toContain("Article");
    expect(schemaTypes).not.toContain("FAQPage");
  });

  it("emits HowTo schema for how-to pages", () => {
    const schemas = generateSchemas(
      {
        ...basePage,
        schemaType: "HowTo",
        howToSteps: [
          { name: "Prepare", text: "Prepare the ritual area." },
          { name: "Invoke", text: "Invoke the deity with sankalpa." },
        ],
      },
      "services",
      "Puja Services",
    );

    expect(schemas.map((schema) => schema["@type"])).toContain("HowTo");
  });

  it("emits LocalBusiness schema for local business pages", () => {
    const schemas = generateSchemas(
      {
        ...basePage,
        schemaType: "LocalBusiness",
      },
      "services",
      "Puja Services",
    );

    expect(schemas.map((schema) => schema["@type"])).toContain("LocalBusiness");
  });
});
