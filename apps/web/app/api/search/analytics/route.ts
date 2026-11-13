// /app/api/search/analytics/route.ts
// Search analytics ingestion proxy — closes D-11.
//
// POST /api/search/analytics
// Accepts either { query, results, type, city } from search-box
// or { queries: [...] } from batch callers, forwarding to
// Laravel backend search_queries / analytics pipeline.

import { type NextRequest } from "next/server";
import { after } from "next/server";
import { newRequestId, ok, err } from "@/application/api-envelope";
import { laravelEnabled, laravelPost } from "@/adapters/backend/laravel-client";
import { InProcessRateLimiter, clientIp } from "@/lib/security/rate-limit";
import { MAX_QUERY_LENGTH } from "@/lib/search/federated";

const rateLimiter = new InProcessRateLimiter(
  { limit: 120, windowMs: 60_000 },
  { maxKeys: 50_000 },
);

interface AnalyticsPayload {
  query?: string;
  q?: string;
  results?: number;
  type?: string | null;
  city?: string | null;
  queries?: Array<{
    q?: string;
    query?: string;
    results?: number;
    type?: string | null;
    city?: string | null;
  }>;
}

export async function POST(req: NextRequest) {
  const requestId = newRequestId();

  const check = rateLimiter.check(clientIp(req.headers), 1);
  if (!check.allowed) {
    return Response.json(err("RATE_LIMITED", "Too many analytics requests", requestId), {
      status: 429,
      headers: rateLimiter.toHeaders(check),
    });
  }

  let body: AnalyticsPayload;
  try {
    body = await req.json();
  } catch {
    return Response.json(
      err("VALIDATION_ERROR", "Invalid JSON payload", requestId),
      { status: 400 },
    );
  }

  const queries: Array<{ q: string; results: number; type?: string | null; city?: string | null }> = [];

  if (Array.isArray(body.queries)) {
    for (const item of body.queries) {
      const q = (item.q ?? item.query ?? "").trim();
      if (q) {
        queries.push({
          q: q.slice(0, MAX_QUERY_LENGTH),
          results: typeof item.results === "number" ? item.results : 0,
          type: item.type ?? null,
          city: item.city ?? null,
        });
      }
    }
  } else {
    const q = (body.q ?? body.query ?? "").trim();
    if (q) {
      queries.push({
        q: q.slice(0, MAX_QUERY_LENGTH),
        results: typeof body.results === "number" ? body.results : 0,
        type: body.type ?? null,
        city: body.city ?? null,
      });
    }
  }

  if (queries.length === 0) {
    return Response.json(
      err("VALIDATION_ERROR", "No valid queries provided", requestId),
      { status: 400 },
    );
  }

  if (laravelEnabled()) {
    after(async () => {
      try {
        await laravelPost(
          null,
          "/search/analytics",
          { queries },
          {},
          { timeout: "fast" },
        );
      } catch {
        // fail-open
      }
    });
  }

  return Response.json(ok({ accepted: queries.length }, requestId));
}

