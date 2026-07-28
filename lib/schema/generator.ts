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
import { generateAEO } from "@/lib/seo/aeo";

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

function audienceLanguages(page: SEOPage): string[] {
  switch (page.audienceRegion) {
    case "US":
    case "UK":
    case "Australia":
    case "Ireland":
    case "Germany":
    case "global":
    case "global-diaspora":
      return ["English", "Hindi"];
    default:
      return ["English", "Hindi"];
  }
}

function areaServedNodes(page: SEOPage): SchemaNode[] {
  const cities = ensureArray(page.targetCities);
  const countryName = page.countryCode
    ? ({
        US: "United States",
        UK: "United Kingdom",
        AU: "Australia",
        IE: "Ireland",
        DE: "Germany",
      } as Record<string, string>)[page.countryCode] ?? page.countryCode
    : undefined;

  const nodes: SchemaNode[] = [];
  if (countryName) {
    nodes.push({ "@type": "Country", name: countryName });
  }
  if (!countryName) {
    nodes.push({ "@type": "Place", name: "Delhi NCR" }, { "@type": "Country", name: "India" });
  }
  cities.forEach((city) => nodes.push({ "@type": "Place", name: city }));
  return nodes;
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

function buildOfferCatalog(page: SEOPage): SchemaNode | undefined {
  const packages = page.serviceDetails?.packages ?? [];
  if (packages.length === 0) return undefined;

  return {
    "@type": "OfferCatalog",
    name: `${page.title} packages`,
    itemListElement: packages.map((pkg) => ({
      "@type": "Offer",
      name: pkg.name,
      description: pkg.description,
      price: pkg.price,
      priceCurrency: "INR",
    })),
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
    ...(page.speakableFaqs && page.speakableFaqs.length > 0
      ? {
          speakable: {
            "@type": "SpeakableSpecification",
            xpath: page.speakableFaqs.slice(0, 3).map((_, index) => `/html/body/main/section[${index + 1}]`),
          },
        }
      : {}),
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
    ...(page.speakableFaqs && page.speakableFaqs.length > 0
      ? {
          speakable: {
            "@type": "SpeakableSpecification",
            cssSelector: ["main h2", "main h3", "main p"].slice(0, page.speakableFaqs.length),
          },
        }
      : {}),
  };
}

function buildServiceSchema(page: SEOPage, basePath: string): SchemaNode {
  const url = pageUrl(basePath, page);
  const secondaryKeywords = ensureArray(page.secondaryKeywords);
  const keywordVariants = ensureArray(page.keywordVariants);
  const areaServed = areaServedNodes(page);
  const aggregateOffer = buildServiceOffer(page, url);
  const offerCatalog = buildOfferCatalog(page);

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
    availableLanguage: audienceLanguages(page),
    availableChannel: {
      "@type": "ServiceChannel",
      serviceUrl: url,
      availableLanguage: audienceLanguages(page),
      serviceLocation: {
        "@type": "Place",
        name: page.cityName && page.countryCode
          ? `${page.cityName}, ${page.countryCode}`
          : "Delhi NCR and online",
      },
    },
    audience: {
      "@type": "Audience",
      audienceType: page.audienceRegion && page.audienceRegion !== "india"
        ? `Hindu families in ${page.audienceRegion} seeking supported ritual guidance`
        : page.searchIntent === "transactional"
          ? "Families booking puja, pandit, or spiritual consultation services"
          : "General Hindu audience",
    },
    category: [page.primaryKeyword, ...secondaryKeywords, ...keywordVariants],
    image: buildImageObject(page.og?.ogImageUrl ?? page.heroImage ?? page.serviceDetails?.image),
    ...(page.deliveryMode ? { serviceMode: page.deliveryMode } : {}),
    ...(page.serviceDetails?.summary ? { slogan: page.serviceDetails.summary } : {}),
    ...(aggregateOffer ? { offers: aggregateOffer } : {}),
    ...(offerCatalog ? { hasOfferCatalog: offerCatalog } : {}),
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
  const priceRange = page.serviceDetails?.priceRange ?? "Rs. 2,500 - Rs. 5,500";
  const locationNodes = areaServedNodes(page);

  return {
    "@context": "https://schema.org",
    "@type": "LocalBusiness",
    "@id": `${url}#localbusiness`,
    name: `${SITE_NAME} Delhi NCR`,
    url,
    description: page.metaDescription,
    image: buildImageObject(page.og?.ogImageUrl ?? page.heroImage ?? page.serviceDetails?.image),
    priceRange,
    areaServed: locationNodes,
    serviceArea: locationNodes,
    address: {
      "@type": "PostalAddress",
      streetAddress: page.countryCode ? "Remote support and India-side coordination" : "Pandav Nagar",
      addressLocality: page.cityName ?? "Delhi",
      addressRegion: page.countryCode ?? "Delhi",
      postalCode: page.countryCode ? "000000" : "110092",
      addressCountry: page.countryCode ?? "IN",
    },
    telephone: "+91-7289000377",
    email: "help@navdhya.com",
    availableLanguage: audienceLanguages(page),
    brand: { "@id": ORGANIZATION_ID },
    parentOrganization: { "@id": ORGANIZATION_ID },
  };
}

function buildFaqSchema(page: SEOPage, basePath: string): SchemaNode | undefined {
  const faqs = page.faqs ?? [];
  if (faqs.length === 0) return undefined;

  const url = pageUrl(basePath, page);
  return {
    "@context": "https://schema.org",
    "@type": "FAQPage",
    "@id": `${url}#faq`,
    url,
    mainEntity: faqs.map((faq) => ({
      "@type": "Question",
      name: faq.question,
      acceptedAnswer: {
        "@type": "Answer",
        text: faq.answer,
      },
    })),
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
  const url = pageUrl(basePath, page);
  const aeo = generateAEO({ page, url });

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
      if (buildFaqSchema(page, basePath)) schemas.push(buildFaqSchema(page, basePath)!);
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

  if (aeo.keyFacts) schemas.push(aeo.keyFacts);
  if (aeo.tldr) schemas.push(aeo.tldr);
  if (aeo.claimReview) schemas.push(aeo.claimReview);

  return schemas.filter((schema): schema is SchemaNode => schema !== null && schema !== undefined);
}
