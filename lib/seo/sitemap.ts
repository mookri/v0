import { existsSync, readdirSync, statSync } from "fs";
import { dirname, join, relative, sep } from "path";
import type { MetadataRoute } from "next";
import { getAllPages } from "@/lib/content/loader";
import { getClusterMeta, getRegistryEntry } from "@/lib/content/registry";
import { SITE_URL } from "@/lib/seo/site";
import { normalizePublicPath, toPublicUrl } from "@/lib/seo/routes";
import { SITEMAP_CHUNK_SIZE } from "@/lib/seo/sitemap.constants";

type SitemapEntry = MetadataRoute.Sitemap[number];

const APP_DIR = join(process.cwd(), "app");
const EXCLUDED_TOP_LEVEL_SEGMENTS = new Set(["api", "auth"]);
const EXCLUDED_STATIC_ROUTES = new Set(["/robots", "/sitemap"]);

function collectPageFiles(dir: string): string[] {
  if (!existsSync(dir)) return [];

  const files: string[] = [];
  for (const entry of readdirSync(dir, { withFileTypes: true })) {
    const fullPath = join(dir, entry.name);

    if (entry.isDirectory()) {
      files.push(...collectPageFiles(fullPath));
      continue;
    }

    if (entry.isFile() && entry.name === "page.tsx") {
      files.push(fullPath);
    }
  }

  return files;
}

function normalizeRouteFromPageFile(filePath: string): string | null {
  const routeDir = dirname(relative(APP_DIR, filePath));
  const rawSegments = routeDir === "." ? [] : routeDir.split(sep).filter(Boolean);
  const publicSegments: string[] = [];

  for (const segment of rawSegments) {
    if (segment.startsWith("(") && segment.endsWith(")")) continue;
    if (segment.startsWith("@") || segment.startsWith("_")) return null;
    if (segment.includes("[")) return null;
    publicSegments.push(segment);
  }

  if (publicSegments.length > 0 && EXCLUDED_TOP_LEVEL_SEGMENTS.has(publicSegments[0])) {
    return null;
  }

  const route = publicSegments.length === 0 ? "/" : `/${publicSegments.join("/")}`;
  return EXCLUDED_STATIC_ROUTES.has(route) ? null : route;
}

function inferPriority(route: string): number {
  if (route === "/") return 1;
  if (route === "/services" || route === "/pujas") return 0.95;
  if (route.startsWith("/pujas/")) return 0.84;
  if (route.startsWith("/compare/")) return 0.88;
  if (route.startsWith("/centers/")) return 0.84;
  if (route === "/about" || route === "/contact" || route === "/book") return 0.85;
  if (route.startsWith("/explore/")) return 0.82;
  if (route.startsWith("/services/") || route.startsWith("/pandit-services/")) return 0.8;
  if (route.startsWith("/astrology/") || route.startsWith("/vastu/")) return 0.79;
  if (route.startsWith("/ceremonies/") || route.startsWith("/festival-rituals/")) return 0.78;
  if (route.startsWith("/ritual-guides/") || route.startsWith("/spiritual-guides/")) return 0.76;
  if (route.startsWith("/blog/")) return 0.75;
  if (route.startsWith("/faq/")) return 0.72;
  if (route.includes("policy") || route.includes("terms")) return 0.35;

  const depth = route.split("/").filter(Boolean).length;
  return Math.max(0.4, 0.8 - depth * 0.08);
}

function inferChangeFrequency(route: string): SitemapEntry["changeFrequency"] {
  if (route === "/") return "daily";
  if (route.startsWith("/resources/news")) return "daily";
  if (route.startsWith("/pujas/")) return "weekly";
  if (route.startsWith("/compare/") || route.startsWith("/blog/")) return "weekly";
  if (route.startsWith("/centers/")) return "monthly";
  if (route.startsWith("/explore/") || route.startsWith("/services/") || route.startsWith("/ceremonies/")) {
    return "weekly";
  }
  if (route.includes("policy") || route.includes("terms")) return "yearly";
  if (route === "/about" || route === "/contact") return "monthly";
  return "weekly";
}

function discoverStaticAppEntries(): SitemapEntry[] {
  return collectPageFiles(APP_DIR).flatMap((filePath) => {
    const route = normalizeRouteFromPageFile(filePath);
    if (!route) return [];

    return [{
      url: toPublicUrl(route),
      lastModified: new Date(statSync(filePath).mtime),
      changeFrequency: inferChangeFrequency(route),
      priority: inferPriority(route),
    }];
  });
}

function discoverSeoContentEntries(): SitemapEntry[] {
  return getAllPages().flatMap((page) => {
    if (page.noindex) return [];

    const entry = getRegistryEntry(page.slug);
    if (!entry) return [];

    const clusterMeta = getClusterMeta(entry.cluster);
    if (!clusterMeta) return [];

    const route = normalizePublicPath(`/${clusterMeta.basePath}/${page.slug}`);
    return [{
      url: toPublicUrl(route),
      lastModified: new Date(page.automation?.generatedAt ?? page.lastUpdated),
      changeFrequency: inferChangeFrequency(route),
      priority: inferPriority(route),
    } satisfies SitemapEntry];
  });
}

function dedupeEntries(entries: SitemapEntry[]): SitemapEntry[] {
  const byUrl = new Map<string, SitemapEntry>();
  for (const entry of entries) {
    byUrl.set(entry.url, entry);
  }

  return [...byUrl.values()].sort((left, right) => left.url.localeCompare(right.url));
}

export function buildSitemapEntries(): MetadataRoute.Sitemap {
  return dedupeEntries([
    ...discoverStaticAppEntries(),
    ...discoverSeoContentEntries(),
  ]);
}

export function buildSitemapChunks(entries: MetadataRoute.Sitemap): MetadataRoute.Sitemap[] {
  const chunks: MetadataRoute.Sitemap[] = [];

  for (let index = 0; index < entries.length; index += SITEMAP_CHUNK_SIZE) {
    chunks.push(entries.slice(index, index + SITEMAP_CHUNK_SIZE));
  }

  return chunks;
}

function formatLastModified(date?: string | Date): string | undefined {
  if (!date) return undefined;
  const value = date instanceof Date ? date : new Date(date);
  return Number.isNaN(value.getTime()) ? undefined : value.toISOString();
}

export function buildSitemapIndexXml(entries: MetadataRoute.Sitemap): string {
  const chunks = buildSitemapChunks(entries);
  const sitemapNodes = chunks.map((chunk, index) => {
    const lastModified = formatLastModified(
      chunk.reduce<Date | undefined>((latest, entry) => {
        const current = entry.lastModified ? new Date(entry.lastModified) : undefined;
        if (!current || Number.isNaN(current.getTime())) return latest;
        if (!latest || current > latest) return current;
        return latest;
      }, undefined),
    );

    return [
      "  <sitemap>",
      `    <loc>${SITE_URL}/sitemaps/${index}.xml</loc>`,
      lastModified ? `    <lastmod>${lastModified}</lastmod>` : "",
      "  </sitemap>",
    ].filter(Boolean).join("\n");
  }).join("\n");

  return `<?xml version="1.0" encoding="UTF-8"?>\n<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">\n${sitemapNodes}\n</sitemapindex>\n`;
}

export function generateSitemapXml(entries: MetadataRoute.Sitemap): string {
  const xmlEntries = entries.map((entry) => {
    const lastModified = formatLastModified(entry.lastModified);
    const changeFrequency = entry.changeFrequency ? `    <changefreq>${entry.changeFrequency}</changefreq>\n` : "";
    const priority = typeof entry.priority === "number" ? `    <priority>${entry.priority.toFixed(2)}</priority>\n` : "";

    return [
      "  <url>",
      `    <loc>${entry.url}</loc>`,
      lastModified ? `    <lastmod>${lastModified}</lastmod>` : "",
      changeFrequency.trimEnd(),
      priority.trimEnd(),
      "  </url>",
    ].filter(Boolean).join("\n");
  }).join("\n");

  return `<?xml version="1.0" encoding="UTF-8"?>\n<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">\n${xmlEntries}\n</urlset>\n`;
}
