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
    url: toAbsoluteUrl("/navdhya-logo.png"),
    width: 2157,
    height: 570,
  },
  foundingDate: "2023",
  areaServed: [
    { "@type": "Place", name: "Delhi NCR" },
    { "@type": "Country", name: "India" },
  ],
  sameAs: [
    "https://www.instagram.com/thenavdhya",
    "https://www.facebook.com/thenavdhya",
    "https://www.youtube.com/@thenavdhya",
  ],
  contactPoint: {
    "@type": "ContactPoint",
    contactType: "Customer Service",
    telephone: "+91-7289000377",
    email: "help@navdhya.com",
    availableLanguage: ["English", "Hindi"],
  },
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

function ensureArray(value: unknown): string[] {
  if (Array.isArray(value)) return value.filter(Boolean);
  if (typeof value === "string" && value.trim() !== "") return [value.trim()];
  return [];
}

function describeComparisonRow(row: Record<string, string>): string {
  return Object.entries(row)
    .map(([key, value]) => `${key}: ${value}`)
    .join(" | ");
}

function buildKeywordThing(name: string): SchemaNode {
  return {
    "@type": "Thing",
    name,
  };
}

function extractPriceNumbers(priceRange?: string): { lowPrice?: string; highPrice?: string } {
  if (!priceRange) return {};
  const matches = priceRange.match(/\d[\d,]*/g)?.map((match) => match.replace(/,/g, ""));
  if (!matches || matches.length === 0) return {};

  return {
    lowPrice: matches[0],
    highPrice: matches.length > 1 ? matches[matches.length - 1] : matches[0],
  };
}

function buildServiceOffer(page: SEOPage, url: string): SchemaNode | undefined {
  const priceRange = page.serviceDetails?.priceRange;
  const { lowPrice, highPrice } = extractPriceNumbers(priceRange);
  if (!lowPrice) return undefined;

  return {
    "@type": "AggregateOffer",
    priceCurrency: "INR",
    lowPrice,
    highPrice: highPrice ?? lowPrice,
    offerCount: page.serviceDetails?.packages?.length ?? 1,
    availability: "https://schema.org/InStock",
    url,
  };
}

function buildBreadcrumb(basePath: string, clusterLabel: string, page: SEOPage): SchemaNode {
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
    primaryImageOfPage: buildImageObject(page.og?.ogImageUrl ?? page.heroImage ?? page.serviceDetails?.image),
    datePublished: page.automation?.generatedAt ?? page.lastUpdated,
    dateModified: page.lastUpdated,
    mainEntity: { "@id": primaryEntityId(page, url) },
  };
}

function buildArticleSchema(page: SEOPage, basePath: string): SchemaNode {
  const url = pageUrl(basePath, page);
  const secondaryKeywords = ensureArray(page.secondaryKeywords);
  const keywordVariants = ensureArray(page.keywordVariants);
  const allKeywords = [page.primaryKeyword, ...secondaryKeywords, ...keywordVariants];

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
    ...(page.reviewedBy
      ? {
          reviewedBy: {
            "@type": "Person",
            name: page.reviewedBy,
          },
        }
      : {}),
    publisher: { "@id": ORGANIZATION_ID },
    mainEntityOfPage: { "@id": `${url}#webpage` },
    image: buildImageObject(page.og?.ogImageUrl ?? page.heroImage ?? page.serviceDetails?.image),
    keywords: allKeywords.join(", "),
    articleSection: page.outline,
    wordCount: wordCount(page),
    inLanguage: DEFAULT_LANGUAGE,
    about: [buildKeywordThing(page.primaryKeyword), { "@id": ORGANIZATION_ID }],
    mentions: [...secondaryKeywords, ...keywordVariants].map(buildKeywordThing),
  };
}

function buildServiceSchema(page: SEOPage, basePath: string): SchemaNode {
  const url = pageUrl(basePath, page);
  const secondaryKeywords = ensureArray(page.secondaryKeywords);
  const keywordVariants = ensureArray(page.keywordVariants);
  const targetCities = ensureArray(page.targetCities);
  const serviceAreas = ensureArray(page.serviceDetails?.serviceAreas);
  const areaServed = [
    { "@type": "Place", name: "Delhi NCR" },
    { "@type": "Country", name: "India" },
    ...Array.from(new Set([...targetCities, ...serviceAreas])).map((city) => ({ "@type": "Place", name: city })),
  ];

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
    areaServed,
    availableChannel: {
      "@type": "ServiceChannel",
      serviceUrl: url,
      availableLanguage: ["English", "Hindi"],
      serviceLocation: {
        "@type": "Place",
        name: "Delhi NCR and online",
      },
    },
    audience: {
      "@type": "Audience",
      audienceType: page.searchIntent === "transactional"
        ? "Families booking puja, pandit, or spiritual consultation services"
        : "General Hindu audience",
    },
    category: [page.primaryKeyword, ...secondaryKeywords, ...keywordVariants],
    image: buildImageObject(page.og?.ogImageUrl ?? page.heroImage ?? page.serviceDetails?.image),
    ...(page.serviceDetails?.summary ? { slogan: page.serviceDetails.summary } : {}),
    ...(buildServiceOffer(page, url) ? { offers: buildServiceOffer(page, url) } : {}),
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
    image: buildImageObject(page.og?.ogImageUrl ?? page.heroImage ?? page.serviceDetails?.image),
    totalTime: `PT${Math.max(page.readingTime, 5)}M`,
    step: page.howToSteps.map((step, index) => ({
      "@type": "HowToStep",
      position: index + 1,
      name: step.name,
      text: step.text,
      url: `${url}#step-${index + 1}`,
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
  const targetCities = ensureArray(page.targetCities);
  const serviceAreas = ensureArray(page.serviceDetails?.serviceAreas);
  const priceRange = page.serviceDetails?.priceRange ?? "Rs. 2,500 - Rs. 5,500";
  const locationNodes = Array.from(new Set([...targetCities, ...serviceAreas])).map((city) => ({
    "@type": "Place",
    name: city,
  }));

  return {
    "@context": "https://schema.org",
    "@type": "LocalBusiness",
    "@id": `${url}#localbusiness`,
    name: `${SITE_NAME} Delhi NCR`,
    url,
    description: page.metaDescription,
    image: buildImageObject(page.og?.ogImageUrl ?? page.heroImage ?? page.serviceDetails?.image),
    priceRange,
    areaServed: [
      { "@type": "Place", name: "Delhi NCR" },
      { "@type": "Country", name: "India" },
      ...locationNodes,
    ],
    serviceArea: [
      { "@type": "Place", name: "Delhi NCR" },
      { "@type": "Country", name: "India" },
      ...locationNodes,
    ],
    address: {
      "@type": "PostalAddress",
      streetAddress: "Pandav Nagar",
      addressLocality: "Delhi",
      addressRegion: "Delhi",
      postalCode: "110092",
      addressCountry: "IN",
    },
    telephone: "+91-7289000377",
    email: "help@navdhya.com",
    availableLanguage: ["English", "Hindi"],
    brand: { "@id": ORGANIZATION_ID },
    parentOrganization: { "@id": ORGANIZATION_ID },
  };
}

function buildItemListSchema(page: SEOPage, basePath: string): SchemaNode | undefined {
  const rows = page.comparisonTable ?? [];
  if (rows.length === 0) return undefined;

  const url = pageUrl(basePath, page);

  return {
    "@context": "https://schema.org",
    "@type": "ItemList",
    "@id": `${url}#itemlist`,
    name: `${page.title} comparison snapshot`,
    itemListElement: rows.map((row, index) => ({
      "@type": "ListItem",
      position: index + 1,
      name: row.feature ?? row.topic ?? row.name ?? `Comparison point ${index + 1}`,
      description: describeComparisonRow(row),
      url: `${url}#comparison-${index + 1}`,
    })),
  };
}

export function generateSchemas(page: SEOPage, basePath: string, clusterLabel: string): SchemaNode[] {
  if (page.noindex) return [];

  const schemas: SchemaNode[] = [];
  schemas.push(buildBreadcrumb(basePath, clusterLabel, page));
  schemas.push(buildWebPageSchema(page, basePath));

  switch (page.schemaType) {
    case "Article":
      schemas.push(buildArticleSchema(page, basePath));
      break;
    case "Service":
      schemas.push(buildServiceSchema(page, basePath));
      break;
    case "FAQPage":
      schemas.push(buildArticleSchema(page, basePath));
      break;
    case "HowTo": {
      const howTo = buildHowToSchema(page, basePath);
      if (howTo) schemas.push(howTo);
      schemas.push(buildArticleSchema(page, basePath));
      break;
    }
    case "Organization":
      schemas.push(buildOrganizationSchema(page, basePath));
      break;
    case "LocalBusiness":
      schemas.push(buildLocalBusinessSchema(page, basePath));
      break;
    default:
      schemas.push(buildArticleSchema(page, basePath));
      break;
  }

  const itemList = buildItemListSchema(page, basePath);
  if (itemList) schemas.push(itemList);

  return schemas.filter((schema): schema is SchemaNode => schema !== null && schema !== undefined);
}
