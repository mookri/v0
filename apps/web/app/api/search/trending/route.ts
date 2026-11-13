// /app/api/search/trending/route.ts
// Trending search queries — closes D-11.
//
// GET /api/search/trending?limit=6

import { type NextRequest } from "next/server";
import { newRequestId, ok, err } from "@/application/api-envelope";
import { laravelEnabled, laravelGet } from "@/adapters/backend/laravel-client";
import { InProcessRateLimiter, clientIp } from "@/lib/security/rate-limit";

const rateLimiter = new InProcessRateLimiter(
  { limit: 120, windowMs: 60_000 },
  { maxKeys: 50_000 },
);

const DEFAULT_TRENDING: Array<{ query: string; count: number; zero_results: boolean }> = [
  { query: "Satyanarayan Puja", count: 120, zero_results: false },
  { query: "Rudrabhishek", count: 98, zero_results: false },
  { query: "Griha Pravesh", count: 85, zero_results: false },
  { query: "Navgraha Shanti", count: 64, zero_results: false },
  { query: "Maha Mrityunjaya", count: 52, zero_results: false },
  { query: "Vastu Shanti", count: 47, zero_results: false },
];

export async function GET(req: NextRequest) {
  const requestId = newRequestId();

  const check = rateLimiter.check(clientIp(req.headers), 1);
  if (!check.allowed) {
    return Response.json(err("RATE_LIMITED", "Too many trending requests", requestId), {
      status: 429,
      headers: rateLimiter.toHeaders(check),
    });
  }

  const params = new URL(req.url).searchParams;
  const limitRaw = Number(params.get("limit"));
  const limit = Number.isFinite(limitRaw) && limitRaw > 0 ? Math.min(Math.floor(limitRaw), 20) : 6;

  let trending = DEFAULT_TRENDING.slice(0, limit);

  if (laravelEnabled()) {
    try {
      const { data } = await laravelGet<{ trending?: Array<{ query: string; count?: number; zero_results?: boolean }> }>(
        null,
        "/search/trending",
        { limit: String(limit) },
        {},
        { timeout: "fast" },
      );
      if (data?.trending && Array.isArray(data.trending) && data.trending.length > 0) {
        trending = data.trending.map((t) => ({
          query: t.query,
          count: t.count ?? 1,
          zero_results: Boolean(t.zero_results),
        }));
      }
    } catch {
      // Degrade to curated defaults on backend blip
    }
  }

  return Response.json(
    ok({ trending }, requestId),
    {
      headers: {
        "Cache-Control": "public, max-age=120, stale-while-revalidate=300",
      },
    },
  );
}

