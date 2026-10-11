// /lib/content/registry.ts
// Builds the SEO content registry from content/pages so route resolution stays
// in sync with disk even during a long-running dev session.
//
// Dual backend:
//   - Sync API (default): always reads disk — used by Jest, sitemap, llms.txt,
//     and any build-time code path.
//   - Async API (`*Async`): serves the Laravel SEO API when
//     SEO_CONTENT_SOURCE=api (production), falling back to disk when the
//     API is unreachable. Responses are memoised in-process with a short
//     TTL; cross-request caching is owned by the "use cache" entries in
//     dal/seo.ts.

import { existsSync, readFileSync, readdirSync, statSync } from "fs";
import { join } from "path";
import type {
  ContentRegistry,
  ContentCluster,
  RegistryEntry,
  ClusterMeta,
  SEOPage,
} from "./types";
import { parseJsonString } from "./json";

const CONTENT_DIR = join(process.cwd(), "content");
const PAGES_DIR = join(CONTENT_DIR, "pages");
const REGISTRY_PATH = join(CONTENT_DIR, "registry.json");

const USE_API = process.env.SEO_CONTENT_SOURCE === "api";
const API_BASE = (process.env.NAVDHYA_API_BASE ?? "").replace(/\/$/, "");
const API_TENANT = process.env.CUSTOMER_TENANT_ID ?? "";
const API_CACHE_TTL_MS = 60_000;
const API_CACHE_MAX_KEYS = 500;

// ---------------------------------------------------------------------------
// In-process TTL cache for API payloads
// ---------------------------------------------------------------------------

const apiCache = new Map<string, { value: unknown; expiry: number }>();

function apiCacheGet<T>(key: string): T | undefined {
  const entry = apiCache.get(key);
  if (!entry) return undefined;
  if (Date.now() > entry.expiry) {
    apiCache.delete(key);
    return undefined;
  }
  return entry.value as T;
}

function apiCacheSet(key: string, value: unknown): void {
  if (apiCache.size >= API_CACHE_MAX_KEYS) {
    const oldest = apiCache.keys().next().value;
    if (oldest !== undefined) apiCache.delete(oldest);
  }
  apiCache.set(key, { value, expiry: Date.now() + API_CACHE_TTL_MS });
}

export function clearRegistryApiCache(): void {
  apiCache.clear();
}

async function fetchJson(path: string): Promise<unknown | null> {
  if (!API_BASE) return null;

  try {
    const headers: Record<string, string> = { Accept: "application/json" };
    if (API_TENANT) headers["X-Tenant-Id"] = API_TENANT;

    const res = await fetch(`${API_BASE}${path}`, {
      headers,
      // Caching is owned by the "use cache" entries in dal/seo.ts (Cache
      // Components build) plus the in-process TTL below — no fetch-level
      // cache directives here.
      signal: AbortSignal.timeout(5_000),
    });

    if (!res.ok) return null;
    return await res.json();
  } catch (err) {
    console.error("[content-registry] API request failed:", path, err);
    return null;
  }
}

// ---------------------------------------------------------------------------
// API payload shapes (mirror SeoPageController::registry())
// ---------------------------------------------------------------------------

interface ApiRegistryPayload {
  clusters?: Record<string, { label?: string; base_path?: string }>;
  pages?: Record<string, { cluster?: string; noindex?: boolean; generated_at?: string }>;
}

function mapApiRegistry(payload: ApiRegistryPayload | null): ContentRegistry | null {
  if (!payload || !payload.pages || !payload.clusters) return null;

  const clusters: Record<string, ClusterMeta> = { ...DEFAULT_CLUSTERS };
  for (const [key, value] of Object.entries(payload.clusters)) {
    if (!value) continue;
    clusters[key] = {
      label: value.label ?? DEFAULT_CLUSTERS[key as ContentCluster]?.label ?? key,
      basePath: value.base_path ?? DEFAULT_CLUSTERS[key as ContentCluster]?.basePath ?? key,
    };
  }

  const pages: Record<string, RegistryEntry> = {};
  for (const [slug, value] of Object.entries(payload.pages)) {
    if (!value?.cluster) continue;
    // The API carries no source filename; synthesise the canonical disk name
    // so callers that only need a stable identifier keep working.
    pages[slug] = { file: `${slug}.json`, cluster: value.cluster as ContentCluster };
  }

  return { clusters: clusters as ContentRegistry["clusters"], pages };
}

async function loadRegistryFromApi(): Promise<ContentRegistry | null> {
  if (!USE_API || !API_BASE) return null;

  const cacheKey = "registry:api";
  const cached = apiCacheGet<ContentRegistry>(cacheKey);
  if (cached) return cached;

  const json = (await fetchJson("/api/v1/seo_content/seo/registry")) as
    | { data?: ApiRegistryPayload }
    | null;
  const mapped = mapApiRegistry(json?.data ?? null);
  if (mapped) apiCacheSet(cacheKey, mapped);
  return mapped;
}

const DEFAULT_CLUSTERS: Record<ContentCluster, ClusterMeta> = {
  brand: { label: "Brand Knowledge", basePath: "explore" },
  pujas: { label: "Delhi NCR Puja Services", basePath: "pujas" },
  services: { label: "Puja Services", basePath: "services" },
  ceremonies: { label: "Hindu Ceremonies", basePath: "ceremonies" },
  "ritual-guides": { label: "Ritual Guides", basePath: "ritual-guides" },
  "pandit-services": { label: "Pandit Services", basePath: "pandit-services" },
  "festival-rituals": { label: "Festival Rituals", basePath: "festival-rituals" },
  "spiritual-guides": { label: "Spiritual Guides", basePath: "spiritual-guides" },
  faq: { label: "Frequently Asked Questions", basePath: "faq" },
  blog: { label: "Navdhya Journal", basePath: "blog" },
  compare: { label: "Delhi NCR Comparisons", basePath: "compare" },
  centers: { label: "Service Centers", basePath: "centers" },
  astrology: { label: "Astrology Guides", basePath: "astrology" },
  vastu: { label: "Vastu Guides", basePath: "vastu" },
};

// ---------------------------------------------------------------------------
// Disk registry (signature-invalidated, TTL-bounded)
// ---------------------------------------------------------------------------

let _cache: ContentRegistry | null = null;
let _cacheSignature = "";
let _cacheTime = 0;
const CACHE_TTL = process.env.NODE_ENV === "production" ? Infinity : 2000;

// process.uptime() is not intercepted by Next.js during prerendering the way
// Date.now() can be, so TTL checks stay monotonic across a build.
function getSafeTime(): number {
  if (typeof process !== "undefined" && process.uptime) return process.uptime() * 1000;
  return 0;
}

function getDiskSignature(): string {
  const signatureParts: string[] = [];

  if (existsSync(REGISTRY_PATH)) {
    const stat = statSync(REGISTRY_PATH);
    signatureParts.push(`registry:${stat.size}:${stat.mtimeMs}`);
  } else {
    signatureParts.push("registry:missing");
  }

  if (!existsSync(PAGES_DIR)) {
    signatureParts.push("pages:missing");
    return signatureParts.join("|");
  }

  const files = readdirSync(PAGES_DIR)
    .filter((file) => file.endsWith(".json"))
    .sort();

  for (const file of files) {
    const stat = statSync(join(PAGES_DIR, file));
    signatureParts.push(`${file}:${stat.size}:${stat.mtimeMs}`);
  }

  return signatureParts.join("|");
}

function loadClusterMetaFromDisk(): Record<ContentCluster, ClusterMeta> {
  if (!existsSync(REGISTRY_PATH)) {
    return { ...DEFAULT_CLUSTERS };
  }

  try {
    const raw = readFileSync(REGISTRY_PATH, "utf-8");
    const parsed = parseJsonString<Partial<ContentRegistry>>(raw);
    return {
      ...DEFAULT_CLUSTERS,
      ...(parsed.clusters ?? {}),
    };
  } catch (err) {
    console.warn("[content-registry] Failed to read cluster metadata from registry.json:", err);
    return { ...DEFAULT_CLUSTERS };
  }
}

function isKnownCluster(value: unknown): value is ContentCluster {
  return typeof value === "string" && value in DEFAULT_CLUSTERS;
}

function buildPageIndexFromDisk(): Record<string, RegistryEntry> {
  const pages: Record<string, RegistryEntry> = {};

  if (!existsSync(PAGES_DIR)) {
    return pages;
  }

  const files = readdirSync(PAGES_DIR)
    .filter((file) => file.endsWith(".json"))
    .sort();

  for (const file of files) {
    const filePath = join(PAGES_DIR, file);

    try {
      const raw = readFileSync(filePath, "utf-8");
      const page = parseJsonString<Partial<SEOPage>>(raw);

      if (!page.slug || !isKnownCluster(page.cluster)) {
        console.warn(`[content-registry] Skipping ${file}: missing valid slug or cluster.`);
        continue;
      }

      if (pages[page.slug]) {
        console.warn(
          `[content-registry] Duplicate slug "${page.slug}" found in ${file}; keeping ${pages[page.slug].file}.`,
        );
        continue;
      }

      pages[page.slug] = {
        file,
        cluster: page.cluster,
      };
    } catch (err) {
      console.warn(`[content-registry] Failed to index ${file}:`, err);
    }
  }

  return pages;
}

function loadRegistryFromDisk(): ContentRegistry {
  if (_cache && process.env.NODE_ENV === "production") {
    return _cache;
  }

  const now = getSafeTime();
  if (_cache && now - _cacheTime < CACHE_TTL) {
    return _cache;
  }

  const signature = getDiskSignature();
  if (_cache && _cacheSignature === signature) {
    _cacheTime = now;
    return _cache;
  }

  _cache = {
    clusters: loadClusterMetaFromDisk(),
    pages: buildPageIndexFromDisk(),
  };
  _cacheSignature = signature;
  _cacheTime = now;

  return _cache;
}

// ---------------------------------------------------------------------------
// Sync API — disk-backed (Jest, sitemap, llms.txt, build-time callers)
// ---------------------------------------------------------------------------

export function getAllSlugs(): string[] {
  return Object.keys(loadRegistryFromDisk().pages);
}

export function getRegistryEntry(slug: string): RegistryEntry | null {
  return loadRegistryFromDisk().pages[slug] ?? null;
}

export function getSlugsByCluster(cluster: ContentCluster): string[] {
  return Object.entries(loadRegistryFromDisk().pages)
    .filter(([, entry]) => entry.cluster === cluster)
    .map(([slug]) => slug);
}

export function getClusterMeta(cluster: ContentCluster): ClusterMeta | null {
  return loadRegistryFromDisk().clusters[cluster] ?? null;
}

export function getAllClusters(): Record<ContentCluster, ClusterMeta> {
  return loadRegistryFromDisk().clusters;
}

export function getContentFileName(slug: string): string | null {
  return getRegistryEntry(slug)?.file ?? null;
}

export function clearRegistryCache(): void {
  _cache = null;
  _cacheSignature = "";
  _cacheTime = 0;
}

// ---------------------------------------------------------------------------
// Async API — Laravel SEO API when SEO_CONTENT_SOURCE=api, disk otherwise
// ---------------------------------------------------------------------------

async function loadRegistryAsync(): Promise<ContentRegistry> {
  if (USE_API && API_BASE) {
    const fromApi = await loadRegistryFromApi();
    if (fromApi) return fromApi;
    console.warn("[content-registry] SEO API unavailable — falling back to disk");
  }
  return loadRegistryFromDisk();
}

export async function getAllSlugsAsync(): Promise<string[]> {
  return Object.keys((await loadRegistryAsync()).pages);
}

export async function getRegistryEntryAsync(slug: string): Promise<RegistryEntry | null> {
  return (await loadRegistryAsync()).pages[slug] ?? null;
}

export async function getSlugsByClusterAsync(cluster: ContentCluster): Promise<string[]> {
  return Object.entries((await loadRegistryAsync()).pages)
    .filter(([, entry]) => entry.cluster === cluster)
    .map(([slug]) => slug);
}

export async function getClusterMetaAsync(cluster: ContentCluster): Promise<ClusterMeta | null> {
  return (await loadRegistryAsync()).clusters[cluster] ?? null;
}

export async function getAllClustersAsync(): Promise<Record<ContentCluster, ClusterMeta>> {
  return (await loadRegistryAsync()).clusters;
}
