// /lib/schema/generator.ts
// JSON-LD structured data factory for the Navdhya SEO platform.

import type { SEOPage } from "@/lib/content/types";
import { buildCanonicalUrl } from "@/lib/seo/metadata";
import {
  DEFAULT_LANGUAGE,
  DEFAULT_OG_IMAGE,
  DEFAULT_OG_IMAGE_HEIGHT,
  DEFAULT_OG_IMAGE_WIDTH,
  SITE_NAME,
  SITE_URL,
  toAbsoluteUrl,
} from "@/lib/seo/site";

type SchemaNode = Record<string, unknown>;

const ORGANIZATION_ID = `${SITE_URL}#organization`;
const WEBSITE_ID = `${SITE_URL}#website`;

const ORGANIZATION: SchemaNode = {
  "@type": "Organization",
  "@id": ORGANIZATION_ID,
  name: SITE_NAME,
  url: SITE_URL,
  logo: {
    "@type": "ImageObject",
    url: toAbsoluteUrl(DEFAULT_OG_IMAGE),
    width: DEFAULT_OG_IMAGE_WIDTH,
    height: DEFAULT_OG_IMAGE_HEIGHT,
  },
  foundingDate: "2023",
  areaServed: [
    { "@type": "Place", name: "Delhi-NCR" },
    { "@type": "Country", name: "India" },
  ],
  sameAs: [
    "https://www.instagram.com/navdhya",
    "https://www.facebook.com/navdhya",
  ],
  knowsAbout: [
    "Vedic rituals",
    "Pandit booking",
    "Online puja services",
    "Home puja services",
    "Vedic astrology",
    "Hindu ceremonies",
  ],
};

function pageUrl(basePath: string, page: SEOPage): string {
  return buildCanonicalUrl(basePath, page.slug, page.canonicalUrl);
}

function primaryEntityId(page: SEOPage, url: string): string {
  switch (page.schemaType) {
    case "FAQPage":
      return `${url}#faq`;
    case "HowTo":
      return `${url}#howto`;
    case "Service":
      return `${url}#service`;
    case "Organization":
      return `${url}#organization-page`;
    case "LocalBusiness":
      return `${url}#localbusiness`;
    default:
      return `${url}#article`;
  }
}

function buildImageObject(imageUrl?: string): SchemaNode {
  return {
    "@type": "ImageObject",
    url: toAbsoluteUrl(imageUrl ?? DEFAULT_OG_IMAGE),
    width: DEFAULT_OG_IMAGE_WIDTH,
    height: DEFAULT_OG_IMAGE_HEIGHT,
  };
}

function stripHtml(value: string): string {
  return value.replace(/<[^>]+>/g, " ").replace(/\s+/g, " ").trim();
}

function wordCount(page: SEOPage): number {
  const faqText = (page.faqs ?? []).map((faq) => `${faq.question} ${faq.answer}`).join(" ");
  const howToText = (page.howToSteps ?? []).map((step) => `${step.name} ${step.text}`).join(" ");
  const merged = `${stripHtml(page.content)} ${faqText} ${howToText}`.trim();
  return merged ? merged.split(/\s+/).filter(Boolean).length : 0;
}

function buildKeywordThing(name: string): SchemaNode {
  return {
    "@type": "Thing",
    name,
  };
}

function buildBreadcrumb(
  basePath: string,
  clusterLabel: string,
  page: SEOPage,
): SchemaNode {
  const url = pageUrl(basePath, page);

  return {
    "@context": "https://schema.org",
    "@type": "BreadcrumbList",
    "@id": `${url}#breadcrumb`,
    itemListElement: [
      { "@type": "ListItem", position: 1, name: "Home", item: SITE_URL },
      {
        "@type": "ListItem",
        position: 2,
        name: clusterLabel,
        item: `${SITE_URL}/${basePath}`,
      },
      {
        "@type": "ListItem",
        position: 3,
        name: page.title,
        item: url,
      },
    ],
  };
}

function buildWebPageSchema(page: SEOPage, basePath: string): SchemaNode {
  const url = pageUrl(basePath, page);

  return {
    "@context": "https://schema.org",
    "@type": "WebPage",
    "@id": `${url}#webpage`,
    url,
    name: page.title,
    headline: page.title,
    description: page.metaDescription,
    inLanguage: DEFAULT_LANGUAGE,
    isPartOf: {
      "@type": "WebSite",
      "@id": WEBSITE_ID,
      url: SITE_URL,
      name: SITE_NAME,
    },
    breadcrumb: { "@id": `${url}#breadcrumb` },
    about: [buildKeywordThing(page.primaryKeyword), { "@id": ORGANIZATION_ID }],
    publisher: { "@id": ORGANIZATION_ID },
    primaryImageOfPage: buildImageObject(page.og?.ogImageUrl ?? page.heroImage),
    datePublished: page.automation?.generatedAt ?? page.lastUpdated,
    dateModified: page.lastUpdated,
    mainEntity: { "@id": primaryEntityId(page, url) },
  };
}

function buildArticleSchema(page: SEOPage, basePath: string): SchemaNode {
  const url = pageUrl(basePath, page);

  return {
    "@context": "https://schema.org",
    "@type": "Article",
    "@id": `${url}#article`,
    headline: page.title,
    description: page.metaDescription,
    url,
    datePublished: page.automation?.generatedAt ?? page.lastUpdated,
    dateModified: page.lastUpdated,
    author: {
      "@type": "Organization",
      name: page.author || SITE_NAME,
    },
    publisher: { "@id": ORGANIZATION_ID },
    mainEntityOfPage: { "@id": `${url}#webpage` },
    image: buildImageObject(page.og?.ogImageUrl ?? page.heroImage),
    keywords: [page.primaryKeyword, ...page.secondaryKeywords].join(", "),
    articleSection: page.outline,
    wordCount: wordCount(page),
    inLanguage: DEFAULT_LANGUAGE,
    about: [buildKeywordThing(page.primaryKeyword), { "@id": ORGANIZATION_ID }],
    mentions: page.secondaryKeywords.map(buildKeywordThing),
  };
}

function buildServiceSchema(page: SEOPage, basePath: string): SchemaNode {
  const url = pageUrl(basePath, page);

  return {
    "@context": "https://schema.org",
    "@type": "Service",
    "@id": `${url}#service`,
    serviceType: page.primaryKeyword,
    name: page.title,
    description: page.metaDescription,
    url,
    provider: { "@id": ORGANIZATION_ID },
    brand: { "@id": ORGANIZATION_ID },
    areaServed: [
      { "@type": "Place", name: "Delhi-NCR" },
      { "@type": "Country", name: "India" },
    ],
    availableChannel: {
      "@type": "ServiceChannel",
      serviceUrl: url,
      availableLanguage: ["English", "Hindi"],
      serviceLocation: {
        "@type": "Place",
        name: "Delhi-NCR and online",
      },
    },
    audience: {
      "@type": "Audience",
      audienceType: page.searchIntent === "transactional"
        ? "Families booking puja, pandit, or spiritual consultation services"
        : "General Hindu audience",
    },
    category: [page.primaryKeyword, ...page.secondaryKeywords],
    image: buildImageObject(page.og?.ogImageUrl ?? page.heroImage),
  };
}

function buildFAQSchema(page: SEOPage, basePath: string): SchemaNode | undefined {
  if (!page.faqs || page.faqs.length === 0) return undefined;

  const url = pageUrl(basePath, page);

  return {
    "@context": "https://schema.org",
    "@type": "FAQPage",
    "@id": `${url}#faq`,
    mainEntity: page.faqs.map((faq) => ({
      "@type": "Question",
      name: faq.question,
      acceptedAnswer: { "@type": "Answer", text: faq.answer },
    })),
  };
}

function buildHowToSchema(page: SEOPage, basePath: string): SchemaNode | undefined {
  if (!page.howToSteps || page.howToSteps.length === 0) return undefined;

  const url = pageUrl(basePath, page);

  return {
    "@context": "https://schema.org",
    "@type": "HowTo",
    "@id": `${url}#howto`,
    name: page.title,
    description: page.metaDescription,
    url,
    image: buildImageObject(page.og?.ogImageUrl ?? page.heroImage),
    totalTime: `PT${Math.max(page.readingTime, 5)}M`,
    step: page.howToSteps.map((step, i) => ({
      "@type": "HowToStep",
      position: i + 1,
      name: step.name,
      text: step.text,
      url: `${url}#step-${i + 1}`,
      ...(step.imageUrl ? { image: buildImageObject(step.imageUrl) } : {}),
    })),
  };
}

function buildOrganizationSchema(page: SEOPage, basePath: string): SchemaNode {
  const url = pageUrl(basePath, page);

  return {
    "@context": "https://schema.org",
    ...ORGANIZATION,
    "@id": `${url}#organization-page`,
    description: page.metaDescription,
    mainEntityOfPage: { "@id": `${url}#webpage` },
  };
}

function buildLocalBusinessSchema(page: SEOPage, basePath: string): SchemaNode {
  const url = pageUrl(basePath, page);

  return {
    "@context": "https://schema.org",
    "@type": "LocalBusiness",
    "@id": `${url}#localbusiness`,
    name: `${SITE_NAME} Delhi-NCR`,
    url,
    description: page.metaDescription,
    image: buildImageObject(page.og?.ogImageUrl ?? page.heroImage),
    priceRange: "$$",
    areaServed: [
      { "@type": "Place", name: "Delhi-NCR" },
      { "@type": "Country", name: "India" },
    ],
    serviceArea: [
      { "@type": "Place", name: "Delhi-NCR" },
      { "@type": "Country", name: "India" },
    ],
    address: {
      "@type": "PostalAddress",
      addressLocality: "Delhi",
      addressRegion: "Delhi-NCR",
      addressCountry: "IN",
    },
    availableLanguage: ["English", "Hindi"],
    brand: { "@id": ORGANIZATION_ID },
    parentOrganization: { "@id": ORGANIZATION_ID },
  };
}

/**
 * Generate all applicable schemas for a page.
 * Returns an array of valid schema objects ready for injection as JSON-LD.
 */
export function generateSchemas(
  page: SEOPage,
  basePath: string,
  clusterLabel: string,
): SchemaNode[] {
  if (page.noindex) return [];

  const schemas: SchemaNode[] = [];

  schemas.push(buildBreadcrumb(basePath, clusterLabel, page));
  schemas.push(buildWebPageSchema(page, basePath));

  switch (page.schemaType) {
    case "Article": {
      schemas.push(buildArticleSchema(page, basePath));
      break;
    }

    case "Service": {
      schemas.push(buildServiceSchema(page, basePath));
      break;
    }

    case "FAQPage": {
      const faq = buildFAQSchema(page, basePath);
      if (faq) schemas.push(faq);
      schemas.push(buildArticleSchema(page, basePath));
      break;
    }

    case "HowTo": {
      const howTo = buildHowToSchema(page, basePath);
      if (howTo) schemas.push(howTo);
      schemas.push(buildArticleSchema(page, basePath));
      break;
    }

    case "Organization": {
      schemas.push(buildOrganizationSchema(page, basePath));
      break;
    }

    case "LocalBusiness": {
      schemas.push(buildLocalBusinessSchema(page, basePath));
      break;
    }

    default: {
      schemas.push(buildArticleSchema(page, basePath));
      break;
    }
  }

  if (page.schemaType !== "FAQPage") {
    const faq = buildFAQSchema(page, basePath);
    if (faq) schemas.push(faq);
  }

  return schemas.filter(
    (schema): schema is SchemaNode => schema !== null && schema !== undefined,
  );
}
