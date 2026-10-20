// /app/api/search/suggest/route.ts
// Public search autocomplete — SEO track S39.
//
// GET /api/search/suggest?q=&limit=
//
// Grouped type-ahead (by entity type) for the header search box and the
// /search page. Two legs, merged and deduplicated: the in-process BM25
// prefix suggestions (instant, no network) and Laravel's suggest endpoint
// (the authority across temples, pandits, puja services and guides).
// Anonymous like the rest of the search surface; degrades to whichever
// leg answered.

import { type NextRequest } from "next/server";
import { newRequestId, ok, err } from "@/application/api-envelope";
import { InProcessRateLimiter, clientIp } from "@/lib/security/rate-limit";
import {
  federateSuggestions,
  MAX_SUGGEST_QUERY_LENGTH,
} from "@/lib/search/federated";

// Autocomplete fires per keystroke — 120/min per IP keeps a fast typist
// well under the limit while still bounding abuse.
const rateLimiter = new InProcessRateLimiter(
  { limit: 120, windowMs: 60_000 },
  { maxKeys: 50_000 },
);

export async function GET(req: NextRequest) {
  const requestId = newRequestId();

  const check = rateLimiter.check(clientIp(req.headers), 1);
  if (!check.allowed) {
    return Response.json(err("RATE_LIMITED", "Too many suggestion requests", requestId), {
      status: 429,
      headers: rateLimiter.toHeaders(check),
    });
  }

  // Parse from req.url (not req.nextUrl) so the handler is testable with a
  // plain Request — both share the same URL contract.
  const params = new URL(req.url).searchParams;
  const q = (params.get("q") ?? "").trim();

  // Below two characters there is nothing to suggest — an empty list, not
  // an error, so the header box can simply stop fetching.
  if (q.length < 2) {
    return Response.json(ok({ query: q, suggestions: [] }, requestId), {
      headers: { "Cache-Control": "public, max-age=30" },
    });
  }
  if (q.length > MAX_SUGGEST_QUERY_LENGTH) {
    return Response.json(
      err("VALIDATION_ERROR", `Query must be at most ${MAX_SUGGEST_QUERY_LENGTH} characters`, requestId),
      { status: 400 },
    );
  }

  const limitRaw = Number(params.get("limit"));
  const limit = Number.isFinite(limitRaw) && limitRaw > 0 ? Math.min(Math.floor(limitRaw), 20) : 8;

  const suggestions = await federateSuggestions(q, limit);

  return Response.json(ok({ query: q, suggestions }, requestId), {
    // Suggestions are stale-safe for a minute and shareable per query.
    headers: { "Cache-Control": "public, max-age=60" },
  });
}
