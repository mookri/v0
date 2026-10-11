// /lib/search/federated.ts
// Hybrid (federated) search for the BFF — SEO track S37.
//
// Two legs, fused:
//   - BM25 leg: an in-process index (lib/search/bm25.ts) over the content
//     corpus. When the Laravel backend is enabled the corpus is its
//     /search/index-docs projection (lite rows, all entity types);
//     otherwise — or when the backend is unreachable — it is the disk SEO
//     corpus, so search never hard-depends on the backend being up.
//   - FULLTEXT leg: Laravel's `/api/v1/search` — the relevance authority
//     (driver FULLTEXT on MySQL, LIKE ranking on sqlite).
//
// The two ranked lists are fused with Reciprocal Rank Fusion (k=60):
// robust when the two rankers score on incompatible scales, and cheap.
//
// NOTE: deliberately no `import "server-only"` — Jest route tests import
// this module and the repo has no `server-only` test mapping. Client
// components must not import this file (it reads fs); the route handlers
// and server components that call it keep it server-side by construction.

import { readFileSync, readdirSync, existsSync } from "fs";
import { join } from "path";
import {
  laravelEnabled,
  laravelGet,
  laravelPost,
} from "@/adapters/backend/laravel-client";
import { Bm25Index, type IndexableDoc } from "@/lib/search/bm25";
import { getClusterMeta } from "@/lib/content/registry";
import type { ContentCluster } from "@/lib/content/types";

// ---------------------------------------------------------------------------
// Public shapes
// ---------------------------------------------------------------------------

export interface SearchResult {
  /** entity_type — seo_page | temple | pandit | puja_service | … */
  type: string;
  title: string;
  /** Site-relative URL. */
  url: string;
  subtitle?: string | null;
  snippet?: string | null;
  city?: string | null;
  image?: string | null;
  /** Fusion score (RRF). */
  score: number;
  /** Which leg(s) surfaced the result. */
  source: "bm25" | "fulltext" | "both";
}

export interface SuggestionItem {
  title: string;
  url: string;
  slug?: string | null;
  city?: string | null;
  subtitle?: string | null;
}

export interface SuggestionGroup {
  type: string;
  label: string;
  items: SuggestionItem[];
}

export interface SearchParams {
  q: string;
  type?: string | null;
  city?: string | null;
  limit?: number;
}

export interface SearchOutcome {
  results: SearchResult[];
  sources: { bm25: number; fulltext: number };
}

export const MAX_QUERY_LENGTH = 200;
export const MAX_SUGGEST_QUERY_LENGTH = 80;
const MAX_RESULTS_LIMIT = 20;
const DEFAULT_RESULTS_LIMIT = 10;
const SUGGEST_GROUP_LIMIT = 5;
/** Reciprocal Rank Fusion constant — 60 is the standard from Cormack et al. */
const RRF_K = 60;

// ---------------------------------------------------------------------------
// Backend response shapes (Search module: SearchController::formatResult)
// ---------------------------------------------------------------------------

interface BackendIndexDoc {
  entity_type: string;
  entity_id?: string | null;
  slug?: string | null;
  url?: string | null;
  title?: string | null;
  subtitle?: string | null;
  body?: string | null;
  city?: string | null;
}

interface BackendSearchResult {
  entity_type: string;
  entity_id?: string | null;
  slug?: string | null;
  url?: string | null;
  title?: string | null;
  subtitle?: string | null;
  snippet?: string | null;
  city?: string | null;
  image?: string | null;
  card?: Record<string, unknown> | null;
  score?: number | null;
}

interface BackendSuggestionGroup {
  type: string;
  label?: string | null;
  items: Array<{
    title?: string | null;
    slug?: string | null;
    city?: string | null;
    subtitle?: string | null;
  }>;
}

// ---------------------------------------------------------------------------
// Corpus
// ---------------------------------------------------------------------------

const CORPUS_TTL_MS = 10 * 60_000;
const CORPUS_MAX_KEYS = 500;

let corpusCache: { index: Bm25Index; builtAt: number } | null = null;
/** Busts the corpus cache (wired to POST /api/revalidate's content edits). */
export function invalidateSearchIndex(): void {
  corpusCache = null;
}

const PAGES_DIR = join(process.cwd(), "content", "pages");

function stripTags(html: string): string {
  return html
    .replace(/<[^>]+>/g, " ")
    .replace(/&nbsp;/gi, " ")
    .replace(/&amp;/gi, "&")
    .replace(/&lt;/gi, "<")
    .replace(/&gt;/gi, ">")
    .replace(/&#39;/gi, "'")
    .replace(/&quot;/gi, '"')
    .replace(/\s+/g, " ")
    .trim();
}

function truncate(value: string, max: number): string {
  if (value.length <= max) return value;
  return `${value.slice(0, max).trimEnd()}…`;
}

/** Site-relative URL for a disk SEO page (`/{basePath}/{slug}`). */
export function seoPageUrl(slug: string, cluster: string): string {
  const meta = getClusterMeta(cluster as ContentCluster);
  const basePath = meta?.basePath ?? cluster;
  return basePath === "pujas" ? `/pujas/${slug}` : `/${basePath}/${slug}`;
}

function loadDiskDocs(): IndexableDoc[] {
  if (!existsSync(PAGES_DIR)) return [];

  const docs: IndexableDoc[] = [];

  for (const file of readdirSync(PAGES_DIR)) {
    if (!file.endsWith(".json")) continue;
    try {
      const raw = readFileSync(join(PAGES_DIR, file), "utf-8");
      const page = JSON.parse(raw) as {
        slug?: string;
        cluster?: string;
        title?: string;
        metaTitle?: string;
        metaDescription?: string;
        content?: string;
        targetCities?: string[];
        noindex?: boolean;
      };

      if (!page.slug || !page.cluster || !page.title || page.noindex) continue;

      const text = stripTags(page.content ?? "");
      docs.push({
        id: `seo_page:${page.slug}`,
        type: "seo_page",
        title: page.title,
        subtitle: page.metaDescription ?? null,
        text,
        url: seoPageUrl(page.slug, page.cluster),
        slug: page.slug,
        city: page.targetCities?.[0] ?? null,
        image: null,
      });
    } catch {
      // A malformed page must not take the whole corpus down.
    }
  }

  return docs;
}

async function loadBackendDocs(): Promise<IndexableDoc[]> {
  if (!laravelEnabled()) return [];

  try {
    const { data } = await laravelGet<BackendIndexDoc[]>(
      null,
      "/search/index-docs",
      {},
      {},
      { timeout: "normal" },
    );

    return (Array.isArray(data) ? data : [])
      .filter((d) => d && d.title && d.url)
      .map((d) => ({
        id: `${d.entity_type}:${d.slug ?? d.url}`,
        type: d.entity_type ?? "unknown",
        title: String(d.title),
        subtitle: d.subtitle ?? null,
        text: d.body ? stripTags(d.body) : null,
        url: String(d.url),
        slug: d.slug ?? null,
        city: d.city ?? null,
        image: null,
      }));
  } catch {
    // Backend down / throttled — the caller falls back to the disk corpus.
    return [];
  }
}

async function getCorpusIndex(): Promise<Bm25Index> {
  if (corpusCache && Date.now() - corpusCache.builtAt < CORPUS_TTL_MS) {
    return corpusCache.index;
  }

  // Backend projection when available; disk corpus otherwise. When the
  // backend is enabled but its index endpoint fails, disk keeps search
  // alive (single degraded leg instead of an empty result set).
  let docs = await loadBackendDocs();
  if (docs.length === 0) {
    docs = loadDiskDocs();
  }

  const index = new Bm25Index(docs.slice(0, CORPUS_MAX_KEYS * 4));
  corpusCache = { index, builtAt: Date.now() };
  return index;
}

// ---------------------------------------------------------------------------
// URL helpers for suggestion groups (backend suggest rows carry no url)
// ---------------------------------------------------------------------------

function suggestionUrl(type: string, slug: string | null, title: string): string {
  if (!slug) return `/search?q=${encodeURIComponent(title)}`;
  switch (type) {
    case "temple":
      return `/temples/${slug}`;
    case "seo_page":
      return seoPageUrl(slug, "brand"); // best effort; see note below
    case "pandit":
      return `/find-pandit?q=${encodeURIComponent(title)}`;
    case "puja_service":
      return `/pujas?q=${encodeURIComponent(title)}`;
    default:
      return `/search?q=${encodeURIComponent(title)}`;
  }
}

// ---------------------------------------------------------------------------
// Fusion
// ---------------------------------------------------------------------------

function normalizeUrl(url: string): string {
  return url.trim().toLowerCase().replace(/\/+$/, "");
}

interface Ranked {
  item: Omit<SearchResult, "score">;
  rank: number;
}

function fuse(lists: Ranked[][], limit: number): SearchResult[] {
  const byUrl = new Map<
    string,
    { item: Omit<SearchResult, "score">; score: number; legs: number }
  >();

  lists.forEach((list, legIndex) => {
    for (const { item, rank } of list) {
      const key = normalizeUrl(item.url);
      const contribution = 1 / (RRF_K + rank);
      const existing = byUrl.get(key);
      if (existing) {
        existing.score += contribution;
        existing.legs |= legIndex + 1;
        // Prefer the richer projection (backend rows carry card/image).
        existing.item = {
          ...existing.item,
          ...Object.fromEntries(
            Object.entries(item).filter(
              ([, v]) => v !== undefined && v !== null && v !== "",
            ),
          ),
        } as Omit<SearchResult, "score">;
      } else {
        byUrl.set(key, { item, score: contribution, legs: legIndex + 1 });
      }
    }
  });

  return [...byUrl.values()]
    .map(({ item, score, legs }) => ({
      ...item,
      source: legs === 3 ? ("both" as const) : legs === 2 ? ("fulltext" as const) : ("bm25" as const),
      score: Number(score.toFixed(6)),
    }))
    .sort((a, b) => b.score - a.score || a.title.localeCompare(b.title))
    .slice(0, limit);
}

// ---------------------------------------------------------------------------
// Public API
// ---------------------------------------------------------------------------

export function clampLimit(limit: number | undefined): number {
  const parsed = Math.floor(Number(limit));
  if (!Number.isFinite(parsed) || parsed < 1) return DEFAULT_RESULTS_LIMIT;
  return Math.min(parsed, MAX_RESULTS_LIMIT);
}

/**
 * Run the hybrid search. Never throws: both legs degrade independently
 * (BM25 corpus falls back to disk; FULLTEXT errors collapse to the BM25
 * leg) so `/api/search` answers 200 with whatever it has.
 */
export async function federateSearch(params: SearchParams): Promise<SearchOutcome> {
  const limit = clampLimit(params.limit);

  const index = await getCorpusIndex();

  const bm25Docs = index.search(params.q, limit * 2);
  const bm25Items: Ranked[] = bm25Docs
    .map(({ doc }, i) => ({
      item: {
        type: doc.type,
        title: doc.title,
        url: doc.url,
        subtitle: doc.subtitle ?? null,
        snippet: doc.text ? truncate(doc.text, 200) : null,
        city: doc.city ?? null,
        image: doc.image ?? null,
        source: "bm25" as const,
      },
      rank: i + 1,
    }))
    .filter(({ item }) => {
      if (params.type && item.type !== params.type) return false;
      if (params.city && item.city && item.city.toLowerCase() !== params.city.toLowerCase())
        return false;
      return true;
    });

  let fulltextItems: Ranked[] = [];
  if (laravelEnabled()) {
    try {
      const query: Record<string, string> = { q: params.q, limit: String(limit * 2) };
      if (params.type) query.type = params.type;
      if (params.city) query.city = params.city;

      const { data } = await laravelGet<{ results?: BackendSearchResult[] }>(
        null,
        "/search",
        query,
        {},
        { timeout: "normal" },
      );

      fulltextItems = (data?.results ?? []).map((r, i) => ({
        item: {
          type: r.entity_type,
          title: r.title ?? "",
          url: r.url ?? "/search",
          subtitle: r.subtitle ?? null,
          snippet: r.snippet ?? null,
          city: r.city ?? null,
          image: r.image ?? null,
          source: "fulltext" as const,
        },
        rank: i + 1,
      }));
    } catch {
      // Rate-limited or unreachable — BM25 leg still answers.
    }
  }

  return {
    results: fuse([bm25Items, fulltextItems], limit),
    sources: { bm25: bm25Items.length, fulltext: fulltextItems.length },
  };
}

/**
 * Grouped type-ahead for the header autocomplete. Local prefix suggestions
 * first (instant, no network), backend groups merged in and deduplicated.
 */
export async function federateSuggestions(
  q: string,
  limit = 8,
): Promise<SuggestionGroup[]> {
  const query = q.trim();
  if (query.length < 2) return [];

  const groups = new Map<string, SuggestionGroup>();
  const seen = new Map<string, Set<string>>();

  const push = (group: SuggestionGroup, item: SuggestionItem) => {
    const key = normalizeUrl(item.url);
    const seenInGroup = seen.get(group.type) ?? new Set<string>();
    if (seenInGroup.has(key)) return;
    seenInGroup.add(key);
    seen.set(group.type, seenInGroup);

    const existing = groups.get(group.type);
    if (existing) {
      existing.items.push(item);
    } else {
      groups.set(group.type, { ...group, items: [item] });
    }
  };

  // Instant leg — in-process BM25 prefix suggestions.
  const index = await getCorpusIndex();
  for (const doc of index.suggest(query, limit)) {
    push(
      { type: doc.type, label: typeLabel(doc.type), items: [] },
      {
        title: doc.title,
        url: doc.url,
        slug: doc.slug ?? null,
        city: doc.city ?? null,
        subtitle: doc.subtitle ?? null,
      },
    );
  }

  // Authority leg — Laravel's suggest (FULLTEXT/LIKE over all entity types).
  if (laravelEnabled()) {
    try {
      const { data } = await laravelGet<{ suggestions?: BackendSuggestionGroup[] }>(
        null,
        "/search/suggest",
        { q: query, limit: String(limit) },
        {},
        { timeout: "fast" },
      );

      for (const group of data?.suggestions ?? []) {
        for (const item of group.items ?? []) {
          if (!item?.title) continue;
          push(
            { type: group.type, label: group.label ?? typeLabel(group.type), items: [] },
            {
              title: item.title,
              url: suggestionUrl(group.type, item.slug ?? null, item.title),
              slug: item.slug ?? null,
              city: item.city ?? null,
              subtitle: item.subtitle ?? null,
            },
          );
        }
      }
    } catch {
      // Throttled/unreachable — the instant leg already answered.
    }
  }

  return [...groups.values()]
    .map((group) => ({ ...group, items: group.items.slice(0, SUGGEST_GROUP_LIMIT) }))
    .filter((group) => group.items.length > 0);
}

function typeLabel(type: string): string {
  switch (type) {
    case "seo_page":
      return "Guides & Blogs";
    case "temple":
      return "Temples";
    case "pandit":
      return "Pandits";
    case "puja_service":
      return "Puja Services";
    default:
      return type.charAt(0).toUpperCase() + type.slice(1).replace(/[_-]/g, " ");
  }
}

/**
 * Persist one search into Laravel's `search_queries` table (the analytics
 * authority). Sanitised: query text, optional type/city and a result count
 * — never an identity, token or header. Failures are swallowed: analytics
 * must never break the search response.
 */
export async function recordSearchQuery(entry: {
  q: string;
  type?: string | null;
  city?: string | null;
  results: number;
}): Promise<void> {
  if (!laravelEnabled()) return;

  const q = entry.q.replace(/[\u0000-\u001F\u007F]/g, " ").trim().slice(0, MAX_QUERY_LENGTH);
  if (!q) return;

  try {
    await laravelPost(
      null,
      "/search/analytics",
      {
        queries: [
          {
            q,
            type: entry.type ?? undefined,
            city: entry.city ?? undefined,
            results: entry.results,
          },
        ],
      },
      {},
      { timeout: "fast" },
    );
  } catch {
    // Fire-and-forget: a throttled analytics sink is not a search failure.
  }
}
