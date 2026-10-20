// /lib/content/loader.ts
// Reads and validates individual JSON content files.
//
// Dual backend:
//   - Sync API (default): always reads disk — used by Jest, sitemap, llms.txt
//     and other build-time callers.
//   - Async API (`*Async`): serves the Laravel SEO API when
//     SEO_CONTENT_SOURCE=api (production), falling back to disk when the
//     API is unreachable. Each response is memoised in-process with a short
//     TTL; cross-request caching is owned by the "use cache" entries in
//     dal/seo.ts.

import { existsSync, readFileSync } from "fs";
import { join } from "path";
import type { SEOPage, ContentCluster } from "./types";
import {
  getRegistryEntry,
  getRegistryEntryAsync,
  getAllSlugs,
  getAllSlugsAsync,
  getSlugsByCluster,
  getSlugsByClusterAsync,
  getClusterMeta,
  getClusterMetaAsync,
} from "./registry";
import { parseJsonString } from "./json";

const PAGES_DIR = join(process.cwd(), "content", "pages");

const USE_API = process.env.SEO_CONTENT_SOURCE === "api";
const API_BASE = (process.env.NAVDHYA_API_BASE ?? "").replace(/\/$/, "");
const API_TENANT = process.env.CUSTOMER_TENANT_ID ?? "";
const API_CACHE_TTL_MS = 60_000;
const API_CACHE_MAX_KEYS = 500;

const TITLE_CONTEXT_BY_CLUSTER: Record<ContentCluster, string> = {
  brand: "Platform Guide & Answers",
  pujas: "Puja Booking Guide",
  services: "Service Guide",
  ceremonies: "Meaning, Steps & Timing",
  "ritual-guides": "Practical Ritual Guide",
  "pandit-services": "Pandit Booking Guide",
  "festival-rituals": "Festival Ritual Guide",
  "spiritual-guides": "Meaning & Practice Guide",
  faq: "Common Questions Answered",
  blog: "Insights & Updates",
  compare: "Comparison & Alternatives Guide",
  centers: "Local Booking Guide",
  astrology: "Astrology Guide",
  vastu: "Vastu Guide",
};

const TEXT_REPLACEMENTS: Array<[RegExp, string]> = [
  [/₹|&#8377;/gi, "Rs. "],
  [/–|—/gi, "-"],
  [/’|‘/gi, "'"],
  [/“|”/gi, '"'],
  [/…/gi, "..."],
  [/Delhi-NCR/gi, "Delhi NCR"],
];

function repairText(value: string): string {
  return TEXT_REPLACEMENTS.reduce(
    (current, [pattern, replacement]) => current.replace(pattern, replacement),
    value,
  );
}

function stripBrandSuffix(value: string): string {
  return repairText(value)
    .replace(/\s*(?:\||-|--)\s*Navdhya\s*$/i, "")
    .replace(/\s+/g, " ")
    .trim();
}

function collapseWhitespace(value: string): string {
  return repairText(value).replace(/\s+/g, " ").trim();
}

function ensurePeriod(value: string): string {
  const trimmed = value.trim();
  if (!trimmed) return trimmed;
  return /[.!?]$/.test(trimmed) ? trimmed : `${trimmed}.`;
}

function escapeHtml(value: string): string {
  return value
    .replace(/&/g, "&amp;")
    .replace(/</g, "&lt;")
    .replace(/>/g, "&gt;")
    .replace(/"/g, "&quot;")
    .replace(/'/g, "&#39;");
}

function shortenText(value: string, maxLength: number): string {
  const cleaned = collapseWhitespace(value);
  if (cleaned.length <= maxLength) return cleaned;
  const shortened = cleaned.slice(0, maxLength);
  const safeCut = shortened.lastIndexOf(" ");
  return `${(safeCut > 40 ? shortened.slice(0, safeCut) : shortened).trim()}...`;
}

function buildSupportCopy(page: SEOPage): string {
  if (page.cluster === "brand") {
    return 'Want a clearer next step? <a href="/about">Learn about Navdhya</a> or <a href="/contact">speak with our team</a>.';
  }

  if (page.cluster === "pandit-services") {
    return 'Need help choosing the right priest? <a href="/pandits">Explore verified pandits</a> or <a href="/book">start a booking request</a>.';
  }

  if (page.cluster === "pujas") {
    return 'Need help choosing the right ceremony? <a href="/pujas">Book a pandit in Delhi NCR</a> or <a href="/compare/best-pandit-booking-delhi">compare local booking options</a>.';
  }

  if (page.cluster === "compare") {
    return 'Comparing booking options? <a href="/pujas">Browse Delhi NCR puja services</a> or <a href="/compare/best-pandit-booking-delhi">review local comparison guides</a>.';
  }

  if (page.cluster === "centers") {
    return 'Planning a local ceremony? <a href="/pujas">Book a pandit in Delhi NCR</a> or <a href="/pandits">explore verified pandits</a>.';
  }

  if (page.cluster === "astrology" || page.cluster === "vastu") {
    return 'Need more personalized planning help? <a href="/consultation">Request a consultation</a> or <a href="/pujas">browse puja services</a>.';
  }

  if (page.cluster === "festival-rituals") {
    return 'Planning a seasonal ceremony? <a href="/pujas">Browse Delhi NCR puja services</a>, review related festival guides, or <a href="/contact">talk to Navdhya</a> for practical support.';
  }

  if (page.cluster === "services" || page.cluster === "spiritual-guides") {
    return 'Need personal guidance? <a href="/consultation">Request a consultation</a> or <a href="/book">book a service</a>.';
  }

  return 'Need help planning this ritual? <a href="/book">Book a puja</a> or <a href="/contact">talk to Navdhya</a>.';
}

function normalizeMetaTitle(page: SEOPage): string {
  const titleBase = stripBrandSuffix(page.metaTitle || page.title);
  if (titleBase.length >= 40) {
    return titleBase;
  }

  const context = TITLE_CONTEXT_BY_CLUSTER[page.cluster];
  return `${titleBase} - ${context}`;
}

function normalizeMetaDescription(page: SEOPage): string {
  const baseDescription = collapseWhitespace(page.metaDescription || "");
  if (!baseDescription) {
    return `Read practical guidance and clear next steps for ${page.title.toLowerCase()} from Navdhya.`;
  }

  if (baseDescription.length >= 120) {
    return ensurePeriod(baseDescription);
  }

  return ensurePeriod(
    `${baseDescription} Read practical guidance, common questions, and helpful next steps from Navdhya.`,
  );
}

function normalizeFaqs(page: SEOPage) {
  return (page.faqs ?? []).map((faq) => ({
    question: collapseWhitespace(faq.question),
    answer: collapseWhitespace(
      faq.answer
        .replace(/Navdhya uses this kind of guidance to help families prepare more clearly, communicate better, and approach the ritual or booking process with greater confidence\./gi, "Clear guidance helps families prepare better and ask the right questions before the ceremony.")
        .replace(/That extra clarity also helps the household ask better follow-up questions and avoid avoidable confusion on the ceremony day\./gi, ""),
    ),
  }));
}

function normalizeHowToSteps(page: SEOPage) {
  return (page.howToSteps ?? []).map((step) => ({
    ...step,
    name: collapseWhitespace(step.name),
    text: ensurePeriod(collapseWhitespace(step.text)),
  }));
}

function normalizeServiceDetails(page: SEOPage) {
  if (!page.serviceDetails) return page.serviceDetails;

  return {
    ...page.serviceDetails,
    summary: page.serviceDetails.summary ? ensurePeriod(collapseWhitespace(page.serviceDetails.summary)) : undefined,
    priceRange: page.serviceDetails.priceRange ? collapseWhitespace(page.serviceDetails.priceRange) : undefined,
    image: page.serviceDetails.image ? collapseWhitespace(page.serviceDetails.image) : undefined,
    galleryImages: (page.serviceDetails.galleryImages ?? []).map(collapseWhitespace),
    packages: (page.serviceDetails.packages ?? []).map((pkg) => ({
      ...pkg,
      name: collapseWhitespace(pkg.name),
      description: ensurePeriod(collapseWhitespace(pkg.description)),
      price: collapseWhitespace(pkg.price),
      duration: pkg.duration ? collapseWhitespace(pkg.duration) : undefined,
    })),
    highlights: (page.serviceDetails.highlights ?? []).map(collapseWhitespace),
    benefits: (page.serviceDetails.benefits ?? []).map((benefit) => ensurePeriod(collapseWhitespace(benefit))),
    process: (page.serviceDetails.process ?? []).map((step) => ({
      step: collapseWhitespace(step.step),
      description: ensurePeriod(collapseWhitespace(step.description)),
    })),
    includes: (page.serviceDetails.includes ?? []).map(collapseWhitespace),
    serviceProof: (page.serviceDetails.serviceProof ?? []).map((point) => ensurePeriod(collapseWhitespace(point))),
    bookingLeadTime: page.serviceDetails.bookingLeadTime
      ? ensurePeriod(collapseWhitespace(page.serviceDetails.bookingLeadTime))
      : undefined,
    serviceAreas: (page.serviceDetails.serviceAreas ?? []).map(collapseWhitespace),
  };
}

function normalizeContent(page: SEOPage): string {
  const shortDescription = escapeHtml(shortenText(normalizeMetaDescription(page), 180));
  const supportCopy = buildSupportCopy(page);
  const introBlock = [
    `<p>${shortDescription}</p>`,
    `<p>${supportCopy}</p>`,
  ].join("");

  const helpfulNextSteps = [
    "<h2>Helpful next steps</h2>",
    `<p>${supportCopy}</p>`,
  ].join("");

  const cleanedContent = collapseWhitespace(page.content)
    .replace(/Explains [^.]+\./gi, "")
    .replace(/Navdhya explains/gi, "This guide explains")
    .replace(/Navdhya treats platform-technology content as both educational and trust-building\./gi, "This guide is meant to be practical and easy to trust.")
    .replace(/Navdhya uses this kind of guidance to help families prepare more clearly, communicate better, and approach the ritual or booking process with greater confidence\./gi, "Clear guidance helps families prepare better and ask the right questions before the ceremony.")
    .replace(/That extra clarity also helps the household ask better follow-up questions and avoid avoidable confusion on the ceremony day\./gi, "")
    .replace(/<p>\s*young /g, "<p>Young ")
    .replace(/\s{2,}/g, " ")
    .trim();

  if (page.cluster === "pujas") {
    return cleanedContent;
  }

  if (/<h2[^>]*>\s*Helpful next steps\s*<\/h2>/i.test(cleanedContent)) {
    return `${introBlock}${cleanedContent}`;
  }

  return `${introBlock}${cleanedContent}${helpfulNextSteps}`;
}

// ---------------------------------------------------------------------------
// Normalisation memo — keyed on slug + content fingerprint so a content edit
// (from API or disk) busts the entry without a process restart.
// ---------------------------------------------------------------------------

const normalizeCache = new Map<string, SEOPage>();
const NORMALIZE_CACHE_MAX_KEYS = 1000;

function normalizePage(page: SEOPage): SEOPage {
  const fingerprint = `${page.content?.length ?? 0}:${page.automation?.contentHash ?? page.lastUpdated ?? ""}`;
  const cacheKey = `${page.slug}:${fingerprint}`;

  const cached = normalizeCache.get(cacheKey);
  if (cached) return cached;

  const normalized: SEOPage = {
    ...page,
    title: collapseWhitespace(page.title),
    metaTitle: normalizeMetaTitle(page),
    metaDescription: normalizeMetaDescription(page),
    content: normalizeContent(page),
    faqs: normalizeFaqs(page),
    howToSteps: normalizeHowToSteps(page),
    heroImage: page.heroImage ? collapseWhitespace(page.heroImage) : undefined,
    proofPoints: (page.proofPoints ?? []).map((point) => ensurePeriod(collapseWhitespace(point))),
    pricingNote: page.pricingNote ? ensurePeriod(collapseWhitespace(page.pricingNote)) : undefined,
    localAngle: page.localAngle ? ensurePeriod(collapseWhitespace(page.localAngle)) : undefined,
    targetCities: (page.targetCities ?? []).map(collapseWhitespace),
    requiredInternalLinks: (page.requiredInternalLinks ?? []).map(collapseWhitespace),
    recommendedAnchorTexts: (page.recommendedAnchorTexts ?? []).map(collapseWhitespace),
    serviceDetails: normalizeServiceDetails(page),
  };

  if (normalizeCache.size >= NORMALIZE_CACHE_MAX_KEYS) {
    const oldest = normalizeCache.keys().next().value;
    if (oldest !== undefined) normalizeCache.delete(oldest);
  }
  normalizeCache.set(cacheKey, normalized);

  return normalized;
}

// ---------------------------------------------------------------------------
// Disk backend (default)
// ---------------------------------------------------------------------------

function loadPageFromDisk(slug: string): SEOPage | null {
  const entry = getRegistryEntry(slug);
  if (!entry) return null;

  const filePath = join(PAGES_DIR, entry.file);
  if (!existsSync(filePath)) return null;

  try {
    const raw = readFileSync(filePath, "utf-8");
    const page = parseJsonString<SEOPage>(raw);

    if (!page.id || !page.slug || !page.title || !page.content) {
      console.warn(`[content-loader] Invalid page data in ${entry.file}`);
      return null;
    }

    return normalizePage(page);
  } catch (err) {
    console.error(`[content-loader] Failed to load ${entry.file}:`, err);
    return null;
  }
}

// ---------------------------------------------------------------------------
// API backend (SEO_CONTENT_SOURCE=api)
// ---------------------------------------------------------------------------

interface ApiPageEnvelope {
  data?: {
    page_json?: SEOPage | string;
    slug?: string;
    noindex?: boolean;
  };
}

const apiPageCache = new Map<string, { value: SEOPage | null; expiry: number }>();

function apiPageCacheGet(slug: string): SEOPage | null | undefined {
  const entry = apiPageCache.get(slug);
  if (!entry) return undefined;
  if (Date.now() > entry.expiry) {
    apiPageCache.delete(slug);
    return undefined;
  }
  return entry.value;
}

function apiPageCacheSet(slug: string, value: SEOPage | null): void {
  if (apiPageCache.size >= API_CACHE_MAX_KEYS) {
    const oldest = apiPageCache.keys().next().value;
    if (oldest !== undefined) apiPageCache.delete(oldest);
  }
  apiPageCache.set(slug, { value, expiry: Date.now() + API_CACHE_TTL_MS });
}

export function clearContentApiCache(): void {
  apiPageCache.clear();
}

async function fetchPageFromApi(slug: string): Promise<SEOPage | null> {
  if (!API_BASE) return null;

  try {
    const headers: Record<string, string> = { Accept: "application/json" };
    if (API_TENANT) headers["X-Tenant-Id"] = API_TENANT;

    const res = await fetch(`${API_BASE}/api/v1/seo_content/seo/pages/${encodeURIComponent(slug)}`, {
      headers,
      // Caching is owned by the "use cache" entries in dal/seo.ts (Cache
      // Components build) plus the in-process TTL below — no fetch-level
      // cache directives here.
      signal: AbortSignal.timeout(5_000),
    });

    // 404 means "not in DB" — a real answer, not a transport failure.
    if (res.status === 404) return null;
    if (!res.ok) return null;

    const json = (await res.json()) as ApiPageEnvelope;
    const raw = json?.data?.page_json;
    const parsed = typeof raw === "string" ? parseJsonString<SEOPage>(raw) : raw;

    if (!parsed?.id || !parsed?.slug || !parsed?.title || !parsed?.content) {
      console.warn(`[content-loader] API returned an invalid page payload for "${slug}"`);
      return null;
    }

    return parsed;
  } catch (err) {
    console.error(`[content-loader] API request failed for "${slug}":`, err);
    return null;
  }
}

// ---------------------------------------------------------------------------
// Sync public API — disk-backed (Jest, sitemap, llms.txt, build-time callers)
// ---------------------------------------------------------------------------

export function getPage(slug: string): SEOPage | null {
  return loadPageFromDisk(slug);
}

export function getAllPages(): SEOPage[] {
  const pages: SEOPage[] = [];
  for (const slug of getAllSlugs()) {
    const page = loadPageFromDisk(slug);
    if (page) pages.push(page);
  }
  return pages;
}

export function getPagesByCluster(cluster: ContentCluster): SEOPage[] {
  const pages: SEOPage[] = [];
  for (const slug of getSlugsByCluster(cluster)) {
    const page = loadPageFromDisk(slug);
    if (page) pages.push(page);
  }
  return pages;
}

export function getStaticParams(): Array<{ slug: string[] }> {
  const slugs = getAllSlugs();
  return slugs.flatMap((slug) => {
    const entry = getRegistryEntry(slug);
    if (!entry) return [{ slug: [slug] }];

    const clusterMeta = getClusterMeta(entry.cluster);
    const basePath = clusterMeta?.basePath ?? entry.cluster;
    if (basePath === "pujas") {
      return [];
    }

    return [{ slug: [basePath, slug] }];
  });
}

export function resolveSlugFromParams(slugParts: string[]): string | null {
  if (!slugParts || slugParts.length === 0) return null;
  if (slugParts.length > 2) return null;

  const pageSlug = slugParts[slugParts.length - 1];
  const entry = getRegistryEntry(pageSlug);
  if (!entry) return null;

  if (slugParts.length === 2) {
    const clusterMeta = getClusterMeta(entry.cluster);
    const expectedBasePath = clusterMeta?.basePath ?? entry.cluster;
    if (expectedBasePath === "pujas") {
      return null;
    }
    if (slugParts[0] !== expectedBasePath) {
      return null;
    }
  }

  return pageSlug;
}

// ---------------------------------------------------------------------------
// Async public API — API-backed with disk fallback (ISR route handlers)
// ---------------------------------------------------------------------------

export async function getPageAsync(slug: string): Promise<SEOPage | null> {
  if (USE_API && API_BASE) {
    const cached = apiPageCacheGet(slug);
    if (cached !== undefined) return cached;

    const fromApi = await fetchPageFromApi(slug);
    // API answered 404 or was unreachable — fall back to the disk corpus so a
    // not-yet-imported slug still renders instead of hard-404ing. The resolved
    // value (including the fallback) is what gets memoised, so every caller in
    // this process agrees on the answer.
    const page = fromApi ? normalizePage(fromApi) : loadPageFromDisk(slug);
    if (!fromApi) {
      console.warn(`[content-loader] SEO API had no page for "${slug}" — served from disk`);
    }

    apiPageCacheSet(slug, page);
    return page;
  }

  return loadPageFromDisk(slug);
}

export async function getAllPagesAsync(): Promise<SEOPage[]> {
  const pages: SEOPage[] = [];
  for (const slug of await getAllSlugsSafe()) {
    const page = await getPageAsync(slug);
    if (page) pages.push(page);
  }
  return pages;
}

export async function getPagesByClusterAsync(cluster: ContentCluster): Promise<SEOPage[]> {
  const pages: SEOPage[] = [];
  for (const slug of await getSlugsByClusterAsync(cluster)) {
    const page = await getPageAsync(slug);
    if (page) pages.push(page);
  }
  return pages;
}

export async function resolveSlugFromParamsAsync(slugParts: string[]): Promise<string | null> {
  if (!slugParts || slugParts.length === 0) return null;
  if (slugParts.length > 2) return null;

  const pageSlug = slugParts[slugParts.length - 1];
  const entry = await getRegistryEntryAsync(pageSlug);
  if (!entry) return null;

  if (slugParts.length === 2) {
    const clusterMeta = await getClusterMetaAsync(entry.cluster);
    const expectedBasePath = clusterMeta?.basePath ?? entry.cluster;
    if (expectedBasePath === "pujas") {
      return null;
    }
    if (slugParts[0] !== expectedBasePath) {
      return null;
    }
  }

  return pageSlug;
}

async function getAllSlugsSafe(): Promise<string[]> {
  try {
    return await getAllSlugsAsync();
  } catch {
    return [];
  }
}
