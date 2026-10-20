import { getAllPages } from "../apps/web/lib/content/loader";
import { getClusterMeta, getRegistryEntry } from "../apps/web/lib/content/registry";
import { generatePageMetadata } from "../apps/web/lib/seo/metadata";
import { buildSitemapEntries } from "../apps/web/lib/seo/sitemap";
import { generateSchemas } from "../apps/web/lib/schema/generator";

const MOJIBAKE_PATTERNS = [/â‚¹/i, /Ã¢â€šÂ¹/i, /â€“/i, /Ã¢â‚¬â€œ/i, /â€”/i];
const BASE_SLUGS = new Set(["what-is-navdhya", "about-navdhya", "navdhya-services"]);

function stripHtml(value: string): string {
  return value.replace(/<[^>]+>/g, " ").replace(/\s+/g, " ").trim();
}

function countWords(content: string, faqs: Array<{ question: string; answer: string }> = []) {
  const text = `${stripHtml(content)} ${faqs.map((faq) => `${faq.question} ${faq.answer}`).join(" ")}`.trim();
  return text ? text.split(/\s+/).filter(Boolean).length : 0;
}

function countH2s(content: string) {
  return (content.match(/<h2>/g) ?? []).length;
}

function routeForSlug(slug: string) {
  const entry = getRegistryEntry(slug);
  if (!entry) return null;
  const clusterMeta = getClusterMeta(entry.cluster);
  if (!clusterMeta) return null;
  return `/${clusterMeta.basePath}/${slug}`;
}

const pages = getAllPages();
const sitemapUrls = new Set(buildSitemapEntries().map((entry) => entry.url));
const exactTitleMap = new Map<string, string>();
const exactDescriptionMap = new Map<string, string>();

const errors: string[] = [];
const warnings: string[] = [];

for (const page of pages) {
  const route = routeForSlug(page.slug);
  if (!route) {
    errors.push(`[${page.slug}] Missing route resolution from registry.`);
    continue;
  }

  const clusterMeta = getClusterMeta(page.cluster);
  if (!clusterMeta) {
    errors.push(`[${page.slug}] Missing cluster metadata.`);
    continue;
  }

  const metadata = generatePageMetadata(page, clusterMeta.basePath);
  const schemas = generateSchemas(page, clusterMeta.basePath, clusterMeta.label);
  const words = countWords(page.content, page.faqs ?? []);
  const h2s = countH2s(page.content);
  const faqCount = (page.faqs ?? []).length;
  const searchableText = [
    page.title,
    page.metaTitle,
    page.metaDescription,
    page.content,
    JSON.stringify(page.serviceDetails ?? {}),
  ].join(" ");

  if (MOJIBAKE_PATTERNS.some((pattern) => pattern.test(searchableText))) {
    errors.push(`[${page.slug}] Contains mojibake text that should be normalized.`);
  }

  if ((page.metaTitle ?? "").length < 45 || (page.metaTitle ?? "").length > 72) {
    warnings.push(`[${page.slug}] Meta title length is outside the ideal range.`);
  }

  if ((page.metaDescription ?? "").length < 120 || (page.metaDescription ?? "").length > 165) {
    warnings.push(`[${page.slug}] Meta description length is outside the ideal range.`);
  }

  if (page.schemaType === "FAQPage") {
    if (words < 500) {
      errors.push(`[${page.slug}] FAQ page word count ${words} is below 500.`);
    }
    if (words > 1200) {
      warnings.push(`[${page.slug}] FAQ page word count ${words} is above the ideal range.`);
    }
    if (faqCount < 8) {
      errors.push(`[${page.slug}] FAQ page has ${faqCount} FAQs; expected at least 8.`);
    }
    if (faqCount > 12) {
      warnings.push(`[${page.slug}] FAQ page has ${faqCount} FAQs; this may feel overloaded.`);
    }
    if (h2s < 2) {
      errors.push(`[${page.slug}] FAQ page has only ${h2s} H2 sections.`);
    }
  } else {
    const minimumWords = page.cluster === "pujas" ? 900 : 700;
    if (words < minimumWords) {
      errors.push(`[${page.slug}] Page word count ${words} is below ${minimumWords}.`);
    }
    if (words > 1800) {
      warnings.push(`[${page.slug}] Page word count ${words} is above the ideal ceiling.`);
    }
    if (h2s < 5) {
      errors.push(`[${page.slug}] Page has only ${h2s} H2 sections; expected at least 5.`);
    }
    if (h2s > 8) {
      warnings.push(`[${page.slug}] Page has ${h2s} H2 sections; review for section sprawl.`);
    }
    if (faqCount < 3) {
      errors.push(`[${page.slug}] Page has only ${faqCount} FAQs; expected at least 3.`);
    }
    if (faqCount > 8) {
      warnings.push(`[${page.slug}] Page has ${faqCount} FAQs; consider trimming.`);
    }
  }

  if (!page.relatedSlugs || page.relatedSlugs.length < 3) {
    errors.push(`[${page.slug}] Needs at least 3 related slugs.`);
  }

  for (const relatedSlug of page.relatedSlugs ?? []) {
    if (relatedSlug === page.slug) {
      errors.push(`[${page.slug}] Related slug list contains itself.`);
    } else if (!getRegistryEntry(relatedSlug)) {
      errors.push(`[${page.slug}] Related slug "${relatedSlug}" does not resolve.`);
    }
  }

  for (const link of page.requiredInternalLinks ?? []) {
    if (!page.content.includes(link) && !BASE_SLUGS.has(page.slug)) {
      errors.push(`[${page.slug}] Required internal link ${link} is not present in the page content.`);
    }
  }

  if ((metadata.alternates?.canonical ?? "") === "") {
    errors.push(`[${page.slug}] Canonical metadata is missing.`);
  }

  if (schemas.length === 0) {
    errors.push(`[${page.slug}] No schemas were generated.`);
  }

  const routeUrl = `https://www.navdhya.com${route}`;
  if (!sitemapUrls.has(routeUrl)) {
    errors.push(`[${page.slug}] Route ${routeUrl} is missing from sitemap entries.`);
  }

  if (page.cluster === "pujas") {
    if (!page.serviceDetails?.priceRange) {
      errors.push(`[${page.slug}] Puja page is missing serviceDetails.priceRange.`);
    }
    if (!(page.serviceDetails?.packages?.length ?? 0)) {
      errors.push(`[${page.slug}] Puja page is missing service packages.`);
    }
    if (!(page.serviceDetails?.process?.length ?? 0)) {
      errors.push(`[${page.slug}] Puja page is missing ceremony process details.`);
    }
    if (!(page.serviceDetails?.serviceAreas?.length ?? 0)) {
      errors.push(`[${page.slug}] Puja page is missing service area coverage.`);
    }
  }

  if (exactTitleMap.has(page.metaTitle)) {
    errors.push(`[${page.slug}] Meta title duplicates ${exactTitleMap.get(page.metaTitle)}.`);
  } else {
    exactTitleMap.set(page.metaTitle, page.slug);
  }

  if (exactDescriptionMap.has(page.metaDescription)) {
    errors.push(`[${page.slug}] Meta description duplicates ${exactDescriptionMap.get(page.metaDescription)}.`);
  } else {
    exactDescriptionMap.set(page.metaDescription, page.slug);
  }
}

if (warnings.length > 0) {
  console.log("SEO audit warnings:");
  warnings.forEach((warning) => console.log(`- ${warning}`));
}

if (errors.length > 0) {
  console.error("SEO audit failed:");
  errors.forEach((error) => console.error(`- ${error}`));
  process.exit(1);
}

console.log(`SEO audit passed for ${pages.length} pages.`);
