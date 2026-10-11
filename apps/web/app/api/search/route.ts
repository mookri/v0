// /app/api/search/route.ts
// Public federated search — SEO track S39.
//
// GET /api/search?q=&type=&city=&limit=
//
// Hybrid by design (see lib/search/federated.ts): an in-process BM25 leg
// over the content corpus fused with Laravel's FULLTEXT authority leg
// (reciprocal rank fusion). Anonymous by design — the backend search
// surface is public (platform-navdhya S36 opted it out of auth.any), so
// no bearer and no session ride along.
//
// Every failure path degrades to the BM25 leg rather than an error: a
// backend blip or a 429 from the backend throttle must not blank out the
// marketing site's search box. Outage visibility is /api/readyz's job.

import { type NextRequest } from "next/server";
import { after } from "next/server";
import { newRequestId, ok, err } from "@/application/api-envelope";
import { laravelEnabled } from "@/adapters/backend/laravel-client";
import { InProcessRateLimiter, clientIp } from "@/lib/security/rate-limit";
import {
  federateSearch,
  recordSearchQuery,
  MAX_QUERY_LENGTH,
} from "@/lib/search/federated";

// Per-IP: 60 searches/min — mirrors the backend's own throttle so the BFF
// never forwards a burst the backend will reject anyway.
const rateLimiter = new InProcessRateLimiter(
  { limit: 60, windowMs: 60_000 },
  { maxKeys: 50_000 },
);

export async function GET(req: NextRequest) {
  const requestId = newRequestId();

  const check = rateLimiter.check(clientIp(req.headers), 1);
  if (!check.allowed) {
    return Response.json(err("RATE_LIMITED", "Too many search requests", requestId), {
      status: 429,
      headers: rateLimiter.toHeaders(check),
    });
  }

  // Parse from req.url (not req.nextUrl) so the handler is testable with a
  // plain Request — both share the same URL contract.
  const params = new URL(req.url).searchParams;
  const q = (params.get("q") ?? "").trim();

  if (!q) {
    return Response.json(
      err("VALIDATION_ERROR", 'Query parameter "q" is required', requestId),
      { status: 400 },
    );
  }
  if (q.length > MAX_QUERY_LENGTH) {
    return Response.json(
      err("VALIDATION_ERROR", `Query must be at most ${MAX_QUERY_LENGTH} characters`, requestId),
      { status: 400 },
    );
  }

  const type = params.get("type");
  const city = params.get("city");
  const limitRaw = Number(params.get("limit"));
  const limit = Number.isFinite(limitRaw) && limitRaw > 0 ? Math.min(Math.floor(limitRaw), 20) : 10;

  const { results, sources } = await federateSearch({ q, type, city, limit });

  // Persist the query into the backend's search_queries table (sanitised:
  // text + type + city + result count, never an identity). Runs after the
  // response is flushed; analytics must not add latency to search. Gated
  // on the backend being enabled — after() is a Next-runtime API and
  // recordSearchQuery is a no-op without a backend anyway.
  if (laravelEnabled()) {
    after(() => recordSearchQuery({ q, type, city, results: results.length }));
  }

  return Response.json(
    ok({ query: q, results, sources, count: results.length }, requestId),
    {
      headers: {
        // Result pages are per-query; the CDN may coalesce identical
        // queries for a short window but must not serve stale counts long.
        "Cache-Control": "public, max-age=30, stale-while-revalidate=60",
      },
    },
  );
}
