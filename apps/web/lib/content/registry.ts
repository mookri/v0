// /lib/content/registry.ts
// Builds the SEO content registry from content/pages so route resolution stays
// in sync with disk even during a long-running dev session.

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

let _cache: ContentRegistry | null = null;
let _cacheSignature = "";

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

/**
 * Load the registry from disk and refresh it automatically when content/page
 * files or registry.json change.
 */
function loadRegistry(): ContentRegistry {
  const signature = getDiskSignature();
  if (_cache && _cacheSignature === signature) {
    return _cache;
  }

  _cache = {
    clusters: loadClusterMetaFromDisk(),
    pages: buildPageIndexFromDisk(),
  };
  _cacheSignature = signature;

  return _cache;
}

/**
 * Get all page slugs.
 */
export function getAllSlugs(): string[] {
  const registry = loadRegistry();
  return Object.keys(registry.pages);
}

/**
 * Get the registry entry for a slug.
 */
export function getRegistryEntry(slug: string): RegistryEntry | null {
  const registry = loadRegistry();
  return registry.pages[slug] ?? null;
}

/**
 * Get all slugs belonging to a specific cluster.
 */
export function getSlugsByCluster(cluster: ContentCluster): string[] {
  const registry = loadRegistry();
  return Object.entries(registry.pages)
    .filter(([, entry]) => entry.cluster === cluster)
    .map(([slug]) => slug);
}

/**
 * Get cluster metadata.
 */
export function getClusterMeta(cluster: ContentCluster): ClusterMeta | null {
  const registry = loadRegistry();
  return registry.clusters[cluster] ?? null;
}

/**
 * Get all clusters.
 */
export function getAllClusters(): Record<ContentCluster, ClusterMeta> {
  const registry = loadRegistry();
  return registry.clusters;
}

/**
 * Get the content file name for a slug.
 */
export function getContentFileName(slug: string): string | null {
  const entry = getRegistryEntry(slug);
  return entry?.file ?? null;
}

/**
 * Clear the registry cache (used after content updates).
 */
export function clearRegistryCache(): void {
  _cache = null;
  _cacheSignature = "";
}
