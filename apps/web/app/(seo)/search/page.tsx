// /app/(seo)/search/page.tsx
// Site search results page — SEO track S40.
//
// Renders the federated results (S39's lib/search/federated) directly in a
// server component: no client fetch round-trip, one in-process BM25 leg +
// Laravel FULLTEXT leg fused exactly like /api/search does. The query is
// read from ?q= (plus optional ?type= / ?city= / ?limit= filters).
//
// noindex by contract (ROUTE_RULES + metadata below): a per-query results
// page must never enter the sitemap or the index — the entity pages it
// links to are the indexable surface.
//
// Under cacheComponents, reading searchParams here is the dynamic hole the
// (seo) layout's Suspense boundary exists for; the static shell streams
// instantly and the results fill in.

import type { Metadata } from "next";
import Link from "next/link";
import { after } from "next/server";
import { laravelEnabled } from "@/adapters/backend/laravel-client";
import {
  federateSearch,
  recordSearchQuery,
  type SearchResult,
} from "@/lib/search/federated";
import {
  SEARCH_TYPES,
  buildSearchHref,
  clampSearchQuery,
  firstParam,
  isSearchType,
  typeLabel,
} from "@/lib/search/params";
import { SearchBox } from "@/components/search/search-box";

export const metadata: Metadata = {
  title: "Search",
  description: "Search temples, pandits, puja services and spiritual guides on Navdhya.",
  robots: { index: false, follow: true },
};

interface SearchPageProps {
  searchParams: Promise<Record<string, string | string[] | undefined>>;
}

/** One result row. Book CTAs appear for bookable entity types. */
function ResultCard({ result }: { result: SearchResult }) {
  const bookable = result.type === "puja_service" || result.type === "temple";

  return (
    <li className="rounded-sm border border-border bg-card p-5">
      <div className="flex items-start justify-between gap-4">
        <div className="min-w-0">
          <div className="mb-1 flex items-center gap-2 text-[11px] font-semibold uppercase tracking-wide">
            <span className="rounded-full bg-muted px-2 py-0.5 text-muted-foreground">
              {typeLabel(result.type)}
            </span>
            {result.city && (
              <span className="text-muted-foreground">{result.city}</span>
            )}
          </div>
          <h2 className="truncate text-base font-semibold text-foreground">
            <Link href={result.url} className="hover:text-[#FF4500]">
              {result.title}
            </Link>
          </h2>
          {result.subtitle && (
            <p className="mt-0.5 truncate text-sm text-muted-foreground">
              {result.subtitle}
            </p>
          )}
          {result.snippet && (
            <p className="mt-2 line-clamp-2 text-sm text-muted-foreground">
              {result.snippet}
            </p>
          )}
        </div>
        {bookable && (
          <Link
            href="/book"
            className="shrink-0 rounded-sm bg-[#FF4500] px-4 py-2 text-[13px] font-semibold text-white transition-colors hover:bg-[#E63E00]"
          >
            Book
          </Link>
        )}
      </div>
    </li>
  );
}

export default async function SearchPage({ searchParams }: SearchPageProps) {
  const params = await searchParams;

  const q = clampSearchQuery(firstParam(params.q));
  const rawType = firstParam(params.type);
  const type = isSearchType(rawType) ? rawType : null;
  const city = firstParam(params.city) || null;
  const limitRaw = Number(firstParam(params.limit));
  const limit = Number.isFinite(limitRaw) && limitRaw > 0 ? Math.min(Math.floor(limitRaw), 20) : 10;

  const hasQuery = q.length >= 2;
  const { results } = hasQuery
    ? await federateSearch({ q, type, city, limit })
    : { results: [] as SearchResult[] };

  // Same analytics contract as /api/search: sanitised query + counts, no
  // identity, fire-and-forget after the response flushes.
  if (hasQuery && laravelEnabled()) {
    after(() => recordSearchQuery({ q, type, city, results: results.length }));
  }

  // Type chips: every known type, active one highlighted, preserving q/city.
  const chips = [
    { value: null, label: "All" },
    ...SEARCH_TYPES.map((value) => ({ value, label: typeLabel(value) })),
  ];

  return (
    <div className="min-h-[60vh] bg-background">
      <div className="container mx-auto max-w-3xl px-6 py-10 md:px-8">
        <h1 className="mb-6 text-2xl font-serif font-bold text-foreground md:text-3xl">
          Search
        </h1>

        <SearchBox variant="page" initialQuery={q} autoFocus={!q} />

        <nav aria-label="Filter by type" className="mt-4 flex flex-wrap gap-2">
          {chips.map((chip) => {
            const active = chip.value === type;
            return (
              <Link
                key={chip.label}
                href={buildSearchHref({ q, type: chip.value, city })}
                aria-current={active ? "true" : undefined}
                className={`rounded-full border px-3 py-1 text-[13px] font-medium transition-colors ${
                  active
                    ? "border-[#FF4500] bg-[#FF4500] text-white"
                    : "border-border text-foreground hover:bg-muted"
                }`}
              >
                {chip.label}
              </Link>
            );
          })}
        </nav>

        {!hasQuery ? (
          <p className="mt-10 text-muted-foreground">
            Type a temple, pandit, puja or festival name to search.
          </p>
        ) : results.length === 0 ? (
          <div className="mt-10">
            <p className="text-foreground">
              No results for <span className="font-semibold">&ldquo;{q}&rdquo;</span>
              {type && <> in {typeLabel(type)}</>}
              {city && <> near {city}</>}.
            </p>
            <p className="mt-2 text-sm text-muted-foreground">
              Try a different spelling, or{" "}
              <Link href={buildSearchHref({ q })} className="text-[#0E3F2E] hover:underline">
                clear the filters
              </Link>{" "}
              — or{" "}
              <Link href="/contact" className="text-[#0E3F2E] hover:underline">
                contact our team
              </Link>{" "}
              and we&apos;ll find it for you.
            </p>
          </div>
        ) : (
          <>
            <p className="mt-8 text-sm text-muted-foreground">
              {results.length} result{results.length === 1 ? "" : "s"} for
              <span className="font-medium text-foreground"> &ldquo;{q}&rdquo;</span>
            </p>
            <ul className="mt-4 space-y-3">
              {results.map((result) => (
                <ResultCard key={`${result.type}:${result.url}`} result={result} />
              ))}
            </ul>
          </>
        )}
      </div>
    </div>
  );
}
